<?php

namespace JntExpress\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use JntExpress\Enums\ShipmentStatus;

class JntShipment extends Model
{
    protected $table = 'jnt_shipments';
    protected $guarded = [];

    protected $casts = [
        'status' => ShipmentStatus::class,
        'insurance' => 'boolean',
        'cod' => 'decimal:2',
        'weight' => 'decimal:3',
        'request' => 'array',
        'response' => 'array',
        'cancelled_at' => 'datetime',
    ];

    public function shippable(): MorphTo
    {
        return $this->morphTo();
    }

    public function events(): HasMany
    {
        return $this->hasMany(JntShipmentEvent::class, 'jnt_shipment_id')->latest('id');
    }

    /** Shipments that have not been cancelled. */
    public function scopeActive(Builder $q): Builder
    {
        return $q->where('status', '!=', ShipmentStatus::Cancelled->value);
    }

    /** Search by waybill / J&T order number (the plugin made jtawb searchable on the orders list). */
    public function scopeSearch(Builder $q, ?string $term): Builder
    {
        $term = trim((string) $term);

        return $term === '' ? $q : $q->where(function ($w) use ($term) {
            $w->where('awb', 'like', "%{$term}%")
              ->orWhere('jnt_order_id', 'like', "%{$term}%")
              ->orWhere('shippable_id', $term);
        });
    }

    public function trackingUrl(): ?string
    {
        return $this->awb ? rtrim(config('jnt.tracking_url'), '/') . '/' . $this->awb : null;
    }

    public function addEvent(string $note, ?ShipmentStatus $status = null, ?string $scanCode = null): JntShipmentEvent
    {
        return $this->events()->create([
            'note' => $note,
            'status' => $status?->value,
            'scan_code' => $scanCode,
        ]);
    }
}
