<?php

namespace JntExpress;

use Illuminate\Support\Collection;
use JntExpress\Contracts\ShippableOrder;
use JntExpress\Services\RateService;
use JntExpress\Services\ShipmentService;
use JntExpress\Services\WebhookProcessor;
use JntExpress\Support\Settings;

class JntManager
{
    public function __construct(
        protected ShipmentService $shipments,
        protected RateService $rates,
        protected WebhookProcessor $webhook,
        protected Settings $settings,
    ) {}

    public function create(iterable $orders, bool $insurance = false): Collection
    {
        return $this->shipments->create($orders, $insurance);
    }

    public function cancel(iterable $shipments): Collection
    {
        return $this->shipments->cancel($shipments);
    }

    public function print(iterable $shipments, string $format = 'consignment'): string
    {
        return $this->shipments->print($shipments, $format);
    }

    public function quote(float $weightKg, string $postcode, string $country = 'MY'): ?float
    {
        return $this->rates->quote($weightKg, $postcode, $country);
    }

    public function quoteForOrder(ShippableOrder $order): ?float
    {
        return $this->rates->quoteForOrder($order);
    }

    public function handleWebhook(string $awb, int|string|null $scanCode): array
    {
        return $this->webhook->process($awb, $scanCode);
    }

    public function settings(): Settings
    {
        return $this->settings;
    }
}
