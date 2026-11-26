<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'ClipForge')</title>
    <style>
        :root { --bg:#0f1115; --card:#181b22; --muted:#8b93a7; --text:#e8ebf2; --accent:#ff7a1a; --err:#ff5c5c; }
        * { box-sizing: border-box; }
        body { margin:0; font:15px/1.5 system-ui, sans-serif; background:var(--bg); color:var(--text); }
        a { color:inherit; text-decoration:none; }
        header { display:flex; justify-content:space-between; align-items:center; padding:16px 24px; border-bottom:1px solid #222733; }
        .logo { font-weight:800; letter-spacing:.5px; } .logo span { color:var(--accent); }
        .btn { background:var(--accent); color:#111; border:0; padding:8px 16px; border-radius:8px; font-weight:600; cursor:pointer; }
        .btn:disabled { opacity:.5; cursor:wait; }
        main { max-width:1200px; margin:0 auto; padding:24px; }
        .grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(260px, 1fr)); gap:20px; }
        .card { background:var(--card); border-radius:10px; overflow:hidden; }
        .thumb { position:relative; aspect-ratio:16/9; background:#000; }
        .thumb img, .thumb video { position:absolute; inset:0; width:100%; height:100%; object-fit:cover; }
        .thumb video { opacity:0; transition:opacity .15s; }
        .thumb.playing video { opacity:1; }
        .dur { position:absolute; right:6px; bottom:6px; background:rgba(0,0,0,.75); padding:1px 6px; border-radius:4px; font-size:12px; }
        .meta { padding:10px 12px; } .meta small { color:var(--muted); }
        form.upload { max-width:520px; display:grid; gap:12px; }
        input[type=text], input[type=file] { width:100%; padding:10px; background:var(--card); border:1px solid #2a2f3c; color:var(--text); border-radius:8px; }
        .bar { height:8px; background:#2a2f3c; border-radius:4px; overflow:hidden; } .bar i { display:block; height:100%; width:0; background:var(--accent); }
        .error { color:var(--err); } .muted { color:var(--muted); }
        .pagination { display:flex; gap:8px; margin-top:24px; }
    </style>
</head>
<body>
<header>
    <a href="{{ route('videos.index') }}" class="logo">Clip<span>Forge</span></a>
    <a href="{{ route('videos.create') }}" class="btn">Upload</a>
</header>
<main>
    @yield('content')
</main>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"
        integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
<script>
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
</script>
@stack('scripts')
</body>
</html>
