<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $titulo ?? 'Notificaciones' }}</title>
    @include('panel.partials.dashboard-modern-styles')
    @include('panel.partials.hechos-saas-overrides')
</head>
<body class="dashboard-modern">
    @include('panel.partials.topbar-saas', [
        'activeRoute' => $activeRoute ?? request()->route()?->getName(),
        'pageTitle' => $titulo ?? 'Notificaciones',
        'pageSubtitle' => $subtitulo ?? 'Resumen operativo de mantenimientos.',
        'periodoConsulta' => $periodoConsulta ?? null,
        'showModuleContext' => false,
    ])

    <main class="wrap">
        <section class="module-header" aria-label="{{ $titulo ?? 'Notificaciones' }}">
            <div class="module-header-copy">
                <p class="module-header-kicker">Notificaciones</p>
                <h1 class="module-header-title">{{ $titulo ?? 'Notificaciones' }}</h1>
                <p class="module-header-subtitle">{{ $subtitulo ?? 'Consulta las alertas operativas de mantenimiento.' }}</p>
            </div>
        </section>

        <section class="card panel-card">
            <div class="panel-card-header">
                <div class="panel-title-block">
                    <p class="section-kicker">Listado</p>
                    <h2 class="panel-section-title">{{ $tituloListado ?? 'Notificaciones' }}</h2>
                    <p class="muted panel-section-subtitle">
                        {{ $descripcionListado ?? 'Alertas disponibles para el periodo actual.' }}
                    </p>
                    <p class="muted panel-section-subtitle">Total: {{ $total ?? count($items ?? []) }}</p>
                </div>
            </div>

            <div class="panel-table-shell">
                <table class="panel-table" aria-label="{{ $tituloListado ?? 'Notificaciones' }}">
                    <thead>
                        <tr>
                            @foreach (($columns ?? []) as $column)
                                <th>{{ $column['label'] ?? '' }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse (($items ?? []) as $item)
                            <tr>
                                @foreach (($columns ?? []) as $column)
                                    @php
                                        $key = (string) ($column['key'] ?? '');
                                        $badgeKey = (string) ($column['badge'] ?? '');
                                        $value = (string) ($item[$key] ?? '-');
                                        $badgeClass = $badgeKey !== '' ? (string) ($item[$badgeKey] ?? '') : '';
                                    @endphp
                                    <td>
                                        @if ($badgeClass !== '')
                                            <span class="estado-tabla {{ $badgeClass }}">{{ $value }}</span>
                                        @else
                                            <span class="texto-principal-tabla">{{ $value }}</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ max(1, count($columns ?? [])) }}" class="panel-table-empty">
                                    {{ $mensajeVacio ?? 'No hay notificaciones para mostrar.' }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>
