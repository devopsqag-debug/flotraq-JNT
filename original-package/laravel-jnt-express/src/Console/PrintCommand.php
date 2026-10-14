<?php

namespace JntExpress\Console;

use Illuminate\Console\Command;
use JntExpress\Services\ShipmentService;

class PrintCommand extends Command
{
    protected $signature = 'jnt:print {shipments* : Waybill numbers or shipment ids}
                            {--format=consignment : consignment | thermal | a4}
                            {--output= : File path (default storage/app/jnt-<format>.pdf)}';
    protected $description = 'Download consignment note PDF(s) from J&T';

    public function handle(ShipmentService $service): int
    {
        try {
            $pdf = $service->print($this->argument('shipments'), $this->option('format'));
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $path = $this->option('output') ?: storage_path('app/jnt-' . $this->option('format') . '.pdf');
        file_put_contents($path, $pdf);
        $this->info("Saved {$path}");

        return self::SUCCESS;
    }
}
