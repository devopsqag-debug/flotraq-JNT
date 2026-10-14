<?php

namespace JntExpress\Events;

use JntExpress\Models\JntShipment;

class ShipmentCancelled
{
    public function __construct(public JntShipment $shipment) {}
}
