@extends('jnt::partials.layout')
@section('title', 'J&T Shipments')
@section('content')
@php $n = config('jnt.routes.name'); @endphp

<div class="card">
    <h3>Order to J&amp;T</h3>
    <form method="POST" action="{{ route($n.'shipments.store') }}" class="row">
        @csrf
        <input name="orders" placeholder="Order IDs, comma separated" required style="min-width:260px">
        @if ($insuranceAllowed)
            <label class="check" style="margin:0"><input type="checkbox" name="insurance" value="1"> With insurance</label>
        @endif
        <button type="submit">Create consignment notes</button>
    </form>
</div>

<div class="card">
    <form method="GET" class="row" style="margin-bottom:12px">
        <input name="q" value="{{ request('q') }}" placeholder="Search waybill / order no." style="min-width:240px">
        <select name="status">
            <option value="">All statuses</option>
            @foreach ($statuses as $st)
                <option value="{{ $st->value }}" @selected(request('status') === $st->value)>{{ $st->label() }}</option>
            @endforeach
        </select>
        <button class="alt" type="submit">Filter</button>
    </form>

    <form method="POST" id="bulk" action="{{ route($n.'shipments.print.bulk') }}" target="_blank">
        @csrf
        <div class="row" style="margin-bottom:12px">
            <select name="format" aria-label="Print format">
                <option value="consignment">Consignment Note</option>
                <option value="thermal">Consignment Note (Thermal)</option>
                <option value="a4">Consignment Note (A4)</option>
            </select>
            <button type="submit">Print selected</button>
            <button type="submit" class="alt" formaction="{{ route($n.'shipments.cancel') }}" formtarget="_self"
                    onclick="return confirm('Cancel the selected J&T orders?')">Cancel J&amp;T order</button>
        </div>

        <table>
            <thead><tr>
                <th><input type="checkbox" aria-label="Select all" onclick="document.querySelectorAll('.pick').forEach(c=>c.checked=this.checked)"></th>
                <th>Order</th><th>J&amp;T Waybill</th><th>Status</th><th>COD</th><th>Created</th><th></th>
            </tr></thead>
            <tbody>
            @forelse ($shipments as $s)
                <tr>
                    <td><input class="pick" type="checkbox" name="ids[]" value="{{ $s->id }}"></td>
                    <td>#{{ $s->shippable_id }}</td>
                    <td><x-jnt::tracking-link :shipment="$s" /></td>
                    <td><x-jnt::status-badge :status="$s->status" /></td>
                    <td>{{ $s->cod > 0 ? 'RM '.number_format($s->cod, 2) : '—' }}</td>
                    <td><small>{{ $s->created_at->format('d M Y H:i') }}</small></td>
                    <td><a href="{{ route($n.'shipments.show', $s) }}">Details</a></td>
                </tr>
            @empty
                <tr><td colspan="7"><small>No shipments yet.</small></td></tr>
            @endforelse
            </tbody>
        </table>
    </form>

    <div style="margin-top:12px">{{ $shipments->links() }}</div>
</div>
@endsection
