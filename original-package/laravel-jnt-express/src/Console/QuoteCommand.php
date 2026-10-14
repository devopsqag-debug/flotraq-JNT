<?php

namespace JntExpress\Console;

use Illuminate\Console\Command;
use JntExpress\Services\RateService;

class QuoteCommand extends Command
{
    protected $signature = 'jnt:quote {weight : Parcel weight in kg} {postcode : Receiver postcode}';
    protected $description = 'Get a J&T shipping quote (includes configured markup)';

    public function handle(RateService $rates): int
    {
        $fee = $rates->quote((float) $this->argument('weight'), $this->argument('postcode'));

        if ($fee === null) {
            $this->error('No rate available (check settings, postcode and rate key).');

            return self::FAILURE;
        }

        $this->info('MYR ' . number_format($fee, 2));

        return self::SUCCESS;
    }
}
