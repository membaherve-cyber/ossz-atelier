<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'OSSZ ATELIER')</title>
    <style>
        body { font-family: ui-sans-serif, system-ui, -apple-system, sans-serif; color: #26262c; margin: 32px; font-size: 13px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #e2e2e7; padding: 6px 10px; text-align: left; vertical-align: top; }
        th { background: #f4f4f6; text-transform: uppercase; font-size: 10px; letter-spacing: .05em; }
        .num { text-align: right; font-variant-numeric: tabular-nums; }
        .brand { font-weight: 700; letter-spacing: .08em; font-size: 15px; }
        .muted { color: #6b6b76; font-size: 11px; }
        h2 { margin: 4px 0 0; font-size: 16px; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="brand">OSSZ ATELIER</div>
    <div class="muted">{{ \App\Support\Trans::fill('frep.printedAt', ['date' => now()->locale(app()->getLocale())->translatedFormat('j M Y H:i')]) }}</div>
    <hr style="border: none; border-top: 2px solid #26262c; margin: 10px 0 16px;">
    @yield('content')
    <script>if (window.location.search.includes('print=1')) window.print()</script>
</body>
</html>
