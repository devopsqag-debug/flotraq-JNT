@extends('jnt::partials.layout')
@section('title', 'J&T Settings')
@section('content')
<form method="POST" action="{{ route(config('jnt.routes.name').'settings.update') }}">
    @csrf @method('PUT')

    <div class="card">
        <h2>J&amp;T Express</h2>
        <small>To start ordering to J&amp;T, fill in your VIP account details. Keys are provided by J&amp;T Express.</small>
        <label class="check"><input type="checkbox" name="enabled" value="1" @checked(old('enabled', $s['enabled'] ?? true))> Enable J&amp;T shipping method</label>
        <div class="grid">
            <div><label>Title</label><input name="title" value="{{ old('title', $s['title'] ?? 'J&T Express') }}"></div>
            <div><label>VIP Code</label><input name="vipcode" value="{{ old('vipcode', $s['vipcode'] ?? '') }}"></div>
            @foreach (['apikey' => 'API Key', 'signkey' => 'Sign Key', 'ratekey' => 'Shipping Rate Key'] as $k => $label)
                <div><label>{{ $label }}</label>
                    <input type="password" name="{{ $k }}" autocomplete="new-password" placeholder="{{ filled($s[$k] ?? null) ? '•••••••• (saved — leave blank to keep)' : '' }}"></div>
            @endforeach
        </div>
    </div>

    <div class="card">
        <h2>Sender</h2>
        <div class="grid">
            <div><label>Sender Name *</label><input name="name" required value="{{ old('name', $s['name'] ?? '') }}"></div>
            <div><label>Sender Phone Number *</label><input type="tel" name="phone" required value="{{ old('phone', $s['phone'] ?? '') }}"></div>
            <div><label>Address</label><input name="address" value="{{ old('address', $s['address'] ?? '') }}"></div>
            <div><label>Address 2</label><input name="address_2" value="{{ old('address_2', $s['address_2'] ?? '') }}"></div>
            <div><label>City</label><input name="city" value="{{ old('city', $s['city'] ?? '') }}"></div>
            <div><label>Postcode *</label><input name="postcode" required value="{{ old('postcode', $s['postcode'] ?? '') }}"></div>
        </div>
    </div>

    <div class="card">
        <h2>Shipping</h2>
        <div class="grid">
            <div><label>Service Type</label>
                <select name="service">
                    <option value="1" @selected(old('service', $s['service'] ?? '1') == '1')>PICKUP</option>
                    <option value="6" @selected(old('service', $s['service'] ?? '1') == '6')>DROPOFF</option>
                </select></div>
            <div><label>Markup (MYR)</label><input type="number" step="0.01" min="0" name="markup" value="{{ old('markup', $s['markup'] ?? 0) }}"><small>Added to every quoted rate.</small></div>
        </div>
        <label class="check"><input type="checkbox" name="insurance" value="1" @checked(old('insurance', $s['insurance'] ?? false))> Allow orders with insurance option</label>
    </div>

    <button type="submit">Save settings</button>
</form>
@endsection
