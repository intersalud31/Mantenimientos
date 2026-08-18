<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ingreso al sistema</title>
    <style>
        :root {
            --bg: #07111f;
            --card: rgba(10, 18, 34, 0.85);
            --line: rgba(148, 163, 184, 0.18);
            --text: #e5eefc;
            --muted: #9fb0ca;
            --accent: #4f8cff;
            --accent-2: #7dd3fc;
            --danger: #ef4444;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Segoe UI", system-ui, -apple-system, BlinkMacSystemFont, sans-serif;
            min-height: 100vh;
            color: var(--text);
            background:
                radial-gradient(circle at top left, rgba(79, 140, 255, .25), transparent 30%),
                radial-gradient(circle at bottom right, rgba(125, 211, 252, .18), transparent 35%),
                linear-gradient(135deg, #020617, #0f172a 55%, #111827);
            display: grid;
            place-items: center;
            padding: 24px;
        }
        .shell {
            width: min(980px, 100%);
            display: grid;
            grid-template-columns: 1.1fr .9fr;
            overflow: hidden;
            border: 1px solid var(--line);
            border-radius: 28px;
            background: var(--card);
            backdrop-filter: blur(18px);
            box-shadow: 0 40px 90px rgba(0, 0, 0, .35);
        }
        .hero {
            padding: 48px;
            background:
                linear-gradient(160deg, rgba(79, 140, 255, .18), rgba(125, 211, 252, .06)),
                linear-gradient(180deg, rgba(255,255,255,.03), rgba(255,255,255,0));
            border-left: 1px solid var(--line);
        }
        .form {
            padding: 48px;
        }
        h1 {
            margin: 0 0 12px;
            font-size: 2.5rem;
            line-height: 1;
        }
        p { color: var(--muted); margin: 0 0 24px; }
        label { display:block; margin: 16px 0 8px; font-size: .92rem; color: #cbd5e1; }
        input {
            width: 100%;
            padding: 14px 16px;
            border-radius: 14px;
            border: 1px solid var(--line);
            background: rgba(15, 23, 42, .7);
            color: var(--text);
            outline: none;
        }
        input:focus { border-color: rgba(79, 140, 255, .7); box-shadow: 0 0 0 4px rgba(79, 140, 255, .15); }
        .btn {
            margin-top: 22px;
            width: 100%;
            border: 0;
            padding: 14px 18px;
            border-radius: 14px;
            color: white;
            background: linear-gradient(135deg, var(--accent), #2563eb);
            font-weight: 700;
            cursor: pointer;
        }
        .error {
            margin: 0 0 18px;
            padding: 12px 14px;
            border-radius: 14px;
            background: rgba(239, 68, 68, .12);
            border: 1px solid rgba(239, 68, 68, .3);
            color: #fecaca;
        }
        .badge {
            display: inline-flex;
            margin-bottom: 18px;
            padding: 8px 12px;
            border-radius: 999px;
            background: rgba(79, 140, 255, .12);
            color: var(--accent-2);
            border: 1px solid rgba(125, 211, 252, .2);
            font-size: .78rem;
            text-transform: uppercase;
            letter-spacing: .12em;
        }
        .hero h2 {
            margin: 0 0 12px;
            font-size: 1.9rem;
        }
        .hero ul {
            margin: 24px 0 0;
            padding-left: 18px;
            color: var(--muted);
        }
        @media (max-width: 900px) {
            .shell { grid-template-columns: 1fr; }
            .hero { border-left: 0; border-top: 1px solid var(--line); }
            .form, .hero { padding: 28px; }
        }
    </style>
</head>
<body>
    <main class="shell">
        <section class="form">
            <span class="badge">Acceso seguro</span>
            <h1>Bienvenido</h1>
            <p>Ingresa con tu cuenta del sistema de mantenimientos.</p>

            @if (session('error_login'))
                <div class="error">{{ session('error_login') }}</div>
            @endif

            <form method="POST" action="{{ route('login.store') }}">
                @csrf
                <label for="correo">Correo</label>
                <input id="correo" type="email" name="correo" value="{{ old('correo') }}" required autofocus>

                <label for="password">Contraseña</label>
                <input id="password" type="password" name="password" required>

                <button class="btn" type="submit">Iniciar sesión</button>
            </form>
        </section>

        <aside class="hero">
            <h2>Flujo de migración</h2>
            <p>Este primer paso conserva la tabla actual de usuarios y la sesión del sistema viejo.</p>
            <ul>
                <li>Login por correo y contraseña.</li>
                <li>Validación de usuario activo.</li>
                <li>Redirección al panel protegido.</li>
            </ul>
        </aside>
    </main>
</body>
</html>
