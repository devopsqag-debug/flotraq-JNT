{{-- Drop into your orders list. Each order row needs <input type="checkbox" name="orders[]" value="ID" form="jnt-bulk">.
     Usage: <x-jnt::order-actions /> --}}
@props(['insurance' => null])
@php
    $n = config('jnt.routes.name');
    $insurance = $insurance ?? app(\JntExpress\Support\Settings::class)->insuranceAllowed();
@endphp
<form id="jnt-bulk" method="POST" action="{{ route($n.'shipments.store') }}" {{ $attributes->merge(['class' => 'jnt-order-actions']) }}>
    @csrf
    @if ($insurance)
        <label><input type="checkbox" name="insurance" value="1"> With insurance</label>
    @endif
    <button type="submit">Order to J&amp;T</button>
</form>
