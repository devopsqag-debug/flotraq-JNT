<?php

namespace JntExpress\Console;

use Illuminate\Console\Command;
use JntExpress\Services\ShipmentService;

class CreateShipmentsCommand extends Command
{
    protected $signature = 'jnt:create {orders* : Order ids} {--insurance : Order with insurance}';
    protected $description = 'Create J&T consignment notes for the given orders';

    public function handle(ShipmentService $service): int
    {
        try {
            $results = $service->create($this->argument('orders'), (bool) $this->option('insurance'));
        } catch (\DomainException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->table(['Order', 'Result', 'Message'], $results->map(fn ($r) => [$r['key'], $r['success'] ? 'OK' : 'FAILED', $r['message']])->all());

        return $results->every(fn ($r) => $r['success']) ? self::SUCCESS : self::FAILURE;
    }
}
