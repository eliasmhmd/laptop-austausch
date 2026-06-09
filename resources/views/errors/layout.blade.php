<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('code') &middot; Laptop-Austausch &middot; Kreis Groß-Gerau</title>
    {{-- Bewusst eigenständiges Styling (kein Vite-Build), damit Fehlerseiten
         auch dann sauber aussehen, wenn z. B. die Assets nicht verfügbar sind. --}}
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center;
            background: #f1f5f9; color: #1e293b;
            font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif;
            padding: 24px;
        }
        .card {
            background: #fff; border: 1px solid #e2e8f0; border-radius: 14px;
            box-shadow: 0 1px 3px rgba(0,0,0,.06); max-width: 30rem; width: 100%;
            padding: 40px 32px; text-align: center;
        }
        .code { font-size: 13px; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; color: #2563eb; margin: 0 0 8px; }
        h1 { font-size: 22px; margin: 0 0 10px; }
        p { margin: 0 0 22px; color: #64748b; font-size: 15px; line-height: 1.5; }
        a.btn {
            display: inline-block; background: #2563eb; color: #fff; text-decoration: none;
            padding: 11px 20px; border-radius: 8px; font-size: 14px; font-weight: 600;
        }
        a.btn:hover { background: #1d4ed8; }
        .brand { margin-top: 26px; font-size: 12px; color: #94a3b8; }
    </style>
</head>
<body>
    <div class="card">
        <p class="code">@yield('code')</p>
        <h1>@yield('title')</h1>
        <p>@yield('message')</p>
        <a class="btn" href="@yield('action_url', url('/'))">@yield('action_label', 'Zur Startseite')</a>
        <p class="brand">Laptop-Austausch &middot; Kreis Groß-Gerau</p>
    </div>
</body>
</html>
