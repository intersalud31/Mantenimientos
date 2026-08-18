<style>
    body.dashboard-modern {
        font-family: var(--panel-font-base);
        line-height: 1.5;
        font-weight: 400;
        background:
            radial-gradient(circle at top left, rgba(37, 99, 235, .04), transparent 30%),
            radial-gradient(circle at top right, rgba(16, 185, 129, .03), transparent 24%),
            #eef2f7;
        color: #0f172a;
    }

    body.dashboard-modern .wrap {
        max-width: 1380px;
        padding: 22px 20px 48px;
    }

    body.dashboard-modern .card {
        background: rgba(255, 255, 255, .98);
        border: 1px solid #dfe7f2;
        border-radius: 20px;
        box-shadow: 0 18px 42px rgba(15, 23, 42, .055);
        padding: 22px;
        margin-bottom: 18px;
    }

    body.dashboard-modern .grid,
    body.dashboard-modern .row,
    body.dashboard-modern .stack {
        gap: 14px;
    }

    body.dashboard-modern .grid {
        display: grid;
    }

    body.dashboard-modern .grid-form,
    body.dashboard-modern .row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(0, 1fr));
        align-items: end;
    }

    body.dashboard-modern .grid-form {
        gap: 12px;
    }

    body.dashboard-modern .row {
        gap: 12px;
    }

    body.dashboard-modern .panel,
    body.dashboard-modern .stat {
        padding: 16px;
        border-radius: 18px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        color: #0f172a;
    }

    body.dashboard-modern .page-header-compact {
        display: grid;
        gap: 5px;
        margin-bottom: 14px;
    }

    body.dashboard-modern .page-header-kicker {
        color: #64748b;
        font-size: .72rem;
        font-weight: 800;
        letter-spacing: .14em;
        text-transform: uppercase;
    }

    body.dashboard-modern .page-header-title {
        margin: 0;
        font-size: clamp(1.45rem, 1.8vw, 1.9rem);
        line-height: 1.1;
        color: #0f172a;
    }

    body.dashboard-modern .page-header-subtitle {
        margin: 0;
        color: #64748b;
        max-width: 760px;
    }

    body.dashboard-modern .page-header-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 4px;
    }

    body.dashboard-modern .chip,
    body.dashboard-modern .status-badge,
    body.dashboard-modern .badge-area {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border-radius: 999px;
        font-weight: 700;
        line-height: 1;
    }

    body.dashboard-modern .chip {
        padding: 8px 11px;
        background: #f8fafc;
        border: 1px solid #dfe7f2;
        color: #475569;
        font-size: .78rem;
    }

    body.dashboard-modern .filter-bar-saas {
        padding: 16px;
        border-radius: 20px;
        background: #fff;
        border: 1px solid #dfe7f2;
        box-shadow: 0 12px 32px rgba(15, 23, 42, .04);
        margin-top: 16px;
    }

    body.dashboard-modern .filter-bar-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.2fr) repeat(3, minmax(144px, 170px)) auto;
        gap: 12px;
        align-items: end;
    }

    body.dashboard-modern .filter-field span {
        display: block;
        margin-bottom: 6px;
        color: #334155;
        font-weight: 700;
        font-size: .9rem;
    }

    body.dashboard-modern .filter-field input,
    body.dashboard-modern .filter-field select,
    body.dashboard-modern input,
    body.dashboard-modern select,
    body.dashboard-modern textarea {
        width: 100%;
        box-sizing: border-box;
        padding: 11px 14px;
        border-radius: 14px;
        border: 1px solid #dfe7f2;
        background: #fff;
        color: #0f172a;
        box-shadow: inset 0 1px 0 rgba(255,255,255,.7);
    }

    body.dashboard-modern textarea {
        min-height: 92px;
        resize: vertical;
    }

    body.dashboard-modern label {
        color: #334155;
        font-weight: 700;
    }

    body.dashboard-modern .filter-actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        justify-content: flex-end;
    }

    body.dashboard-modern .btn {
        padding: 10px 14px;
        border-radius: 12px;
        border: 1px solid transparent;
        background: #0f172a;
        color: #fff;
        cursor: pointer;
        text-decoration: none;
        font-weight: 700;
    }

    body.dashboard-modern .btn-primary {
        background: #2563eb;
        box-shadow: 0 10px 18px rgba(37, 99, 235, .16);
    }

    body.dashboard-modern .btn-ghost {
        background: #fff;
        color: #334155;
        border-color: #dfe7f2;
    }

    body.dashboard-modern .btn.secondary,
    body.dashboard-modern .btn-link.secondary,
    body.dashboard-modern .btn-muted {
        background: #fff;
        color: #2563eb;
        border: 1px solid #dfe7f2;
        box-shadow: none;
    }

    body.dashboard-modern .btn-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    body.dashboard-modern .section-summary {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 12px;
        margin-top: 12px;
    }

    body.dashboard-modern .summary-pill {
        padding: 14px 16px;
        border-radius: 18px;
        background: #f8fafc;
        border: 1px solid #dfe7f2;
    }

    body.dashboard-modern .summary-pill span {
        display: block;
        color: #64748b;
        font-size: .78rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .08em;
        margin-bottom: 4px;
    }

    body.dashboard-modern .summary-pill strong {
        display: block;
        color: #0f172a;
        font-size: 1rem;
    }

    body.dashboard-modern .tabla-shell-premium {
        padding: 0;
        overflow: hidden;
        border-radius: 20px;
        border: 1px solid #dfe7f2;
    }

    body.dashboard-modern .tabla-premium {
        margin-top: 0;
    }

    body.dashboard-modern .tabla-premium thead th {
        background: #f8fafc;
        color: #475569;
        font-size: .76rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .08em;
        padding: 14px 12px;
        border-bottom: 1px solid #e2e8f0;
    }

    body.dashboard-modern .tabla-premium tbody td {
        padding: 15px 12px;
        border-bottom: 1px solid #eef2f7;
    }

    body.dashboard-modern .tabla-premium tbody tr:hover {
        background: #f8fbff;
    }

    body.dashboard-modern table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 0;
    }

    body.dashboard-modern th,
    body.dashboard-modern td {
        text-align: left;
        padding: 14px 12px;
        border-bottom: 1px solid #eef2f7;
        vertical-align: top;
    }

    body.dashboard-modern th {
        color: #64748b;
        font-size: .76rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .08em;
    }

    body.dashboard-modern .texto-principal-tabla {
        display: block;
        color: #0f172a;
        font-weight: 600;
    }

    body.dashboard-modern .subline {
        color: #64748b;
        font-size: .85rem;
        margin-top: 2px;
    }

    body.dashboard-modern .codigo-activo,
    body.dashboard-modern .fecha-destacada {
        color: #0f172a;
        font-weight: 600;
    }

    body.dashboard-modern .fecha-destacada {
        display: inline-flex;
        align-items: center;
        padding: 6px 10px;
        border-radius: 999px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
    }

    body.dashboard-modern .estado-tabla,
    body.dashboard-modern .status-badge {
        padding: 5px 10px;
        font-size: .76rem;
    }

    body.dashboard-modern .status-badge--realizado,
    body.dashboard-modern .estado-tabla-exito {
        background: rgba(16, 185, 129, .12);
        border: 1px solid rgba(16, 185, 129, .18);
        color: #047857;
    }

    body.dashboard-modern .status-badge--programado,
    body.dashboard-modern .estado-tabla-info {
        background: rgba(59, 130, 246, .12);
        border: 1px solid rgba(59, 130, 246, .18);
        color: #1d4ed8;
    }

    body.dashboard-modern .status-badge--pendiente,
    body.dashboard-modern .estado-tabla-alerta {
        background: rgba(245, 158, 11, .12);
        border: 1px solid rgba(245, 158, 11, .18);
        color: #b45309;
    }

    body.dashboard-modern .status-badge--cancelado,
    body.dashboard-modern .estado-tabla-neutral {
        background: rgba(148, 163, 184, .14);
        border: 1px solid rgba(148, 163, 184, .22);
        color: #475569;
    }

    body.dashboard-modern .action-menu-trigger,
    body.dashboard-modern .menu-acciones-disparador {
        width: 40px;
        height: 40px;
        border-radius: 12px;
        border: 1px solid #dbe4f0;
        background: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 4px;
        cursor: pointer;
    }

    body.dashboard-modern .action-menu-trigger span,
    body.dashboard-modern .menu-acciones-disparador span {
        width: 4px;
        height: 4px;
        border-radius: 999px;
        background: #475569;
    }

    body.dashboard-modern .action-menu-panel,
    body.dashboard-modern .menu-acciones-dropdown {
        min-width: 220px;
        border-radius: 16px;
        border: 1px solid #e2e8f0;
        background: #fff;
        box-shadow: 0 20px 50px rgba(15, 23, 42, .12);
        padding: 10px;
        gap: 8px;
    }

    body.dashboard-modern .action-menu-item,
    body.dashboard-modern .menu-acciones-opcion {
        display: block;
        width: 100%;
        text-align: left;
        padding: 10px 12px;
        border-radius: 12px;
        border: 0;
        background: #f8fafc;
        color: #0f172a;
        text-decoration: none;
        cursor: pointer;
    }

    body.dashboard-modern .action-menu-item:hover,
    body.dashboard-modern .menu-acciones-opcion:hover {
        background: #eff6ff;
    }

    body.dashboard-modern .pager {
        display: flex;
        gap: 8px;
        align-items: center;
        flex-wrap: wrap;
        margin-top: 16px;
    }

    body.dashboard-modern .topline {
        align-items: flex-start;
    }

    body.dashboard-modern .muted {
        color: #64748b;
    }

    body.dashboard-modern .mini {
        color: #64748b;
        font-size: .9rem;
    }

    body.dashboard-modern .actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    body.dashboard-modern .modal-panel-dialogo-detalle {
        border-radius: 24px;
        border: 1px solid #e2e8f0;
        background: #fff;
        color: #0f172a;
    }

    body.dashboard-modern .detalle-realizado-modal {
        padding: 28px;
    }

    body.dashboard-modern .detalle-realizado-modal-dialogo {
        width: min(100%, 840px);
        max-height: calc(100vh - 56px);
        padding: 34px 38px 30px;
        border-radius: 26px;
        border: 1px solid #dbe5f1;
        box-shadow: 0 32px 84px rgba(15, 23, 42, .24);
        display: grid;
        gap: 22px;
        font-family: var(--panel-font-base);
        text-rendering: optimizeLegibility;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
    }

    body.dashboard-modern .detalle-realizado-modal-dialogo,
    body.dashboard-modern .detalle-realizado-modal-dialogo *,
    body.dashboard-modern .detalle-realizado-modal-dialogo input,
    body.dashboard-modern .detalle-realizado-modal-dialogo textarea,
    body.dashboard-modern .detalle-realizado-modal-dialogo button,
    body.dashboard-modern .detalle-realizado-modal-dialogo a {
        font-family: var(--panel-font-base) !important;
    }

    body.dashboard-modern .detalle-realizado-modal-dialogo .modal-panel-cabecera {
        align-items: flex-start;
        gap: 18px;
        margin-bottom: 0;
    }

    body.dashboard-modern .detalle-realizado-modal-heading {
        display: grid;
        grid-template-columns: 42px minmax(0, 1fr);
        column-gap: 14px;
        row-gap: 4px;
        align-items: start;
    }

    body.dashboard-modern .detalle-realizado-modal-icon {
        grid-row: 1 / span 3;
        width: 42px;
        height: 42px;
        border-radius: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(180deg, #3b82f6 0%, #2563eb 100%);
        color: #fff;
        box-shadow: 0 10px 24px rgba(37, 99, 235, .24);
    }

    body.dashboard-modern .detalle-realizado-modal-icon svg,
    body.dashboard-modern .detalle-realizado-modal-close svg {
        width: 18px;
        height: 18px;
        display: block;
    }

    body.dashboard-modern .detalle-realizado-modal-dialogo .modal-panel-kicker {
        margin: 2px 0 0;
        font-size: .7rem;
        letter-spacing: .12em;
        font-weight: 800;
    }

    body.dashboard-modern .detalle-realizado-modal-dialogo .modal-panel-cabecera h4 {
        margin: 0;
        font-size: 1.12rem;
        font-weight: 700;
        line-height: 1.25;
    }

    body.dashboard-modern .detalle-realizado-modal-dialogo .modal-panel-texto {
        margin: 4px 0 0;
        font-size: .93rem;
        line-height: 1.55;
        max-width: 58ch;
        font-family: inherit;
        font-weight: 500;
    }

    body.dashboard-modern .detalle-realizado-modal-close {
        width: 40px;
        min-width: 40px;
        height: 40px;
        padding: 0;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #475569;
    }

    body.dashboard-modern .detalle-realizado-modal-resumen {
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 14px;
    }

    body.dashboard-modern .detalle-realizado-modal .modal-panel-resumen-item {
        position: relative;
        min-height: 78px;
        padding: 16px 16px 16px 52px;
        border-radius: 14px;
        border: 1px solid #dbe5f1;
        background: #fbfdff;
        box-shadow: none;
        gap: 5px;
        align-content: center;
    }

    body.dashboard-modern .detalle-realizado-modal .modal-panel-resumen-item::before {
        content: "";
        position: absolute;
        left: 16px;
        top: 18px;
        width: 20px;
        height: 20px;
        background-color: #2563eb;
        mask-repeat: no-repeat;
        mask-position: center;
        mask-size: contain;
        -webkit-mask-repeat: no-repeat;
        -webkit-mask-position: center;
        -webkit-mask-size: contain;
    }

    body.dashboard-modern .detalle-realizado-modal .modal-panel-resumen-item:nth-child(1)::before {
        mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath d='M12 12a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Zm-6 7a6 6 0 0 1 12 0' fill='none' stroke='black' stroke-width='1.8' stroke-linecap='round'/%3E%3C/svg%3E");
        -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath d='M12 12a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Zm-6 7a6 6 0 0 1 12 0' fill='none' stroke='black' stroke-width='1.8' stroke-linecap='round'/%3E%3C/svg%3E");
    }

    body.dashboard-modern .detalle-realizado-modal .modal-panel-resumen-item:nth-child(2)::before {
        mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath d='M4.75 6.75h14.5v9.5H4.75zM8.5 19.25h7' fill='none' stroke='black' stroke-width='1.8' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
        -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath d='M4.75 6.75h14.5v9.5H4.75zM8.5 19.25h7' fill='none' stroke='black' stroke-width='1.8' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
    }

    body.dashboard-modern .detalle-realizado-modal .modal-panel-resumen-item:nth-child(3)::before {
        mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath d='M7.5 4.75h5.25l4 4-7.5 7.5-4-4V7.5l2.25-2.75Z' fill='none' stroke='black' stroke-width='1.8' stroke-linejoin='round'/%3E%3Ccircle cx='9' cy='9' r='1' fill='black'/%3E%3C/svg%3E");
        -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath d='M7.5 4.75h5.25l4 4-7.5 7.5-4-4V7.5l2.25-2.75Z' fill='none' stroke='black' stroke-width='1.8' stroke-linejoin='round'/%3E%3Ccircle cx='9' cy='9' r='1' fill='black'/%3E%3C/svg%3E");
    }

    body.dashboard-modern .detalle-realizado-modal .modal-panel-resumen-item:nth-child(4)::before {
        mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath d='M12 12a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Zm-6 7a6 6 0 0 1 12 0' fill='none' stroke='black' stroke-width='1.8' stroke-linecap='round'/%3E%3C/svg%3E");
        -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath d='M12 12a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Zm-6 7a6 6 0 0 1 12 0' fill='none' stroke='black' stroke-width='1.8' stroke-linecap='round'/%3E%3C/svg%3E");
    }

    body.dashboard-modern .detalle-realizado-modal .modal-panel-resumen-item span {
        color: #7c8aa5;
        font-size: .72rem;
        letter-spacing: .08em;
        font-family: inherit;
        font-weight: 700;
        text-transform: uppercase;
    }

    body.dashboard-modern .detalle-realizado-modal .modal-panel-resumen-item strong {
        color: #0f172a;
        font-size: .98rem;
        line-height: 1.35;
        font-family: inherit;
        font-weight: 700;
        word-break: break-word;
    }

    body.dashboard-modern .detalle-realizado-modal-grid {
        gap: 16px;
    }

    body.dashboard-modern .detalle-realizado-modal .campo-fecha-inline {
        gap: 9px;
    }

    body.dashboard-modern .detalle-realizado-modal .campo-fecha-inline > span,
    body.dashboard-modern .detalle-realizado-modal .detalle-realizado-evidencia-label {
        color: #334155;
        font-size: .76rem;
        letter-spacing: .06em;
        font-family: inherit;
        font-weight: 700;
        text-transform: uppercase;
    }

    body.dashboard-modern .detalle-realizado-modal .input-fecha-programar,
    body.dashboard-modern .detalle-realizado-modal .campo-fecha-inline textarea {
        border-radius: 12px;
        border-color: #dbe5f1;
        background: #fff;
        box-shadow: none;
        color: #0f172a;
        font-family: inherit;
        appearance: none;
        -webkit-appearance: none;
    }

    body.dashboard-modern .detalle-realizado-modal .input-fecha-programar {
        min-height: 48px;
        padding: 12px 16px 12px 42px;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath d='M8 3.75V6m8-2.25V6M4.75 9h14.5M6.5 5.25h11A1.75 1.75 0 0 1 19.25 7v10.5A1.75 1.75 0 0 1 17.5 19.25h-11A1.75 1.75 0 0 1 4.75 17.5V7A1.75 1.75 0 0 1 6.5 5.25Zm2.5 7.5h2.25m2.5 0H15' fill='none' stroke='%232563eb' stroke-width='1.8' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: 14px center;
        background-size: 16px 16px;
        font-size: .96rem;
        font-weight: 500;
        line-height: 1.35;
        display: block;
    }

    body.dashboard-modern .detalle-realizado-modal .campo-fecha-inline textarea {
        min-height: 136px;
        padding: 14px 16px;
        resize: none;
        font-size: .94rem;
        font-weight: 500;
        line-height: 1.6;
        display: block;
    }

    body.dashboard-modern .detalle-realizado-modal .detalle-realizado-evidencia-wrap {
        gap: 10px;
    }

    body.dashboard-modern .detalle-realizado-modal .evidencia-pdf-card-modal,
    body.dashboard-modern .detalle-realizado-modal .detalle-realizado-evidencia-vacio {
        display: grid;
        grid-template-columns: auto minmax(0, 1fr) auto;
        gap: 16px;
        align-items: center;
        padding: 18px 20px;
        border-radius: 16px;
        border: 1px solid #dbe5f1;
        background: #fbfdff;
    }

    body.dashboard-modern .detalle-realizado-modal .detalle-realizado-evidencia-vacio {
        display: grid;
    }

    body.dashboard-modern .detalle-realizado-modal .detalle-realizado-evidencia-icono {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        border: 1px solid #dbe5f1;
        color: #ef4444;
        font-size: .72rem;
        font-weight: 800;
        letter-spacing: .08em;
        align-self: center;
        justify-self: start;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        line-height: 1;
    }

    body.dashboard-modern .detalle-realizado-modal .evidencia-pdf-copy {
        display: grid;
        gap: 4px;
        align-content: center;
        min-width: 0;
    }

    body.dashboard-modern .detalle-realizado-modal .evidencia-pdf-copy strong {
        color: #0f172a;
        font-size: .95rem;
        font-weight: 600;
        font-family: inherit;
        line-height: 1.35;
        word-break: break-word;
    }

    body.dashboard-modern .detalle-realizado-modal .evidencia-pdf-copy span {
        color: #64748b;
        font-size: .88rem;
        line-height: 1.45;
        font-family: inherit;
        font-weight: 500;
    }

    body.dashboard-modern .detalle-realizado-modal .detalle-realizado-pdf-actions,
    body.dashboard-modern .detalle-realizado-modal .evidencia-pdf-acciones {
        display: flex;
        gap: 10px;
        align-items: center;
        align-self: center;
    }

    body.dashboard-modern .detalle-realizado-modal .evidencia-pdf-acciones .btn-link {
        min-width: 82px;
        min-height: 44px;
        justify-content: center;
        border-radius: 12px;
        padding: 12px 16px;
        font-weight: 700;
        font-family: inherit;
        text-decoration: none;
        box-shadow: none;
        align-items: center;
        line-height: 1.2;
    }

    body.dashboard-modern .detalle-realizado-modal .evidencia-pdf-acciones .btn-link.secondary {
        background: #fff;
        border: 1px solid #dbe5f1;
        color: #2563eb;
    }

    body.dashboard-modern .detalle-realizado-modal .evidencia-pdf-acciones .btn-link:not(.secondary) {
        background: #2563eb;
        border: 1px solid #2563eb;
        color: #fff;
    }

    body.dashboard-modern .detalle-realizado-modal .evidencia-pdf-acciones .is-disabled {
        background: #f8fafc;
        border-color: #dbe5f1;
        color: #94a3b8;
        pointer-events: none;
    }

    body.dashboard-modern .detalle-realizado-modal-actions {
        margin-top: 4px;
        padding-top: 18px;
        border-top: 1px solid #e2e8f0;
        justify-content: flex-end;
    }

    body.dashboard-modern .detalle-realizado-modal-actions .btn {
        min-width: 104px;
        border-radius: 12px;
        background: #0f172a;
        color: #fff;
        border: 1px solid #0f172a;
    }

    body.dashboard-modern .detalle-realizado-modal-actions .btn:hover,
    body.dashboard-modern .detalle-realizado-modal-actions .btn:focus-visible {
        background: #111f38;
        border-color: #111f38;
    }

    @media (max-width: 900px) {
        body.dashboard-modern .detalle-realizado-modal-dialogo {
            width: min(100%, 760px);
            padding: 28px 24px 24px;
        }

        body.dashboard-modern .detalle-realizado-modal-resumen {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 640px) {
        body.dashboard-modern .detalle-realizado-modal {
            padding: 16px;
        }

        body.dashboard-modern .detalle-realizado-modal-dialogo {
            width: 100%;
            max-height: calc(100vh - 32px);
            padding: 22px 18px 20px;
            gap: 18px;
        }

        body.dashboard-modern .detalle-realizado-modal-resumen,
        body.dashboard-modern .detalle-realizado-modal-grid {
            grid-template-columns: 1fr;
        }

        body.dashboard-modern .detalle-realizado-modal .evidencia-pdf-card-modal,
        body.dashboard-modern .detalle-realizado-modal .detalle-realizado-evidencia-vacio {
            grid-template-columns: 1fr;
            align-items: start;
        }

        body.dashboard-modern .detalle-realizado-modal .evidencia-pdf-acciones {
            width: 100%;
            flex-wrap: wrap;
        }

        body.dashboard-modern .detalle-realizado-modal .evidencia-pdf-acciones .btn-link {
            flex: 1 1 140px;
        }
    }

    body.dashboard-modern .maintenance-action-summary {
        display: flex;
        align-items: center;
        justify-content: flex-start;
    }

    body.dashboard-modern .maintenance-modal-form {
        display: grid;
        gap: 14px;
    }

    body.dashboard-modern .maintenance-modal-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
    }

    body.dashboard-modern .maintenance-modal-grid .maintenance-modal-full {
        grid-column: 1 / -1;
    }

    body.dashboard-modern .maintenance-modal-actions {
        display: flex;
        justify-content: flex-end;
    }

    @media (max-width: 720px) {
        body.dashboard-modern .maintenance-modal-grid {
            grid-template-columns: 1fr;
        }

        body.dashboard-modern .maintenance-modal-grid .maintenance-modal-full {
            grid-column: 1 / -1;
        }
    }
</style>
