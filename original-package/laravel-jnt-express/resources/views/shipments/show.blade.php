@extends('jnt::partials.layout')
@section('title', 'Shipment '.$shipment->awb)
@section('content')
@php $n = config('jnt.routes.name'); @endphp
<div class="card">
    <h2>Waybill <x-jnt::tracking-link :shipment="$shipment" /></h2>
    <p><x-jnt::status-badge :status="$shipment->status" />
       &nbsp; Order #{{ $shipment->shippable_id }} · J&amp;T ref {{ $shipment->jnt_order_id }}
       @if ($shipment->insurance) · insured @endif</p>
    <p>Weight {{ $shipment->weight }} kg · COD {{ $shipment->cod > 0 ? 'RM '.number_format($shipment->cod, 2) : '—' }}</p>
    @if ($shipment->awb && $shipment->status->value !== 'cancelled')
        <div class="row">
            <a class="btn" href="{{ route($n.'shipments.print', [$shipment, 'consignment']) }}">Consignment Note</a>
            <a class="btn alt" href="{{ route($n.'shipments.print', [$shipment, 'thermal']) }}">Thermal</a>
            <a class="btn alt" href="{{ route($n.'shipments.print', [$shipment, 'a4']) }}">A4</a>
        </div>
    @endif
</div>
<div class="card">
    <h3>History</h3>
    <table><tbody>
    @foreach ($shipment->events as $e)
        <tr><td><small>{{ $e->created_at->format('d M Y H:i') }}</small></td><td>{{ $e->note }}</td></tr>
    @endforeach
    </tbody></table>
</div>
@endsection
