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
