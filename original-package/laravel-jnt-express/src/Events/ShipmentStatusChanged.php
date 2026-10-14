<?php

namespace JntExpress\Events;

use JntExpress\Enums\ShipmentStatus;
use JntExpress\Models\JntShipment;

class ShipmentStatusChanged
{
    public function __construct(
        public JntShipment $shipment,
        public ?ShipmentStatus $from,
        public ShipmentStatus $to,
    ) {}
}
