<?php

namespace JntExpress\Concerns;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use JntExpress\Models\JntShipment;

/**
 * Add to your Order model and implement JntExpress\Contracts\ShippableOrder:
 *
 *   class Order extends Model implements ShippableOrder { use HasJntShipping; }
 *
 * Column names come from config('jnt.order_map'); override any jnt*() method
 * for custom logic.
 */
trait HasJntShipping
{
    public function jntShipments(): MorphMany
    {
        return $this->morphMany(JntShipment::class, 'shippable');
    }

    /** The current (non-cancelled) shipment. */
    public function jntShipment(): MorphOne
    {
        return $this->morphOne(JntShipment::class, 'shippable')
            ->where('status', '!=', 'cancelled')
            ->latestOfMany();
    }

    public function getJtawbAttribute(): ?string
    {
        return $this->jntShipment?->awb;
    }

    public function jntOrderKey(): int|string
    {
        return $this->getKey();
    }

    protected function jntMap(string $path, mixed $default = null): mixed
    {
        return config('jnt.order_map.' . $path, $default);
    }

    protected function jntUsesShipping(): bool
    {
        $col = $this->jntMap('shipping.address_1');

        return $col && filled(data_get($this, $col));
    }

    protected function jntField(string $field): string
    {
        $group = $this->jntUsesShipping() ? 'shipping' : 'billing';
        $col = $this->jntMap("{$group}.{$field}");

        return (string) ($col ? data_get($this, $col, '') : '');
    }

    public function jntReceiverName(): string
    {
        return trim($this->jntField('name'));
    }

    public function jntReceiverPhone(): string
    {
        // shipping phone, falling back to billing phone (as the WooCommerce plugin did)
        foreach (['shipping', 'billing'] as $group) {
            $col = $this->jntMap("{$group}.phone");
            $v = $col ? data_get($this, $col) : null;
            if (filled($v)) {
                return (string) $v;
            }
        }

        return '';
    }

    public function jntReceiverAddress(): string
    {
        return implode(' ', array_filter(array_map(
            fn ($f) => $this->jntField($f),
            ['address_1', 'address_2', 'city', 'postcode']
        ), fn ($v) => $v !== ''));
    }

    public function jntReceiverPostcode(): string
    {
        return $this->jntField('postcode');
    }

    public function jntItems(): array
    {
        $map = $this->jntMap('items', []);
        $rel = $map['relation'] ?? 'items';
        $items = [];

        foreach (data_get($this, $rel) ?? [] as $item) {
            $items[] = [
                'name' => (string) data_get($item, $map['name'] ?? 'name', ''),
                'quantity' => (float) data_get($item, $map['quantity'] ?? 'quantity', 1),
                'weight' => data_get($item, $map['weight'] ?? 'weight'),
                'virtual' => (bool) data_get($item, $map['virtual'] ?? 'is_virtual', false),
            ];
        }

        return $items;
    }

    public function jntSubtotal(): float
    {
        return (float) data_get($this, $this->jntMap('subtotal', 'subtotal'), 0);
    }

    public function jntTotal(): float
    {
        return (float) data_get($this, $this->jntMap('total', 'total'), 0);
    }

    public function jntIsCashOnDelivery(): bool
    {
        $method = data_get($this, $this->jntMap('payment_method', 'payment_method'));

        return $method !== null && in_array($method, (array) $this->jntMap('cod_payment_methods', ['cod']), true);
    }

    public function jntCustomerNote(): ?string
    {
        return data_get($this, $this->jntMap('customer_note', 'customer_note'));
    }

    public function jntCustomerEmail(): ?string
    {
        return data_get($this, $this->jntMap('email', 'email'));
    }

    public function jntWeightUnit(): string
    {
        return (string) $this->jntMap('weight_unit', 'kg');
    }
}
