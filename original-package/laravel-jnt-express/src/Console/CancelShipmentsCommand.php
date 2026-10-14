<?php

namespace JntExpress\Console;

use Illuminate\Console\Command;
use JntExpress\Services\ShipmentService;

class CancelShipmentsCommand extends Command
{
    protected $signature = 'jnt:cancel {shipments* : Waybill numbers or shipment ids}';
    protected $description = 'Cancel J&T orders';

    public function handle(ShipmentService $service): int
    {
        try {
            $results = $service->cancel($this->argument('shipments'));
        } catch (\DomainException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->table(['Order', 'Result', 'Message'], $results->map(fn ($r) => [$r['key'], $r['success'] ? 'OK' : 'FAILED', $r['message']])->all());

        return $results->isNotEmpty() && $results->every(fn ($r) => $r['success']) ? self::SUCCESS : self::FAILURE;
    }
}
