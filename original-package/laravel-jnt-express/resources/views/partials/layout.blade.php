<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'J&T Express')</title>
    <style>
        :root{--bg:#f6f7f9;--card:#fff;--text:#1f2937;--muted:#6b7280;--line:#e5e7eb;--brand:#d71920;--ok:#15803d;--warn:#b45309;--err:#b91c1c}
        @media (prefers-color-scheme:dark){:root{--bg:#111827;--card:#1f2937;--text:#f3f4f6;--muted:#9ca3af;--line:#374151}}
        *{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--text);font:15px/1.5 system-ui,sans-serif}
        .wrap{max-width:1100px;margin:0 auto;padding:24px 16px}
        nav{display:flex;gap:16px;align-items:center;margin-bottom:20px}nav a{color:var(--text);text-decoration:none;font-weight:600}
        nav .brand{color:var(--brand);font-size:18px;margin-right:8px}
        .card{background:var(--card);border:1px solid var(--line);border-radius:10px;padding:18px;margin-bottom:18px}
        table{width:100%;border-collapse:collapse}th,td{text-align:left;padding:8px 10px;border-bottom:1px solid var(--line);vertical-align:top}th{color:var(--muted);font-size:12px;text-transform:uppercase}
        input,select,textarea{width:100%;padding:8px 10px;border:1px solid var(--line);border-radius:6px;background:var(--bg);color:var(--text);font:inherit}
        label{display:block;font-weight:600;margin:12px 0 4px}small{color:var(--muted)}
        .grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:0 16px}
        button,.btn{background:var(--brand);color:#fff;border:0;border-radius:6px;padding:8px 14px;font:inherit;font-weight:600;cursor:pointer;text-decoration:none;display:inline-block}
        .btn.alt,button.alt{background:transparent;color:var(--text);border:1px solid var(--line)}
        .row{display:flex;gap:8px;flex-wrap:wrap;align-items:center}.row>*{width:auto}
        .msg{padding:10px 14px;border-radius:8px;margin-bottom:8px;border:1px solid var(--line);border-left-width:4px}
        .msg.success{border-left-color:var(--ok)}.msg.warning{border-left-color:var(--warn)}.msg.error{border-left-color:var(--err)}
        .check{display:flex;gap:8px;align-items:center;font-weight:600}.check input{width:auto}
    </style>
</head>
<body>
<div class="wrap">
    <nav>
        <span class="brand">J&amp;T Express</span>
        <a href="{{ route(config('jnt.routes.name').'shipments.index') }}">Shipments</a>
        <a href="{{ route(config('jnt.routes.name').'settings.edit') }}">Settings</a>
    </nav>
    @include('jnt::partials.messages')
    @yield('content')
</div>
</body>
</html>
