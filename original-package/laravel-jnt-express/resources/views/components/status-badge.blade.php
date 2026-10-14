@props(['status'])
@php
    $st = $status instanceof \JntExpress\Enums\ShipmentStatus ? $status : \JntExpress\Enums\ShipmentStatus::tryFrom((string) $status);
@endphp
@if ($st)
    <span {{ $attributes }} style="display:inline-block;padding:2px 8px;border-radius:999px;font-size:12px;font-weight:600;color:#fff;background:{{ $st->color() }}">{{ $st->label() }}</span>
@endif
