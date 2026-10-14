@if ($errors->any())
    @foreach ($errors->all() as $error)
        <div class="msg error">{{ $error }}</div>
    @endforeach
@endif
@foreach (session('jnt.results', []) as $m)
    <div class="msg {{ $m['level'] ?? 'success' }}">{{ $m['text'] }}</div>
@endforeach
@if ($url = session('jnt.print_url'))
    <div class="msg success">Consignment note ready: <a href="{{ $url }}" target="_blank" rel="noopener">open thermal label</a></div>
    <script>window.open(@json($url), '_blank');</script>
@endif
