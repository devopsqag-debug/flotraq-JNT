<?php

namespace JntExpress\Services;

use JntExpress\Enums\ShipmentStatus;
use JntExpress\Models\JntShipment;

/** Optionally mirrors shipment status onto your own order's status column. */
class OrderStatusSync
{
    public function apply(JntShipment $shipment, ShipmentStatus $status): void
    {
        $column = config('jnt.order_status.column');
        $value = config('jnt.order_status.map.' . $status->value);
        $order = $shipment->shippable;

        if (!$column || $value === null || !$order) {
            return;
        }

        $order->forceFill([$column => $value])->save();
    }
}
