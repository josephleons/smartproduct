{{-- Public customer pages. No Vuexy, no JS, small inline CSS: built for cheap phones on 3G. --}}
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex">
  <meta name="theme-color" content="#0F6680">
  <title>@yield('title') · {{ config('variables.templateName') }}</title>
  <link rel="icon" type="image/svg+xml" href="{{ asset('assets/img/favicon/favicon.svg') }}">
  <style>
    :root { --p: #0F6680; --soft: #EAF4F7; --bg: #F7FAFB; --text: #1F2D33; --muted: #5c6b70; --line: #dde6e9; }
    * { box-sizing: border-box; }
    body { margin: 0; font: 15px/1.45 system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif; background: var(--bg); color: var(--text); }
    a { color: var(--p); }
    .wrap { max-width: 560px; margin: 0 auto; padding: 0 16px 24px; }
    .top { background: var(--p); color: #fff; padding: 14px 16px; }
    .top .wrap { padding: 0; display: flex; align-items: center; gap: 10px; }
    .top img { width: 36px; height: 36px; border-radius: 8px; background: #fff; object-fit: contain; }
    .top b { display: block; font-size: 16px; }
    .top small { opacity: .85; }
    .card { background: #fff; border: 1px solid var(--line); border-radius: 12px; padding: 16px; margin-top: 16px; }
    h1 { font-size: 21px; line-height: 1.25; margin: 8px 0 4px; }
    .price { font-size: 26px; font-weight: 700; color: var(--p); margin: 8px 0 0; }
    .badge { display: inline-block; font-size: 12px; font-weight: 600; padding: 3px 10px; border-radius: 999px; }
    .b-available { background: #dcf6e6; color: #1b7a43; } .b-low_stock { background: #fff0d9; color: #a35d00; }
    .b-sold { background: #fde2e2; color: #b42318; } .b-inactive { background: #eceff1; color: #546e7a; }
    .chips { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 10px; }
    .chip { background: var(--soft); color: var(--p); border-radius: 8px; padding: 4px 10px; font-size: 13px; font-weight: 600; }
    .specs { display: grid; grid-template-columns: 1fr 1fr; gap: 1px; background: var(--line); border-radius: 10px; overflow: hidden; margin: 0; }
    .specs div { background: #fff; padding: 10px 12px; }
    .specs dt { font-size: 12px; color: var(--muted); margin: 0; }
    .specs dd { margin: 2px 0 0; font-weight: 600; word-break: break-word; }
    h2 { font-size: 15px; margin: 0 0 10px; }
    .btns { display: grid; gap: 10px; margin-top: 16px; }
    .btn { display: block; text-align: center; padding: 13px; border-radius: 10px; font-weight: 700; text-decoration: none; font-size: 16px; }
    .btn-wa { background: #25D366; color: #fff; } .btn-call { background: var(--p); color: #fff; }
    .btn-ghost { background: #fff; color: var(--p); border: 1px solid var(--p); }
    .muted { color: var(--muted); font-size: 13px; }
    .foot { text-align: center; margin-top: 20px; }
    .list a { display: flex; justify-content: space-between; gap: 12px; padding: 12px 0; border-bottom: 1px solid var(--line); text-decoration: none; color: var(--text); }
    .list a:last-child { border-bottom: 0; }
  </style>
</head>
<body>
  @yield('body')
</body>
</html>
