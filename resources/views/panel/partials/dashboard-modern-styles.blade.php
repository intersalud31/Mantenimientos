<style>
    html {
        margin: 0;
        padding: 0;
        min-height: 100%;
    }

    body.dashboard-modern {
        margin: 0 !important;
        padding: 0 !important;
        padding-top: 0 !important;
        margin-block-start: 0 !important;
        padding-block-start: 0 !important;
        font-family: var(--panel-font-base);
        min-height: 100vh;
        background:
            radial-gradient(circle at top left, rgba(37, 99, 235, .08), transparent 28%),
            radial-gradient(circle at top right, rgba(37, 99, 235, .06), transparent 24%),
            #f5f7fb;
        color: #0f172a;
    }

    body.dashboard-modern,
    body.dashboard-modern button,
    body.dashboard-modern input,
    body.dashboard-modern select,
    body.dashboard-modern textarea,
    body.dashboard-modern label,
    body.dashboard-modern th,
    body.dashboard-modern td,
    body.dashboard-modern .btn,
    body.dashboard-modern .btn-link,
    body.dashboard-modern .modal-panel,
    body.dashboard-modern .modal-panel *,
    body.dashboard-modern .user-modal-panel,
    body.dashboard-modern .user-modal-panel *,
    body.dashboard-modern .user-modal-dialog,
    body.dashboard-modern .user-modal-dialog * {
        font-family: var(--panel-font-base);
    }

    body.dashboard-modern > *:first-child {
        margin-top: 0 !important;
    }

    body.dashboard-modern .wrap {
        max-width: 1360px;
        margin: 0 auto;
        padding: 22px 20px 48px;
    }

    body.dashboard-modern .topbar-shell {
        position: relative;
        top: 0;
        width: 100%;
        max-width: 100%;
        margin: 0;
        padding-top: 0;
        z-index: 30;
        background: rgba(15, 23, 42, .92);
        backdrop-filter: blur(18px);
        border-bottom: 1px solid rgba(148, 163, 184, .16);
        color: #f8fafc;
        box-shadow: 0 10px 26px rgba(15, 23, 42, .12);
    }

    body.dashboard-modern .topbar-shell--stacked {
        border-bottom-left-radius: 0;
        border-bottom-right-radius: 0;
        overflow: visible;
    }

    body.dashboard-modern [x-cloak] {
        display: none !important;
    }

    body.dashboard-modern .topbar-inner {
        width: 100%;
        max-width: none;
        margin: 0;
        padding: 14px 24px;
        display: grid;
        grid-template-columns: auto 1fr auto;
        gap: 18px;
        align-items: center;
        box-sizing: border-box;
    }

    body.dashboard-modern .topbar-focus {
        width: 100%;
        max-width: none;
        margin: 0;
        padding: 0 24px 18px;
        display: grid;
        grid-template-columns: minmax(0, 1fr);
        gap: 18px;
        align-items: end;
        box-sizing: border-box;
    }

    body.dashboard-modern .topbar-focus-copy {
        min-width: 0;
    }

    body.dashboard-modern .topbar-focus-title {
        margin: 4px 0 4px;
        color: #f8fafc;
        font-size: clamp(1.4rem, 2vw, 1.9rem);
        line-height: 1.1;
        letter-spacing: -.02em;
    }

    body.dashboard-modern .topbar-focus-subtitle {
        margin: 0;
        color: #cbd5e1;
        max-width: 760px;
    }

    body.dashboard-modern .topbar-focus-actions {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
        justify-content: flex-end;
    }

    body.dashboard-modern .topbar-user-chip {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        padding: 8px 12px;
        border-radius: 999px;
        background: rgba(255, 255, 255, .08);
        border: 1px solid rgba(148, 163, 184, .18);
        color: #f8fafc;
    }

    body.dashboard-modern .topbar-user-avatar {
        width: 30px;
        height: 30px;
        border-radius: 999px;
        display: inline-grid;
        place-items: center;
        background: rgba(37, 99, 235, .35);
        color: #fff;
        font-size: .75rem;
        font-weight: 800;
        letter-spacing: .04em;
        flex: none;
    }

    body.dashboard-modern .topbar-user-name {
        font-size: .88rem;
        font-weight: 700;
        white-space: nowrap;
    }

    body.dashboard-modern .brand-block {
        display: flex;
        align-items: center;
        gap: 12px;
        min-width: 220px;
    }

    body.dashboard-modern .brand-mark {
        width: 42px;
        height: 42px;
        border-radius: 14px;
        background: linear-gradient(135deg, #2563eb, #0f172a);
        box-shadow: 0 12px 24px rgba(37, 99, 235, .26);
        display: grid;
        place-items: center;
        font-size: 1.1rem;
        color: #fff;
        flex: none;
    }

    body.dashboard-modern .brand-copy strong,
    body.dashboard-modern .brand-copy span {
        display: block;
    }

    body.dashboard-modern .brand-copy strong {
        color: #f8fafc;
        font-size: .98rem;
        letter-spacing: .02em;
    }

    body.dashboard-modern .brand-copy span {
        color: #94a3b8;
        font-size: .82rem;
    }

    body.dashboard-modern .topbar-menu {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
        justify-content: center;
    }

    body.dashboard-modern .menu-group {
        position: relative;
    }

    body.dashboard-modern .menu-trigger {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 11px 15px;
        border-radius: 16px;
        border: 1px solid rgba(148, 163, 184, .18);
        background: rgba(30, 41, 59, .72);
        color: #e2e8f0;
        font-weight: 700;
        cursor: pointer;
        transition: transform .18s ease, background .18s ease, border-color .18s ease, box-shadow .18s ease;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, .04);
    }

    body.dashboard-modern .menu-trigger::before {
        content: '';
        width: 8px;
        height: 8px;
        border-radius: 999px;
        background: linear-gradient(135deg, #93c5fd, #2563eb);
        box-shadow: 0 0 0 3px rgba(59, 130, 246, .12);
        flex: none;
    }

    body.dashboard-modern .menu-trigger:hover,
    body.dashboard-modern .menu-trigger:focus-visible {
        background: rgba(37, 99, 235, .20);
        border-color: rgba(96, 165, 250, .45);
        transform: translateY(-1px);
        outline: none;
    }

    body.dashboard-modern .menu-trigger--active {
        background: linear-gradient(135deg, rgba(37, 99, 235, .26), rgba(15, 23, 42, .92));
        border-color: rgba(96, 165, 250, .48);
        box-shadow: 0 14px 26px rgba(37, 99, 235, .16);
    }

    body.dashboard-modern .menu-trigger-label {
        letter-spacing: .01em;
    }

    body.dashboard-modern .menu-caret {
        width: 0;
        height: 0;
        border-left: 4px solid transparent;
        border-right: 4px solid transparent;
        border-top: 5px solid currentColor;
        opacity: .8;
    }

    body.dashboard-modern .dropdown-panel {
        position: absolute;
        top: calc(100% + 10px);
        left: 0;
        min-width: 270px;
        padding: 12px;
        border-radius: 18px;
        border: 1px solid rgba(148, 163, 184, .18);
        background: #fff;
        box-shadow: 0 24px 60px rgba(15, 23, 42, .18);
        display: grid;
        gap: 8px;
        transform-origin: top left;
    }

    body.dashboard-modern .dropdown-panel-right {
        right: 0;
        left: auto;
    }

    body.dashboard-modern .dropdown-title {
        padding: 8px 10px 4px;
        color: #64748b;
        font-size: .75rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .12em;
    }

    body.dashboard-modern .dropdown-link,
    body.dashboard-modern .dropdown-disabled {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        padding: 11px 12px;
        border-radius: 14px;
        text-decoration: none;
        color: #0f172a;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        transition: transform .15s ease, background .15s ease, border-color .15s ease, box-shadow .15s ease;
    }

    body.dashboard-modern .dropdown-link:hover {
        background: #eef4ff;
        border-color: #dbe7ff;
        transform: translateX(2px);
        box-shadow: 0 8px 18px rgba(37, 99, 235, .08);
    }

    body.dashboard-modern .dropdown-link.active {
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        color: #fff;
        border-color: #2563eb;
        box-shadow: 0 12px 22px rgba(37, 99, 235, .22);
    }

    body.dashboard-modern .dropdown-disabled {
        opacity: .55;
        cursor: not-allowed;
    }

    body.dashboard-modern .topbar-utility {
        display: flex;
        align-items: center;
        gap: 10px;
        justify-content: flex-end;
        flex-wrap: wrap;
    }

    body.dashboard-modern .topbar-user-chip {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        padding: 8px 12px;
        border-radius: 999px;
        background: rgba(255, 255, 255, .08);
        border: 1px solid rgba(148, 163, 184, .18);
        color: #f8fafc;
    }

    body.dashboard-modern .topbar-user-avatar {
        width: 30px;
        height: 30px;
        border-radius: 999px;
        display: inline-grid;
        place-items: center;
        background: rgba(37, 99, 235, .35);
        color: #fff;
        font-size: .75rem;
        font-weight: 800;
        letter-spacing: .04em;
        flex: none;
    }

    body.dashboard-modern .topbar-user-name {
        font-size: .88rem;
        font-weight: 700;
        white-space: nowrap;
    }

    body.dashboard-modern .topbar-search {
        position: relative;
        min-width: min(360px, 36vw);
    }

    body.dashboard-modern .topbar-search input {
        padding-left: 42px;
        border-radius: 999px;
        border-color: rgba(148, 163, 184, .24);
        background: rgba(255, 255, 255, .98);
        box-shadow: 0 10px 18px rgba(15, 23, 42, .06);
    }

    body.dashboard-modern .topbar-search .search-icon {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: #64748b;
        font-size: .95rem;
    }

    body.dashboard-modern .card {
        background: rgba(255, 255, 255, .96);
        border: 1px solid rgba(148, 163, 184, .18);
        border-radius: 28px;
        box-shadow: 0 18px 50px rgba(15, 23, 42, .06);
        padding: 22px;
        margin-bottom: 18px;
    }

    body.dashboard-modern .topline {
        display: flex;
        justify-content: space-between;
        gap: 18px;
        align-items: flex-start;
        flex-wrap: wrap;
    }

    body.dashboard-modern h1,
    body.dashboard-modern h2,
    body.dashboard-modern h4 {
        margin-top: 0;
        color: #0f172a;
        letter-spacing: -.02em;
    }

    body.dashboard-modern h1 { font-size: clamp(2rem, 2.8vw, 2.8rem); }
    body.dashboard-modern h2 { font-size: 1.55rem; }

    body.dashboard-modern .muted { color: #64748b; }
    body.dashboard-modern .mini { color: #64748b; font-size: .92rem; }

    body.dashboard-modern .section-kicker {
        margin: 0 0 10px;
        color: #2563eb;
        font-size: .75rem;
        font-weight: 800;
        letter-spacing: .16em;
        text-transform: uppercase;
    }

    body.dashboard-modern .hero-copy {
        max-width: 760px;
    }

    body.dashboard-modern .module-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 16px;
        flex-wrap: wrap;
        margin: 0 0 18px;
    }

    body.dashboard-modern .module-header-copy {
        min-width: 0;
        max-width: 820px;
    }

    body.dashboard-modern .module-header-kicker {
        margin: 0 0 8px;
        color: #2563eb;
        font-size: .75rem;
        font-weight: 800;
        letter-spacing: .16em;
        text-transform: uppercase;
    }

    body.dashboard-modern .module-header-title {
        margin: 0 0 6px;
        color: #0f172a;
        font-size: clamp(1.7rem, 2.2vw, 2.25rem);
        line-height: 1.08;
        letter-spacing: 0;
    }

    body.dashboard-modern .module-header-subtitle {
        margin: 0;
        color: #64748b;
        max-width: 760px;
    }

    body.dashboard-modern .hero-meta,
    body.dashboard-modern .period-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 16px;
    }

    body.dashboard-modern .chip,
    body.dashboard-modern .estado-tabla,
    body.dashboard-modern .badge-area {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border-radius: 999px;
        font-weight: 700;
        letter-spacing: .01em;
        line-height: 1;
        white-space: nowrap;
    }

    body.dashboard-modern .chip {
        padding: 9px 12px;
        background: #eef4ff;
        border: 1px solid #dbe7ff;
        color: #1d4ed8;
        font-size: .8rem;
    }

    body.dashboard-modern .chip--neutral {
        background: #f8fafc;
        border-color: #e2e8f0;
        color: #475569;
    }

    body.dashboard-modern .panel-navigation {
        margin-top: 18px;
    }

    body.dashboard-modern .panel-grid--two {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 16px;
        align-items: start;
    }

    body.dashboard-modern .panel-card--compact {
        padding: 18px;
    }

    body.dashboard-modern .panel-form-stack {
        display: grid;
        gap: 18px;
    }

    body.dashboard-modern .panel-form-actions--compact {
        justify-content: flex-end;
        margin-top: 2px;
    }

    body.dashboard-modern .summary-pill--compact {
        padding: 12px 14px;
        border-radius: 18px;
    }

    body.dashboard-modern .summary-pill--compact span {
        font-size: .72rem;
    }

    body.dashboard-modern .summary-pill--compact strong {
        font-size: 1.05rem;
    }

    body.dashboard-modern .panel-navigation .btn-link {
        margin-top: 0;
        padding: 11px 15px;
        border-radius: 14px;
        background: #eef4ff;
        color: #1d4ed8;
        border: 1px solid #dbe7ff;
    }

    body.dashboard-modern .panel-navigation .btn-link.secondary {
        background: #2563eb;
        color: #fff;
        border-color: #2563eb;
        box-shadow: 0 12px 28px rgba(37, 99, 235, .22);
    }

    body.dashboard-modern .config-section {
        display: grid;
        gap: 18px;
    }

    body.dashboard-modern .config-inline-form {
        display: grid;
        gap: 14px;
    }

    body.dashboard-modern .config-inline-form-row {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 14px;
        align-items: end;
    }

    body.dashboard-modern .config-inline-form-actions,
    body.dashboard-modern .config-revert-actions {
        align-self: end;
    }

    body.dashboard-modern .config-tech-list {
        display: grid;
        gap: 10px;
        list-style: none;
        padding: 0;
        margin: 0;
    }

    body.dashboard-modern .config-tech-item {
        display: flex;
        align-items: center;
        gap: 10px;
        min-height: 50px;
        padding: 12px 14px;
        border-radius: 16px;
        border: 1px solid #dfe7f2;
        background: #f8fbff;
        color: #0f172a;
        font-size: .95rem;
        font-weight: 600;
    }

    body.dashboard-modern .config-tech-dot {
        width: 10px;
        height: 10px;
        border-radius: 999px;
        background: linear-gradient(135deg, #dbeafe, #60a5fa);
        box-shadow: 0 0 0 4px rgba(37, 99, 235, .08);
        flex: none;
    }

    body.dashboard-modern .config-tech-name {
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    body.dashboard-modern .config-empty-state,
    body.dashboard-modern .config-empty-callout {
        display: grid;
        gap: 10px;
        padding: 16px;
        border-radius: 18px;
        border: 1px solid #e2e8f0;
        background: #f8fbff;
    }

    body.dashboard-modern .config-empty-state strong,
    body.dashboard-modern .config-empty-callout strong {
        color: #0f172a;
        font-size: .98rem;
    }

    body.dashboard-modern .config-empty-state p,
    body.dashboard-modern .config-empty-callout p {
        margin: 0;
    }

    body.dashboard-modern .config-reversion-summary {
        display: grid;
        gap: 12px;
        padding: 16px;
        border-radius: 20px;
        border: 1px solid #dfe7f2;
        background: #fff;
    }

    body.dashboard-modern .config-reversion-summary-copy h3 {
        margin: 0;
        font-size: 1.05rem;
        line-height: 1.25;
    }

    body.dashboard-modern .config-reversion-summary-copy p {
        margin: 0;
    }

    body.dashboard-modern .config-revert-form {
        display: grid;
        gap: 14px;
    }

    body.dashboard-modern .config-revert-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 14px;
        align-items: end;
    }

    body.dashboard-modern .config-revert-note {
        padding: 12px 14px;
        border-radius: 16px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        color: #64748b;
        font-size: .9rem;
        line-height: 1.5;
    }

    body.dashboard-modern .filters {
        display: grid;
        grid-template-columns: minmax(0, 1fr) repeat(2, minmax(160px, 180px)) auto auto;
        gap: 14px;
        align-items: end;
        margin-top: 18px;
    }

    body.dashboard-modern .field {
        min-width: 0;
        flex: initial;
    }

    body.dashboard-modern label {
        display: block;
        margin-bottom: 6px;
        color: #334155;
        font-weight: 700;
        font-size: .92rem;
    }

    body.dashboard-modern input,
    body.dashboard-modern select,
    body.dashboard-modern textarea {
        width: 100%;
        box-sizing: border-box;
        padding: 12px 14px;
        border-radius: 14px;
        border: 1px solid #d7e0ea;
        background: #fff;
        color: #0f172a;
        box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
    }

    body.dashboard-modern input:focus,
    body.dashboard-modern select:focus,
    body.dashboard-modern textarea:focus {
        outline: 0;
        border-color: #93c5fd;
        box-shadow: 0 0 0 4px rgba(37, 99, 235, .10);
    }

    body.dashboard-modern .btn,
    body.dashboard-modern .btn-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 11px 16px;
        border-radius: 14px;
        background: #2563eb;
        color: #fff;
        text-decoration: none;
        border: 1px solid #2563eb;
        cursor: pointer;
        box-shadow: 0 12px 24px rgba(37, 99, 235, .18);
        transition: transform .15s ease, box-shadow .15s ease, background .15s ease;
    }

    body.dashboard-modern .btn:hover,
    body.dashboard-modern .btn-link:hover {
        transform: translateY(-1px);
        box-shadow: 0 16px 28px rgba(37, 99, 235, .22);
    }

    body.dashboard-modern .btn.secondary,
    body.dashboard-modern .btn-link.secondary {
        background: #fff;
        color: #1d4ed8;
        border-color: #dbe7ff;
        box-shadow: none;
    }

    body.dashboard-modern .btn-link.secondary:hover {
        background: #eef4ff;
    }

    body.dashboard-modern .grid {
        display: grid;
        gap: 16px;
    }

    body.dashboard-modern .metric-grid {
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    }

    body.dashboard-modern .metric-card,
    body.dashboard-modern .stat {
        padding: 18px;
        border-radius: 20px;
        border: 1px solid #e2e8f0;
        background: linear-gradient(180deg, #fff, #f8fbff);
        box-shadow: 0 10px 24px rgba(15, 23, 42, .04);
    }

    body.dashboard-modern .metric-card span,
    body.dashboard-modern .stat strong {
        display: block;
        color: #64748b;
        font-size: .88rem;
        font-weight: 700;
        margin-bottom: 10px;
    }

    body.dashboard-modern .metric-card strong,
    body.dashboard-modern .stat .muted {
        font-size: 1.8rem;
        line-height: 1;
        letter-spacing: -.03em;
        color: #0f172a;
    }

    body.dashboard-modern .metric-card--accent strong { color: #2563eb; }
    body.dashboard-modern .metric-card--success strong { color: #059669; }
    body.dashboard-modern .metric-card--warning strong { color: #d97706; }

    body.dashboard-modern .compliance-card {
        padding: 22px;
        border-radius: 24px;
        background: linear-gradient(135deg, rgba(37, 99, 235, .08), rgba(59, 130, 246, .04));
        border: 1px solid rgba(37, 99, 235, .14);
    }

    body.dashboard-modern .compliance-top {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        align-items: center;
        flex-wrap: wrap;
        margin-bottom: 14px;
    }

    body.dashboard-modern .compliance-value {
        font-size: clamp(2rem, 4vw, 3rem);
        font-weight: 800;
        letter-spacing: -.04em;
        color: #1d4ed8;
    }

    body.dashboard-modern .progress-track {
        height: 10px;
        border-radius: 999px;
        background: rgba(148, 163, 184, .18);
        overflow: hidden;
        margin-top: 14px;
    }

    body.dashboard-modern .progress-bar {
        height: 100%;
        border-radius: inherit;
        background: linear-gradient(90deg, #2563eb, #1d4ed8);
    }

    body.dashboard-modern table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }

    body.dashboard-modern .table-shell,
    body.dashboard-modern .tabla-contenedor,
    body.dashboard-modern .panel-table-shell,
    body.dashboard-modern .tabla-premium-wrap {
        overflow: visible;
        border: 1px solid #e2e8f0;
        border-radius: 22px;
        background: #fff;
    }

    body.dashboard-modern .tabla-usuarios,
    body.dashboard-modern .tabla-premium {
        min-width: 100%;
    }

    body.dashboard-modern th {
        text-align: left;
        padding: 14px 14px;
        color: #475569;
        font-size: .84rem;
        text-transform: uppercase;
        letter-spacing: .08em;
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        position: sticky;
        top: 0;
        z-index: 1;
    }

    body.dashboard-modern td {
        text-align: left;
        padding: 16px 14px;
        border-bottom: 1px solid #eef2f7;
        vertical-align: top;
        color: #0f172a;
    }

    body.dashboard-modern tbody tr:hover td {
        background: #f8fbff;
    }

    body.dashboard-modern .badge-area,
    body.dashboard-modern .estado-tabla {
        padding: 7px 11px;
        font-size: .77rem;
    }

    body.dashboard-modern .badge-area {
        background: #eef4ff;
        border: 1px solid #dbe7ff;
        color: #1d4ed8;
    }

    body.dashboard-modern .estado-tabla-exito {
        background: rgba(37, 99, 235, .12);
        border: 1px solid rgba(37, 99, 235, .20);
        color: #047857;
    }

    body.dashboard-modern .estado-tabla-info {
        background: rgba(37, 99, 235, .10);
        border: 1px solid rgba(37, 99, 235, .18);
        color: #1d4ed8;
    }

    body.dashboard-modern .estado-tabla-alerta {
        background: rgba(245, 158, 11, .12);
        border: 1px solid rgba(245, 158, 11, .22);
        color: #b45309;
    }

    body.dashboard-modern .estado-tabla-neutral {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        color: #475569;
    }

    body.dashboard-modern .codigo-activo,
    body.dashboard-modern .fecha-destacada,
    body.dashboard-modern .texto-principal-tabla {
        font-weight: 700;
        color: #0f172a;
    }

    body.dashboard-modern .row-actions,
    body.dashboard-modern .acciones-usuarios {
        min-width: 0;
    }

    body.dashboard-modern .panel-action-menu {
        position: relative;
        display: inline-flex;
        justify-content: flex-end;
        align-items: center;
        isolation: isolate;
    }

    body.dashboard-modern .panel-action-menu--open {
        z-index: 1000;
    }

    body.dashboard-modern .panel-action-menu--start {
        justify-content: flex-start;
    }

    body.dashboard-modern .panel-action-menu-trigger {
        width: 36px;
        height: 36px;
        border-radius: 12px;
        border: 1px solid #dbe4f0;
        background: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 4px;
        cursor: pointer;
        box-shadow: 0 8px 18px rgba(15, 23, 42, .06);
        transition: transform .16s ease, box-shadow .16s ease, border-color .16s ease, background-color .16s ease;
    }

    body.dashboard-modern .panel-action-menu-trigger[aria-expanded="true"] {
        border-color: #bfd4ff;
        background: #f8fbff;
    }

    body.dashboard-modern .panel-action-menu-trigger:hover,
    body.dashboard-modern .panel-action-menu-trigger:focus-visible {
        border-color: #bfd4ff;
        background: #f8fbff;
        outline: none;
        transform: translateY(-1px);
    }

    body.dashboard-modern .panel-action-menu-trigger:focus-visible {
        box-shadow: 0 0 0 3px rgba(37, 99, 235, .12), 0 8px 18px rgba(15, 23, 42, .06);
    }

    body.dashboard-modern .panel-action-menu-trigger span {
        width: 4px;
        height: 4px;
        border-radius: 999px;
        background: #475569;
        display: block;
    }

    body.dashboard-modern .panel-action-menu-panel {
        position: absolute;
        right: 0;
        top: calc(100% + 8px);
        z-index: 1000;
        width: 220px;
        max-width: min(220px, calc(100dvw - 24px));
        max-height: var(--panel-action-menu-max-height, min(260px, calc(100vh - 24px)));
        padding: 8px;
        border-radius: 14px;
        border: 1px solid #dfe7f2;
        background: #fff;
        box-shadow: 0 18px 42px rgba(15, 23, 42, .14);
        display: grid;
        gap: 6px;
        overflow-x: auto;
        overflow-y: auto;
        box-sizing: border-box;
        transform-origin: top right;
    }

    body.dashboard-modern .panel-action-menu--start .panel-action-menu-panel {
        transform-origin: top left;
    }

    body.dashboard-modern .panel-action-menu--top .panel-action-menu-panel {
        top: auto;
        bottom: calc(100% + 8px);
    }

    body.dashboard-modern .panel-action-menu-item {
        display: block;
        width: 100%;
        box-sizing: border-box;
        text-align: left;
        padding: 10px 12px;
        border-radius: 10px;
        border: 0;
        background: #f8fafc;
        color: #0f172a;
        text-decoration: none;
        cursor: pointer;
        font-weight: 700;
        font-family: var(--panel-font-base);
        line-height: 1.25;
        transition: background-color .16s ease, color .16s ease, transform .16s ease, box-shadow .16s ease;
    }

    body.dashboard-modern .panel-action-menu-item:hover,
    body.dashboard-modern .panel-action-menu-item:focus-visible {
        background: #eef4ff;
        outline: none;
    }

    body.dashboard-modern .panel-action-menu-item--soft {
        background: #f8fafc;
        color: #0f172a;
    }

    body.dashboard-modern .panel-action-menu-item--soft:hover,
    body.dashboard-modern .panel-action-menu-item--soft:focus-visible {
        background: #eef4ff;
    }

    body.dashboard-modern .panel-action-menu-item--danger {
        color: #b91c1c;
        background: #fef2f2;
    }

    body.dashboard-modern .panel-action-menu-item--danger:hover,
    body.dashboard-modern .panel-action-menu-item--danger:focus-visible {
        background: #fee2e2;
    }

    body.dashboard-modern .panel-action-menu-item--disabled {
        background: #f8fafc;
        color: #64748b;
        cursor: default;
        transform: none;
        pointer-events: none;
    }

    body.dashboard-modern .panel-action-menu-form {
        margin: 0;
        width: 100%;
    }

    body.dashboard-modern .menu-acciones-disparador {
        background: #fff;
        border-color: #dbe7ff;
    }

    body.dashboard-modern .menu-acciones-dropdown {
        background: #fff;
        border-color: #e2e8f0;
    }

    body.dashboard-modern .menu-acciones-opcion {
        background: #f8fafc;
        color: #0f172a;
    }

    body.dashboard-modern .menu-acciones-opcion:hover {
        background: #eef4ff;
    }

    body.dashboard-modern .menu-acciones-opcion-pdf,
    body.dashboard-modern .menu-acciones-opcion-descarga {
        background: #eef4ff;
    }

    body.dashboard-modern .notice {
        margin: 18px 0 0;
        padding: 14px 16px;
        border-radius: 16px;
        background: rgba(37, 99, 235, .08);
        border: 1px solid rgba(37, 99, 235, .14);
        color: #1d4ed8;
    }

    body.dashboard-modern .notice.error {
        background: rgba(239, 68, 68, .08);
        border-color: rgba(239, 68, 68, .16);
        color: #b91c1c;
    }

    body.dashboard-modern .pager {
        display: flex;
        gap: 10px;
        align-items: center;
        flex-wrap: wrap;
        margin-top: 18px;
    }

    body.dashboard-modern .empty-state,
    body.dashboard-modern .estado-vacio {
        display: flex;
        gap: 14px;
        align-items: center;
        padding: 18px 0;
    }

    body.dashboard-modern .empty-state-icon,
    body.dashboard-modern .estado-vacio-icono {
        width: 42px;
        height: 42px;
        border-radius: 14px;
        display: inline-grid;
        place-items: center;
        background: #eef4ff;
        color: #1d4ed8;
        font-size: 1rem;
        flex: none;
    }

    body.dashboard-modern .modal-panel {
        position: fixed;
        inset: 0;
        z-index: 80;
        display: grid;
        place-items: center;
        padding: 18px;
    }

    body.dashboard-modern .modal-panel-backdrop {
        position: absolute;
        inset: 0;
        background: rgba(15, 23, 42, .58);
    }

    body.dashboard-modern .modal-panel-dialogo {
        position: relative;
        z-index: 1;
        width: min(100%, 920px);
        max-height: calc(100vh - 36px);
        overflow: auto;
        border-radius: 24px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 30px 80px rgba(15, 23, 42, .20);
        padding: 24px;
    }

    body.dashboard-modern .modal-panel-dialogo-operativo {
        width: min(100%, 920px);
    }

    body.dashboard-modern .modal-panel-cabecera {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        align-items: flex-start;
        margin-bottom: 18px;
    }

    body.dashboard-modern .modal-panel-cerrar {
        min-width: 40px;
        height: 40px;
        padding: 0 14px;
        border-radius: 12px;
        border: 1px solid #dbe4f0;
        background: #fff;
        color: #0f172a;
        cursor: pointer;
        flex: none;
        font-weight: 700;
        font-family: var(--panel-font-base);
    }

    body.dashboard-modern .modal-panel-cerrar:hover,
    body.dashboard-modern .modal-panel-cerrar:focus-visible {
        border-color: #bfd4ff;
        outline: none;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, .12);
    }

    body.modal-open {
        overflow: hidden;
    }

    body.dashboard-modern .modal-panel-estatico {
        background: rgba(15, 23, 42, .58);
    }

    body.dashboard-modern .modal-panel-dialogo {
        background: #fff;
        color: #0f172a;
        border-color: #e2e8f0;
    }

    body.dashboard-modern .modal-panel-dialogo-detalle {
        width: min(100%, 980px);
        padding: 30px;
        display: grid;
        gap: 18px;
    }

    body.dashboard-modern .modal-panel-kicker {
        color: #2563eb;
        margin: 0 0 10px;
        font-size: .75rem;
        font-weight: 800;
        letter-spacing: 0;
        text-transform: none;
    }

    body.dashboard-modern .modal-panel-cabecera h4 {
        margin: 0 0 8px;
        color: #0f172a;
        font-size: 1.25rem;
        line-height: 1.2;
        letter-spacing: 0;
    }

    body.dashboard-modern .tabla-programados-nombre {
        font-weight: 400;
        color: #0f172a;
    }

    body.dashboard-modern .tabla-programados-columna-fecha {
        text-align: center;
    }

    body.dashboard-modern .modal-panel-texto,
    body.dashboard-modern .detalle-realizado-evidencia-vacio p {
        color: #64748b;
    }

    body.dashboard-modern .modal-panel-resumen {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 12px;
    }

    body.dashboard-modern .modal-panel-resumen-item,
    body.dashboard-modern .detalle-realizado-evidencia-vacio,
    body.dashboard-modern .evidencia-pdf-card-modal {
        background: #f8fafc;
        border-color: #e2e8f0;
    }

    body.dashboard-modern .modal-panel-resumen-item {
        display: grid;
        gap: 6px;
        padding: 14px 16px;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, .7);
    }

    body.dashboard-modern .modal-panel-resumen-item span {
        color: #64748b;
        font-size: .75rem;
        font-weight: 800;
        letter-spacing: 0;
        text-transform: none;
    }

    body.dashboard-modern .modal-panel-resumen-item strong {
        color: #0f172a;
        font-size: .98rem;
        line-height: 1.35;
        font-weight: 700;
    }

    body.dashboard-modern .modal-panel-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
        align-items: start;
    }

    body.dashboard-modern .modal-panel-campo-completo {
        grid-column: 1 / -1;
    }

    body.dashboard-modern .campo-fecha-inline {
        display: grid;
        gap: 8px;
        align-content: start;
    }

    body.dashboard-modern .campo-fecha-inline > span,
    body.dashboard-modern .detalle-realizado-evidencia-label {
        color: #334155;
        font-size: .82rem;
        font-weight: 800;
        letter-spacing: 0;
        text-transform: none;
    }

    body.dashboard-modern .campo-fecha-inline textarea,
    body.dashboard-modern .input-fecha-programar {
        width: 100%;
        border-radius: 14px;
        border: 1px solid #dfe7f2;
        padding: 12px 14px;
        color: #0f172a;
    }

    body.dashboard-modern .campo-fecha-inline textarea {
        min-height: 126px;
        resize: vertical;
    }

    body.dashboard-modern .detalle-realizado-evidencia-wrap {
        display: grid;
        gap: 10px;
        padding-top: 2px;
    }

    body.dashboard-modern .evidencia-pdf-card-modal {
        display: grid;
        grid-template-columns: auto minmax(0, 1fr) auto;
        gap: 14px;
        align-items: center;
        padding: 16px;
        border: 1px solid #e2e8f0;
        border-radius: 18px;
    }

    body.dashboard-modern .detalle-realizado-evidencia-vacio {
        display: grid;
        gap: 10px;
        padding: 16px;
        border: 1px solid #e2e8f0;
        border-radius: 18px;
    }

    body.dashboard-modern .detalle-realizado-evidencia-vacio .estado-tabla {
        width: fit-content;
    }

    body.dashboard-modern .detalle-realizado-evidencia-vacio p {
        margin: 0;
    }

    body.dashboard-modern .modal-panel-dialogo-detalle .actions.panel-page-actions {
        margin-top: 4px;
        padding-top: 14px;
        border-top: 1px solid #e2e8f0;
        justify-content: flex-end;
    }

    body.dashboard-modern .modal-panel-dialogo-detalle .btn {
        min-width: 124px;
    }

    body.dashboard-modern .input-fecha-programar,
    body.dashboard-modern .campo-fecha-inline textarea {
        background: #fff;
    }

    body.dashboard-modern .maintenance-modal-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
    }

    body.dashboard-modern .maintenance-modal-full {
        grid-column: 1 / -1;
    }

    body.dashboard-modern .maintenance-modal-actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 16px;
    }

    body.dashboard-modern .maintenance-date-field {
        position: relative;
    }

    body.dashboard-modern .maintenance-date-trigger {
        width: 100%;
        min-height: 46px;
        display: inline-flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 10px 14px;
        border-radius: 14px;
        border: 1px solid #dfe7f2;
        background: #fff;
        color: #0f172a;
        font-family: var(--panel-font-base);
        font-size: .92rem;
        line-height: 1.4;
        text-align: left;
        box-shadow: inset 0 1px 0 rgba(255,255,255,.65);
        cursor: pointer;
    }

    body.dashboard-modern .maintenance-date-trigger:hover {
        border-color: #bfd4ff;
        box-shadow: 0 0 0 1px rgba(37, 99, 235, .05), inset 0 1px 0 rgba(255,255,255,.65);
    }

    body.dashboard-modern .maintenance-date-trigger:focus-visible {
        outline: none;
        border-color: #93c5fd;
        box-shadow: 0 0 0 4px rgba(37, 99, 235, .12);
    }

    body.dashboard-modern .maintenance-date-trigger-text {
        min-width: 0;
    }

    body.dashboard-modern .maintenance-date-trigger-icon {
        width: 18px;
        height: 18px;
        display: inline-grid;
        place-items: center;
        color: #2563eb;
        flex: none;
    }

    body.dashboard-modern .maintenance-date-trigger-icon svg {
        width: 18px;
        height: 18px;
    }

    body.dashboard-modern .maintenance-date-picker {
        position: fixed;
        z-index: 150;
        display: grid;
        gap: 12px;
        padding: 14px;
        border-radius: 18px;
        border: 1px solid #dfe7f2;
        background: #fff;
        color: #0f172a;
        box-shadow: 0 24px 60px rgba(15, 23, 42, .22);
        overflow: auto;
    }

    body.dashboard-modern .maintenance-date-picker-header {
        display: grid;
        grid-template-columns: auto minmax(0, 1fr) auto;
        gap: 10px;
        align-items: center;
    }

    body.dashboard-modern .maintenance-date-picker-nav,
    body.dashboard-modern .maintenance-date-picker-close,
    body.dashboard-modern .maintenance-date-picker-today {
        min-height: 40px;
        padding: 0 14px;
        border-radius: 12px;
        border: 1px solid #dfe7f2;
        background: #fff;
        color: #334155;
        font: inherit;
        cursor: pointer;
    }

    body.dashboard-modern .maintenance-date-picker-nav:hover,
    body.dashboard-modern .maintenance-date-picker-close:hover,
    body.dashboard-modern .maintenance-date-picker-today:hover {
        border-color: #bfd4ff;
        color: #0f172a;
    }

    body.dashboard-modern .maintenance-date-picker-nav:focus-visible,
    body.dashboard-modern .maintenance-date-picker-close:focus-visible,
    body.dashboard-modern .maintenance-date-picker-today:focus-visible,
    body.dashboard-modern .maintenance-date-picker-year:focus-visible,
    body.dashboard-modern .maintenance-date-picker-day:focus-visible {
        outline: none;
        border-color: #93c5fd;
        box-shadow: 0 0 0 4px rgba(37, 99, 235, .12);
    }

    body.dashboard-modern .maintenance-date-picker-title-block {
        display: grid;
        gap: 6px;
        justify-items: center;
    }

    body.dashboard-modern .maintenance-date-picker-title {
        font-size: 1rem;
        font-weight: 700;
        text-transform: capitalize;
    }

    body.dashboard-modern .maintenance-date-picker-year-label {
        font-size: .78rem;
        font-weight: 700;
        color: #64748b;
    }

    body.dashboard-modern .maintenance-date-picker-year {
        min-width: 100px;
        min-height: 40px;
        padding: 8px 36px 8px 12px;
        border-radius: 12px;
        border: 1px solid #dfe7f2;
        background: #fff;
        color: #0f172a;
        font-family: var(--panel-font-base);
    }

    body.dashboard-modern .maintenance-date-picker-weekdays {
        display: grid;
        grid-template-columns: repeat(7, minmax(0, 1fr));
        gap: 6px;
        color: #64748b;
        font-size: .78rem;
        font-weight: 700;
        text-align: center;
        letter-spacing: .02em;
    }

    body.dashboard-modern .maintenance-date-picker-grid {
        display: grid;
        grid-template-columns: repeat(7, minmax(0, 1fr));
        gap: 6px;
    }

    body.dashboard-modern .maintenance-date-picker-day {
        min-height: 40px;
        border-radius: 12px;
        border: 1px solid transparent;
        background: #f8fafc;
        color: #0f172a;
        font-family: var(--panel-font-base);
        font-size: .92rem;
        font-weight: 600;
        cursor: pointer;
    }

    body.dashboard-modern .maintenance-date-picker-day:hover {
        background: #eef4ff;
        border-color: #bfd4ff;
    }

    body.dashboard-modern .maintenance-date-picker-day--outside {
        color: #94a3b8;
        background: #fbfdff;
    }

    body.dashboard-modern .maintenance-date-picker-day--disabled,
    body.dashboard-modern .maintenance-date-picker-day:disabled {
        color: #cbd5e1;
        background: #f8fafc;
        border-color: transparent;
        cursor: not-allowed;
        box-shadow: none;
        opacity: .7;
    }

    body.dashboard-modern .maintenance-date-picker-day--disabled:hover,
    body.dashboard-modern .maintenance-date-picker-day:disabled:hover {
        background: #f8fafc;
        border-color: transparent;
    }

    body.dashboard-modern .maintenance-date-picker-day--today {
        border-color: #93c5fd;
        box-shadow: inset 0 0 0 1px rgba(37, 99, 235, .12);
    }

    body.dashboard-modern .maintenance-date-picker-day--selected {
        background: #2563eb;
        color: #fff;
        border-color: #2563eb;
        box-shadow: 0 10px 18px rgba(37, 99, 235, .18);
    }

    body.dashboard-modern .maintenance-date-picker-footer {
        display: flex;
        justify-content: space-between;
        gap: 10px;
        flex-wrap: wrap;
    }

    body.dashboard-modern .maintenance-date-picker .btn.btn-ghost {
        background: #fff;
        color: #334155;
    }

    body.dashboard-modern .maintenance-modal-select {
        font-family: var(--panel-font-base);
        appearance: none;
        background-image:
            linear-gradient(45deg, transparent 50%, #64748b 50%),
            linear-gradient(135deg, #64748b 50%, transparent 50%),
            linear-gradient(to right, transparent, transparent);
        background-position:
            calc(100% - 18px) calc(1em + 1px),
            calc(100% - 13px) calc(1em + 1px),
            100% 0;
        background-size:
            5px 5px,
            5px 5px,
            2.5em 2.5em;
        background-repeat: no-repeat;
        padding-right: 42px;
    }

    body.dashboard-modern .maintenance-upload {
        display: grid;
        gap: 10px;
    }

    body.dashboard-modern .maintenance-upload-stage {
        position: relative;
        min-height: 132px;
    }

    body.dashboard-modern .maintenance-upload-input {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        opacity: 0;
        cursor: pointer;
        z-index: 2;
    }

    body.dashboard-modern .maintenance-upload-card {
        display: grid;
        grid-template-columns: auto 1fr;
        gap: 14px;
        align-items: center;
        min-height: 132px;
        padding: 18px 18px 18px 16px;
        border-radius: 18px;
        border: 1px dashed #bfd4ff;
        background: linear-gradient(180deg, #fbfdff 0%, #f6f9ff 100%);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, .85);
        transition: border-color .18s ease, box-shadow .18s ease, transform .18s ease, background .18s ease;
    }

    body.dashboard-modern .maintenance-upload-card:hover {
        transform: translateY(-1px);
        border-color: #93c5fd;
    }

    body.dashboard-modern .maintenance-upload-input:focus-visible + .maintenance-upload-card,
    body.dashboard-modern .maintenance-upload-stage:focus-within .maintenance-upload-card {
        outline: none;
        border-color: #93c5fd;
        box-shadow: 0 0 0 4px rgba(37, 99, 235, .12), inset 0 1px 0 rgba(255, 255, 255, .85);
    }

    body.dashboard-modern .maintenance-upload--dragover .maintenance-upload-card {
        border-color: #2563eb;
        background: #f8fbff;
        box-shadow: 0 0 0 4px rgba(37, 99, 235, .12);
    }

    body.dashboard-modern .maintenance-upload--has-file .maintenance-upload-card {
        border-style: solid;
        background: #f8fbff;
    }

    body.dashboard-modern .maintenance-upload-icon {
        width: 48px;
        height: 48px;
        border-radius: 16px;
        display: inline-grid;
        place-items: center;
        background: #eef4ff;
        color: #1d4ed8;
        flex: none;
    }

    body.dashboard-modern .maintenance-upload-icon svg {
        width: 24px;
        height: 24px;
    }

    body.dashboard-modern .maintenance-upload-copy strong,
    body.dashboard-modern .maintenance-upload-copy span,
    body.dashboard-modern .maintenance-upload-copy small {
        display: block;
    }

    body.dashboard-modern .maintenance-upload-copy strong {
        color: #0f172a;
        font-size: .98rem;
        font-weight: 700;
    }

    body.dashboard-modern .maintenance-upload-copy span {
        margin-top: 3px;
        color: #64748b;
        font-size: .92rem;
    }

    body.dashboard-modern .maintenance-upload-copy small {
        margin-top: 6px;
        color: #94a3b8;
        font-size: .78rem;
        font-weight: 600;
        letter-spacing: .02em;
    }

    body.dashboard-modern .maintenance-upload-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }

    body.dashboard-modern .maintenance-upload-name {
        color: #334155;
        font-size: .92rem;
        line-height: 1.4;
        word-break: break-word;
    }

    body.dashboard-modern .maintenance-upload-clear {
        white-space: nowrap;
    }

    @media (max-width: 1024px) {
        body.dashboard-modern .filters {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        body.dashboard-modern .panel-grid--two,
        body.dashboard-modern .config-inline-form-row,
        body.dashboard-modern .config-reversion-summary,
        body.dashboard-modern .config-revert-grid {
            grid-template-columns: 1fr;
        }

    }

    @media (max-width: 720px) {
        body.dashboard-modern .wrap {
            padding: 20px 14px 40px;
        }

        body.dashboard-modern .card {
            padding: 18px;
            border-radius: 22px;
        }

        body.dashboard-modern .filters {
            grid-template-columns: 1fr;
        }

        body.dashboard-modern .maintenance-upload-card {
            grid-template-columns: 1fr;
            min-height: 0;
        }

        body.dashboard-modern .maintenance-upload-footer {
            align-items: flex-start;
        }

        body.dashboard-modern .panel-navigation .btn-link {
            flex: 1 1 calc(50% - 10px);
            justify-content: center;
        }

        body.dashboard-modern .config-tech-item,
        body.dashboard-modern .config-empty-state,
        body.dashboard-modern .config-empty-callout,
        body.dashboard-modern .config-reversion-summary,
        body.dashboard-modern .config-revert-note {
            border-radius: 16px;
        }
    }
</style>
