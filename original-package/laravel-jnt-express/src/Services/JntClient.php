<?php

namespace JntExpress\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/**
 * Thin wrapper around J&T Malaysia's HTTP API (port of Jnt_Api).
 */
class JntClient
{
    protected function http(): PendingRequest
    {
        return Http::timeout((int) config('jnt.api.timeout', 30))
            ->withOptions(['verify' => (bool) config('jnt.api.verify_ssl', true)]);
    }

    protected function url(string $endpoint): string
    {
        return rtrim(config('jnt.api.base_url'), '/') . config("jnt.api.endpoints.{$endpoint}");
    }

    protected function account(): array
    {
        return [config('jnt.api.username'), config('jnt.api.api_key')];
    }

    /** md5 of "<json><key>" then base64 (J&T quirk: base64 of the hex string). */
    public static function sign(string $json, string $key): string
    {
        return base64_encode(md5($json . $key));
    }

    /**
     * Create one consignment. $f is the field set from PayloadBuilder.
     *
     * @return array{ok:bool, awb:?string, order_id:?string, code:?string, status:?string, reason:?string, raw:mixed, request:array}
     */
    public function createOrder(array $f, string $signKey): array
    {
        [$user, $apiKey] = $this->account();

        $data = ['detail' => [[
            'username' => $user,
            'api_key' => $apiKey,
            'cuscode' => $f['cuscode'],
            'password' => $f['password'],
            'orderid' => $f['orderid'],
            'shipper_name' => $f['sender_name'],
            'shipper_addr' => $f['sender_addr'],
            'shipper_contact' => $f['sender_name'],
            'shipper_phone' => $f['sender_phone'],
            'sender_zip' => $f['sender_zip'],
            'receiver_name' => $f['receiver_name'],
            'receiver_addr' => $f['receiver_addr'],
            'receiver_phone' => $f['receiver_phone'],
            'receiver_zip' => $f['receiver_zip'],
            'qty' => $f['qty'],
            'weight' => $f['weight'],
            'servicetype' => $f['servicetype'],
            'item_name' => mb_substr((string) $f['item'], 0, 200, 'UTF-8'),
            'goodsType' => $f['goodsType'],
            'goodsvalue' => $f['item_value'],
            'goodsdesc' => $f['goodsdesc'],
            'expressType' => $f['expresstype'],
            'payType' => $f['payType'],
            'cod' => $f['cod'],
            'offerFeeFlag' => $f['offerFeeFlag'],
        ]]];

        $json = json_encode($data);
        $post = ['data_param' => $json, 'data_sign' => self::sign($json, $signKey)];

        $decoded = $this->postForm('create', $post);
        $item = self::firstItem($decoded);

        $awb = $item['awb_no'] ?? null;

        return [
            'ok' => !empty($awb),
            'awb' => $awb ?: null,
            'order_id' => $item['orderid'] ?? $f['orderid'],
            'code' => $item['data']['code'] ?? null,
            'status' => $item['status'] ?? null,
            'reason' => $item['reason'] ?? ($decoded === null ? 'Invalid response from J&T' : null),
            'raw' => $decoded,
            'request' => $data,
        ];
    }

    /** @return array{ok:bool, status:?string, reason:?string, awb:?string, raw:mixed} */
    public function cancelOrder(string $awb, string $orderId, string $signKey): array
    {
        [$user, $apiKey] = $this->account();

        $data = [
            'username' => $user,
            'api_key' => $apiKey,
            'awb_no' => $awb,
            'orderid' => $orderId,
            'remark' => '',
        ];

        $json = json_encode($data);
        $decoded = $this->postForm('cancel', ['data_param' => $json, 'data_sign' => self::sign($json, $signKey)]);
        $item = self::firstItem($decoded);

        return [
            'ok' => ($item['status'] ?? null) === 'success',
            'status' => $item['status'] ?? null,
            'reason' => $item['reason'] ?? ($decoded === null ? 'Invalid response from J&T' : null),
            'awb' => $item['awb_no'] ?? $awb,
            'raw' => $decoded,
        ];
    }

    /**
     * Fetch a consignment-note PDF for one or more comma-separated waybills.
     *
     * @param  string $format  consignment (new layout) | thermal | a4
     * @return string raw PDF bytes
     */
    public function print(string $customerCode, string $awbs, string $format = 'consignment'): string
    {
        [$user, $apiKey] = $this->account();

        $data = [
            'billcode' => $awbs,
            'account' => $user,
            'password' => $apiKey,
            'customercode' => $customerCode,
        ];
        if ($format === 'consignment') {
            $data['enableNewPrint'] = 'mdzt';
        }

        $post = [
            'logistics_interface' => json_encode($data),
            'data_digest' => md5($awbs),
            'msg_type' => '1',
        ];

        $res = $this->http()->asForm()->post($this->url($format === 'a4' ? 'print_a4' : 'print'), $post);

        return $res->body();
    }

    /** Quoted freight for one parcel, or null when J&T gives no price. */
    public function quote(float $weightKg, string $senderZip, string $receiverZip, string $cuscode, string $password, string $rateKey): ?float
    {
        $data = [
            'customerCode' => $cuscode,
            'password' => $password,
            'expressType' => 'EZ',
            'goodsType' => 'PARCEL',
            'pcs' => 1,
            'receiverPostcode' => $receiverZip,
            'senderPostcode' => $senderZip,
            'weight' => $weightKg,
        ];

        $json = json_encode($data);

        $res = $this->http()
            ->withHeaders([
                'Content-Type' => 'application/json',
                'account' => config('jnt.api.username'),
                'sign' => hash('sha256', $json . $rateKey),
            ])
            ->withBody($json, 'application/json')
            ->post($this->url('rate'));

        $freight = $res->json('data.totalFreight');

        return is_numeric($freight) && (float) $freight > 0 ? (float) $freight : null;
    }

    protected function postForm(string $endpoint, array $post): mixed
    {
        try {
            $res = $this->http()->asForm()->post($this->url($endpoint), $post);

            return json_decode($res->body(), true);
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }

    /**
     * J&T wraps results as {"<key>": [ {...} ]} (create) or {"details": [ {...} ]} (cancel).
     * Return the first record regardless of the wrapper key.
     */
    public static function firstItem(mixed $decoded): array
    {
        if (!is_array($decoded)) {
            return [];
        }
        if (isset($decoded['details'][0]) && is_array($decoded['details'][0])) {
            return $decoded['details'][0];
        }
        foreach ($decoded as $value) {
            if (is_array($value) && isset($value[0]) && is_array($value[0])) {
                return $value[0];
            }
        }

        return isset($decoded['awb_no']) || isset($decoded['status']) ? $decoded : [];
    }
}
