@php
    $formAction = $formAction ?? '';
    $queryPresets = $queryPresets ?? [];
    $submitLabel = $submitLabel ?? 'Aplicar';
    $resetLabel = $resetLabel ?? 'Limpiar';
@endphp

<form method="GET" action="{{ $formAction }}" class="filter-bar-saas">
    <input type="hidden" name="realizados_pagina" value="1">

    <div class="filter-bar-grid">
        <label class="filter-field filter-field--search">
            <span>Buscar usuario o activo</span>
            <input id="q_realizados" type="text" name="q_realizados" value="{{ $queryPresets['q_realizados'] ?? '' }}" placeholder="Buscar por nombre o código">
        </label>

        <label class="filter-field filter-field--narrow">
            <span>Año</span>
            <input type="number" name="anio" min="2000" max="{{ now()->year + 1 }}" value="{{ $queryPresets['anio'] ?? now()->year }}">
        </label>

        <label class="filter-field filter-field--narrow">
            <span>Semestre</span>
            <select name="semestre">
                <option value="1" {{ (int) ($queryPresets['semestre'] ?? ((int) now()->format('n') <= 6 ? 1 : 2)) === 1 ? 'selected' : '' }}>Semestre 1</option>
                <option value="2" {{ (int) ($queryPresets['semestre'] ?? ((int) now()->format('n') <= 6 ? 1 : 2)) === 2 ? 'selected' : '' }}>Semestre 2</option>
            </select>
        </label>

        <label class="filter-field filter-field--narrow">
            <span>Por página</span>
            <select id="realizados_por_pagina" name="realizados_por_pagina">
                @foreach ([10, 25, 50] as $opcion)
                    <option value="{{ $opcion }}" {{ (int) ($queryPresets['realizados_por_pagina'] ?? 10) === $opcion ? 'selected' : '' }}>{{ $opcion }}</option>
                @endforeach
            </select>
        </label>

        <div class="filter-actions">
            <button class="btn btn-primary" type="submit">{{ $submitLabel }}</button>
            <a class="btn btn-ghost" href="{{ $formAction }}">{{ $resetLabel }}</a>
        </div>
    </div>
</form>
