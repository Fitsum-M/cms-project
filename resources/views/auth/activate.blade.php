<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Activate account — {{ config('app.name') }}</title>
    <style>
        :root { color-scheme: light dark; }
        body { font-family: ui-sans-serif, system-ui, sans-serif; margin: 0; min-height: 100vh; display: grid; place-items: center; background: #0f172a; color: #e2e8f0; }
        .card { width: min(420px, 92vw); background: #1e293b; border: 1px solid #334155; border-radius: 12px; padding: 2rem; box-shadow: 0 20px 40px rgba(0,0,0,.35); }
        h1 { font-size: 1.35rem; margin: 0 0 .35rem; }
        p { margin: 0 0 1.25rem; color: #94a3b8; font-size: .95rem; }
        label { display: block; font-size: .85rem; margin-bottom: .35rem; color: #cbd5e1; }
        input { width: 100%; box-sizing: border-box; border-radius: 8px; border: 1px solid #475569; background: #0f172a; color: #f8fafc; padding: .7rem .8rem; margin-bottom: 1rem; }
        button { width: 100%; border: 0; border-radius: 8px; padding: .75rem 1rem; background: #2563eb; color: white; font-weight: 600; cursor: pointer; }
        button:hover { background: #1d4ed8; }
        .errors { background: #7f1d1d; color: #fecaca; border-radius: 8px; padding: .75rem 1rem; margin-bottom: 1rem; font-size: .9rem; }
        .errors ul { margin: 0; padding-left: 1.1rem; }
        .meta { font-size: .8rem; color: #64748b; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Activate your account</h1>
        <p>Set a password to finish joining {{ config('app.name') }}.</p>
        <div class="meta">{{ $name }} &lt;{{ $email }}&gt;</div>

        @if ($errors->any())
            <div class="errors">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ url('/activate/'.$token) }}">
            @csrf
            <label for="password">Password</label>
            <input id="password" type="password" name="password" required autocomplete="new-password" autofocus>

            <label for="password_confirmation">Confirm password</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">

            <button type="submit">Activate account</button>
        </form>
    </div>
</body>
</html>
