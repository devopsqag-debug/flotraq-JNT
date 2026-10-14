<?php

namespace JntExpress\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JntShipmentEvent extends Model
{
    protected $table = 'jnt_shipment_events';
    protected $guarded = [];

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(JntShipment::class, 'jnt_shipment_id');
    }
}
