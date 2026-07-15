<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Panel de mantenimientos</title>
    <style>
        body {
            margin: 0;
            font-family: Inter, Segoe UI, sans-serif;
            min-height: 100vh;
            color: #e5eefc;
            background: linear-gradient(135deg, #020617, #0f172a 55%, #111827);
        }
        .wrap {
            max-width: 1100px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        .card {
            border: 1px solid rgba(148, 163, 184, .18);
            border-radius: 24px;
            background: rgba(10, 18, 34, .82);
            padding: 28px;
            box-shadow: 0 30px 80px rgba(0, 0, 0, .28);
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            margin-top: 24px;
        }
        .stat {
            padding: 18px;
            border-radius: 18px;
            background: rgba(15, 23, 42, .8);
            border: 1px solid rgba(148, 163, 184, .12);
        }
        .muted { color: #9fb0ca; }
        .btn {
            display: inline-block;
            margin-top: 18px;
            padding: 12px 16px;
            border-radius: 12px;
            background: #2563eb;
            color: #fff;
            text-decoration: none;
            border: 0;
        }
        .btn-link {
            display: inline-block;
            margin-top: 18px;
            padding: 12px 16px;
            border-radius: 12px;
            background: #2563eb;
            color: #fff;
            text-decoration: none;
            border: 0;
        }
        .btn-link.secondary {
            background: rgba(37, 99, 235, .15);
            border: 1px solid rgba(37, 99, 235, .35);
        }
        .notice {
            margin: 18px 0 0;
            padding: 12px 14px;
            border-radius: 14px;
            background: rgba(59, 130, 246, .12);
            border: 1px solid rgba(59, 130, 246, .25);
            color: #bfdbfe;
        }
        .notice.error {
            background: rgba(239, 68, 68, .12);
            border-color: rgba(239, 68, 68, .25);
            color: #fecaca;
        }
        form { display: inline; }
        .topline {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            align-items: center;
            flex-wrap: wrap;
        }
    </style>
    @include('panel.partials.dashboard-modern-styles')
    @include('panel.partials.hechos-saas-overrides')
</head>
<body class="dashboard-modern">
    <main class="wrap">
        @include('panel.partials.topbar-saas', [
            'activeRoute' => 'panel',
            'pageTitle' => 'Panel',
            'pageSubtitle' => 'Visión general del sistema y accesos rápidos.',
            'periodoConsulta' => $periodoConsulta ?? null,
        ])
        <section class="card">
            <div class="topline">
                <div>
                    <h1>Panel de mantenimientos</h1>
                    <p class="muted">Este es el primer equivalente funcional del panel viejo.</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn" type="submit">Cerrar sesión</button>
                </form>
            </div>

            @if (session('panel_message'))
                <div class="notice">{{ session('panel_message') }}</div>
            @endif

            @if (session('panel_error'))
                <div class="notice error">{{ session('panel_error') }}</div>
            @endif

            <div class="grid">
                <div class="stat">
                    <strong>Usuario</strong>
                    <div class="muted">{{ data_get($usuario, 'nombre', 'Sin sesión') }}</div>
                </div>
                <div class="stat">
                    <strong>Correo</strong>
                    <div class="muted">{{ data_get($usuario, 'correo', '-') }}</div>
                </div>
                <div class="stat">
                    <strong>Rol</strong>
                    <div class="muted">{{ data_get($usuario, 'rol', '-') }}</div>
                </div>
                <div class="stat">
                    <strong>Siguiente paso</strong>
                    <div class="muted">Migrar "Próximos", "Programados" y usuarios.</div>
                </div>
            </div>

        </section>
    </main>
</body>
</html>
