<?php

namespace JntExpress\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Illuminate\Support\Collection create(iterable $orders, bool $insurance = false)
 * @method static \Illuminate\Support\Collection cancel(iterable $shipments)
 * @method static string print(iterable $shipments, string $format = 'consignment')
 * @method static float|null quote(float $weightKg, string $postcode, string $country = 'MY')
 * @method static float|null quoteForOrder(\JntExpress\Contracts\ShippableOrder $order)
 * @method static array handleWebhook(string $awb, int|string|null $scanCode)
 * @method static \JntExpress\Support\Settings settings()
 */
class Jnt extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \JntExpress\JntManager::class;
    }
}
