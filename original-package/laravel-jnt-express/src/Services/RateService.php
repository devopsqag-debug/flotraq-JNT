<?php

namespace JntExpress\Services;

use Illuminate\Support\Facades\Cache;
use JntExpress\Contracts\ShippableOrder;
use JntExpress\Support\Settings;
use JntExpress\Support\Weight;

/** Live shipping rate (port of Jnt_Helper::shipping_rate + Jnt_Shipping::calculate_shipping). */
class RateService
{
    public function __construct(
        protected JntClient $client,
        protected Settings $settings,
        protected PayloadBuilder $builder,
    ) {}

    /**
     * Quote a parcel, including the configured markup.
     * Returns null when J&T cannot quote (the WordPress plugin silently returned 0 here,
     * which would have meant free shipping).
     */
    public function quote(float $weightKg, string $receiverPostcode, string $country = 'MY'): ?float
    {
        if (!$this->settings->enabled() || !in_array(strtoupper($country), config('jnt.countries', ['MY']), true)) {
            return null;
        }

        $weightKg = max($weightKg, 0.01);
        $sender = (string) $this->settings->get('postcode');
        $ttl = (int) config('jnt.rate_cache_ttl', 0);

        $fetch = fn () => $this->client->quote(
            $weightKg,
            $sender,
            $receiverPostcode,
            (string) $this->settings->get('vipcode'),
            (string) $this->settings->get('apikey'),
            (string) $this->settings->get('ratekey'),
        );

        $fee = $ttl > 0
            ? Cache::remember("jnt.rate.{$sender}.{$receiverPostcode}." . number_format($weightKg, 3, '.', ''), $ttl, $fetch)
            : $fetch();

        if ($fee === null) {
            return null;
        }

        return round($fee + (float) $this->settings->get('markup', 0), 2);
    }

    /** Weight of a cart/package: [['weight'=>1.2,'quantity'=>2], ...] in $unit, returned in kg. */
    public function cartWeightKg(iterable $lines, string $unit = 'kg'): float
    {
        $total = 0.0;
        foreach ($lines as $l) {
            $total += (float) ($l['weight'] ?? 0) * (int) ($l['quantity'] ?? 1);
        }

        return Weight::toKg($total, $unit);
    }

    public function quoteForOrder(ShippableOrder $order): ?float
    {
        [$weight] = $this->builder->itemsSummary($order);

        return $this->quote($weight, $order->jntReceiverPostcode());
    }
}
