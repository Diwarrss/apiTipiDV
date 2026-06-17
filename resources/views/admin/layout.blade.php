<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Admin') — TipiDV</title>
    <style>
        :root { --orange: #f26c20; --green: #247a2b; --bg: #f9fafb; --text: #111827; --muted: #6b7280; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: system-ui, Segoe UI, sans-serif; background: var(--bg); color: var(--text); }
        a { color: var(--orange); }
        .top { background: #fff; border-bottom: 1px solid #e5e7eb; padding: 12px 20px; display: flex; justify-content: space-between; align-items: center; }
        .top strong { color: var(--orange); }
        .wrap { max-width: 1100px; margin: 0 auto; padding: 24px 16px; }
        .card { background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; padding: 20px; margin-bottom: 16px; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 12px; margin-bottom: 20px; }
        .stat { background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; padding: 16px; text-align: center; }
        .stat b { display: block; font-size: 1.5rem; color: var(--green); }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th, td { text-align: left; padding: 10px 8px; border-bottom: 1px solid #f3f4f6; }
        th { color: var(--muted); font-weight: 600; font-size: 12px; text-transform: uppercase; }
        .btn { display: inline-block; background: var(--orange); color: #fff; border: 0; border-radius: 8px; padding: 8px 14px; font-weight: 600; cursor: pointer; text-decoration: none; font-size: 14px; }
        .btn--muted { background: #e5e7eb; color: var(--text); }
        .btn--danger { background: #dc2626; }
        input, select { width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; }
        label { display: block; font-size: 13px; color: var(--muted); margin-bottom: 4px; }
        .field { margin-bottom: 14px; }
        .flash { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; padding: 10px 14px; border-radius: 8px; margin-bottom: 16px; }
        .error { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 10px 14px; border-radius: 8px; margin-bottom: 16px; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 12px; font-weight: 600; }
        .badge--ok { background: #d1fae5; color: #065f46; }
        .badge--no { background: #fee2e2; color: #991b1b; }
        code { background: #f3f4f6; padding: 2px 6px; border-radius: 4px; }
        .row { display: flex; gap: 12px; flex-wrap: wrap; align-items: end; }
        .row .field { flex: 1; min-width: 120px; }
    </style>
</head>
<body>
    @hasSection('nav')
        <header class="top">
            <div><strong>TipiDV</strong> · Super admin</div>
            <div>
                @yield('nav')
                <form action="{{ route('admin.logout') }}" method="post" style="display:inline;margin-left:8px;">
                    @csrf
                    <button type="submit" class="btn btn--muted">Salir</button>
                </form>
            </div>
        </header>
    @endif
    <main class="wrap">
        @if (session('status'))
            <div class="flash">{{ session('status') }}</div>
        @endif
        @yield('content')
    </main>
</body>
</html>
