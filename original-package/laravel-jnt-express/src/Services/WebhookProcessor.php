<?php

namespace JntExpress\Services;

use JntExpress\Enums\ShipmentStatus;
use JntExpress\Events\ShipmentStatusChanged;
use JntExpress\Models\JntShipment;

/** Handles J&T "scan" callbacks (port of Jnt_Callback::jnt_process_webhook). */
class WebhookProcessor
{
    public function __construct(protected OrderStatusSync $sync) {}

    public static function ok(): array
    {
        return ['code' => '1', 'message' => 'success', 'data' => 'SUCCESS'];
    }

    public static function fail(string $why): array
    {
        return ['code' => '0', 'message' => 'fail', 'data' => $why];
    }

    public function process(string $awb, int|string|null $scanTypeCode): array
    {
        if ($awb !== '' && $awb === (string) config('jnt.webhook.test_awb')) {
            return self::ok();
        }

        $shipment = JntShipment::query()->where('awb', $awb)->latest('id')->first();

        if (!$shipment) {
            return self::fail('Order Not Found!');
        }

        $to = ShipmentStatus::fromScanCode($scanTypeCode);

        if ($to === null || $shipment->status === $to) {
            return self::ok(); // unknown scan code, or a retry of the same update
        }

        $from = $shipment->status;
        $shipment->update(['status' => $to]);
        $shipment->addEvent($to->note(), $to, (string) $scanTypeCode);
        $this->sync->apply($shipment, $to);

        event(new ShipmentStatusChanged($shipment, $from, $to));

        return self::ok();
    }
}
