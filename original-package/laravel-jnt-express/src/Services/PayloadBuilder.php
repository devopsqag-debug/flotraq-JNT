<?php

namespace JntExpress\Services;

use JntExpress\Contracts\ShippableOrder;
use JntExpress\Support\Settings;
use JntExpress\Support\Weight;

/**
 * Turns an order into the field set J&T's createOrder API expects
 * (same rules as the WooCommerce plugin's Jnt_Helper::process_order).
 */
class PayloadBuilder
{
    public function __construct(protected Settings $settings) {}

    public function build(ShippableOrder $order, bool $insurance = false): array
    {
        $key = $order->jntOrderKey();

        $phone = $order->jntReceiverPhone();
        if (str_contains($phone, '/')) {
            $phone = trim(explode('/', $phone)[0]);
        }

        [$weight, $itemName, $qty] = $this->itemsSummary($order);

        $cod = $order->jntIsCashOnDelivery() ? $order->jntTotal() : 0;

        return [
            'id' => $key,
            // sender
            'sender_name' => $this->settings->get('name'),
            'sender_phone' => $this->settings->get('phone'),
            'sender_addr' => $this->settings->senderAddress(),
            'sender_zip' => $this->settings->get('postcode'),
            'cuscode' => $this->settings->get('vipcode'),
            'password' => $this->settings->get('apikey'),
            // receiver
            'receiver_name' => $order->jntReceiverName(),
            'receiver_phone' => $phone,
            'receiver_addr' => $order->jntReceiverAddress(),
            'receiver_zip' => $order->jntReceiverPostcode(),
            // parcel
            'orderid' => date('ymdHi') . str_pad((string) $key, 6, '0', STR_PAD_LEFT),
            'weight' => $weight,
            'item' => $itemName,
            'item_value' => $order->jntSubtotal(),
            'qty' => $qty,
            'payType' => 'PP_PM',
            'goodsType' => 'PARCEL',
            'servicetype' => $this->settings->get('service', '1'),
            'expresstype' => 'EZ',
            'goodsdesc' => '#' . $key . ' - ' . $order->jntCustomerNote(),
            'cod' => $cod,
            'offerFeeFlag' => $insurance ? 1 : 0,
        ];
    }

    /** @return array{0: float, 1: string, 2: int} weight (kg), item text, total quantity */
    public function itemsSummary(ShippableOrder $order): array
    {
        $weight = 0.0;
        $names = [];
        $qty = 0;

        foreach ($order->jntItems() as $item) {
            $q = $item['quantity'] ?? 0;
            $qty += (int) $q;
            if (!empty($item['virtual'])) {
                continue; // virtual products are not shipped
            }
            if (is_numeric($item['weight'] ?? null) && is_numeric($q)) {
                $weight += (float) $item['weight'] * (float) $q;
            }
            $names[] = $q . ' X ' . $item['name'];
        }

        if ($weight == 0.0) {
            $weight = 0.1;
        } else {
            $weight = Weight::toKg($weight, $order->jntWeightUnit());
            if ($weight <= 0.01) {
                $weight = 0.01;
            }
        }

        return [round($weight, 3), implode(', ', $names), $qty];
    }
}
