@php
    $detalleCallback = $detalleCallback ?? null;
    $evidenciaUrl = $evidenciaUrl ?? '';
    $descargaUrl = $descargaUrl ?? '';
    $evidenciaDisponible = (bool) ($evidenciaDisponible ?? false);
    $evidenciaNombre = $evidenciaNombre ?? 'Sin constancia adjunta';
@endphp

<details class="action-menu">
    <summary class="action-menu-trigger" aria-label="Abrir acciones">
        <span></span>
        <span></span>
        <span></span>
    </summary>

    <div class="action-menu-panel">
        @if (!empty($detalleCallback))
            <button type="button" class="action-menu-item" {{ $detalleCallback }}>
                Ver detalle
            </button>
        @endif

        @if ($evidenciaDisponible && $evidenciaUrl !== '')
            <a class="action-menu-item action-menu-item--soft" href="{{ $evidenciaUrl }}" target="_blank" rel="noopener noreferrer">Ver evidencia PDF</a>
            <a class="action-menu-item action-menu-item--soft" href="{{ $descargaUrl }}" download="{{ $evidenciaNombre }}">Descargar evidencia</a>
        @endif
    </div>
</details>
