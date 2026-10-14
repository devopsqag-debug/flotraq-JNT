<!doctype html>
<html><body style="font-family:Arial,sans-serif;color:#222">
    <h2>{{ $heading }}</h2>
    <p>{{ $line }}</p>
    @if ($awb)
        <p>J&amp;T Tracking Number: <a href="{{ $trackingUrl }}">{{ $awb }}</a></p>
    @endif
</body></html>
