<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Panel de mantenimientos</title>
    @include('panel.partials.dashboard-modern-styles')
    @include('panel.partials.hechos-saas-overrides')
</head>
<body class="dashboard-modern">
    @include('panel.partials.topbar-saas', [
        'activeRoute' => 'panel',
        'pageTitle' => 'Panel',
        'pageSubtitle' => 'Bienvenida al sistema.',
        'periodoConsulta' => $periodoConsulta ?? null,
        'showModuleContext' => false,
    ])

    @php
        $nombreUsuario = trim((string) data_get($usuario, 'nombre', 'Administrador TI'));
    @endphp

    <main class="wrap dashboard-welcome-wrap">
        <section class="dashboard-welcome" aria-label="Bienvenida">
            <div class="dashboard-welcome-copy">
                <p class="module-header-kicker">Panel principal</p>
                <h1>Nos alegra tenerte aqui, {{ mb_strtoupper($nombreUsuario, 'UTF-8') }}.</h1>
                <p>Gestiona usuarios, programaciones, mantenimientos realizados y reportes desde un solo lugar.</p>
            </div>

            <div class="dashboard-welcome-media">
                <img src="{{ asset('images/panel-bienvenida.svg') }}" alt="Equipo de soporte y mantenimiento SST">
            </div>
        </section>
    </main>
</body>
</html>
