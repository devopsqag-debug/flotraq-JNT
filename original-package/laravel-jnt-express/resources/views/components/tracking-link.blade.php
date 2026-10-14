@props(['shipment' => null, 'order' => null])
@php
    $shipment = $shipment ?? $order?->jntShipment;
    $awb = $shipment?->awb;
@endphp
@if ($awb)
    <a {{ $attributes }} target="_blank" rel="noopener" href="{{ rtrim(config('jnt.tracking_url'), '/') . '/' . $awb }}">{{ $awb }}</a>
@endif
