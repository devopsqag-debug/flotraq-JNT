<?php

namespace JntExpress\Events;

use JntExpress\Models\JntShipment;

class ShipmentCreated
{
    public function __construct(public JntShipment $shipment) {}
}
