<style>
    body.dashboard-modern .users-toolbar {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 14px;
        flex-wrap: wrap;
        margin-bottom: 14px;
    }

    body.dashboard-modern .users-toolbar-actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        align-items: center;
    }

    body.dashboard-modern .users-toolbar--integrated {
        margin-top: 14px;
        padding-top: 16px;
        border-top: 1px solid #dfe7f2;
    }

    body.dashboard-modern .users-module-shell {
        margin-bottom: 18px;
        border-radius: 20px;
        border-left-color: #dfe7f2;
        border-right-color: #dfe7f2;
        box-shadow: 0 18px 42px rgba(15, 23, 42, .055);
    }

    body.dashboard-modern .users-module-shell--head {
        border-bottom: 1px solid #dfe7f2;
        border-top-left-radius: 20px;
        border-top-right-radius: 20px;
    }

    body.dashboard-modern .users-module-shell--table {
        margin-top: 0;
        border-top-left-radius: 20px;
        border-top-right-radius: 20px;
        border-bottom-left-radius: 20px;
        border-bottom-right-radius: 20px;
        padding-top: 18px;
    }

    body.dashboard-modern .users-filter-strip {
        margin-top: 14px;
        padding: 16px;
        border-radius: 20px;
        background: #fff;
        border: 1px solid #dfe7f2;
        box-shadow: 0 12px 32px rgba(15, 23, 42, .04);
    }

    body.dashboard-modern .users-group {
        display: grid;
        gap: 10px;
        margin-top: 18px;
    }

    body.dashboard-modern .users-group-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 12px 14px;
        border-radius: 18px;
        background: #f8fafc;
        border: 1px solid #dfe7f2;
    }

    body.dashboard-modern .users-group-title {
        margin: 0;
        font-size: .96rem;
        color: #0f172a;
        font-weight: 800;
    }

    body.dashboard-modern .users-group-count {
        color: #64748b;
        font-size: .82rem;
        font-weight: 700;
    }

    body.dashboard-modern .users-group-toggle {
        display: inline-flex;
        align-items: center;
        justify-content: flex-start;
        gap: 10px;
        width: 100%;
        cursor: pointer;
        color: inherit;
        text-align: left;
        background: transparent;
        border: 0;
        padding: 0;
    }

    body.dashboard-modern .users-group-toggle::before {
        content: '';
        width: 10px;
        height: 10px;
        border-radius: 4px;
        background: linear-gradient(135deg, #dbeafe, #60a5fa);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, .08);
        flex: none;
    }

    body.dashboard-modern .users-group-toggle-main {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        min-width: 0;
    }

    body.dashboard-modern .users-group-caret {
        width: 0;
        height: 0;
        border-left: 5px solid transparent;
        border-right: 5px solid transparent;
        border-top: 6px solid #64748b;
        transition: transform .18s ease;
        flex: none;
    }

    body.dashboard-modern .users-group-shell[data-open='true'] .users-group-caret {
        transform: rotate(180deg);
    }

    body.dashboard-modern .users-group-shell[data-open='false'] .user-record {
        display: none;
    }

    body.dashboard-modern .user-row-name {
        display: block;
        color: #0f172a;
        font-weight: 600;
    }

    body.dashboard-modern .user-table tbody tr:hover {
        background: #f8fbff;
    }

    body.dashboard-modern .user-record td {
        transition: background .16s ease;
    }

    body.dashboard-modern .user-badge-type,
    body.dashboard-modern .user-badge-state,
    body.dashboard-modern .user-badge-area {
        display: inline-flex;
        align-items: center;
        padding: 6px 10px;
        border-radius: 999px;
        font-size: .76rem;
        font-weight: 700;
        line-height: 1;
    }

    body.dashboard-modern .user-badge-type--normal {
        background: rgba(59, 130, 246, .12);
        border: 1px solid rgba(59, 130, 246, .18);
        color: #1d4ed8;
    }

    body.dashboard-modern .user-badge-type--nuevo {
        background: rgba(16, 185, 129, .12);
        border: 1px solid rgba(16, 185, 129, .18);
        color: #047857;
    }

    body.dashboard-modern .user-badge-state--activo {
        background: rgba(16, 185, 129, .12);
        border: 1px solid rgba(16, 185, 129, .18);
        color: #047857;
    }

    body.dashboard-modern .user-badge-state--inactivo {
        background: rgba(239, 68, 68, .10);
        border: 1px solid rgba(239, 68, 68, .18);
        color: #b91c1c;
    }

    body.dashboard-modern .user-badge-area {
        background: #eff6ff;
        border: 1px solid #dbeafe;
        color: #1d4ed8;
    }

    body.dashboard-modern .user-actions {
        position: relative;
        display: inline-flex;
    }

    body.dashboard-modern .user-actions-trigger {
        width: 40px;
        height: 40px;
        border-radius: 12px;
        border: 1px solid #dfe7f2;
        background: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 4px;
        cursor: pointer;
        transition: transform .16s ease, box-shadow .16s ease, border-color .16s ease;
        box-shadow: 0 8px 18px rgba(15, 23, 42, .06);
    }

    body.dashboard-modern .user-actions-trigger:hover,
    body.dashboard-modern .user-actions-trigger:focus-visible {
        transform: translateY(-1px);
        border-color: #bfd4ff;
        outline: none;
    }

    body.dashboard-modern .user-actions-trigger:focus-visible {
        box-shadow: 0 0 0 3px rgba(37, 99, 235, .12), 0 8px 18px rgba(15, 23, 42, .06);
    }

    body.dashboard-modern .user-actions-trigger span {
        width: 4px;
        height: 4px;
        border-radius: 999px;
        background: #475569;
        display: block;
    }

    body.dashboard-modern .user-actions-panel {
        position: absolute;
        right: 0;
        bottom: calc(100% + 8px);
        min-width: 220px;
        padding: 12px;
        border-radius: 18px;
        background: #fff;
        border: 1px solid #dfe7f2;
        box-shadow: 0 20px 50px rgba(15, 23, 42, .12);
        display: grid;
        gap: 8px;
        z-index: 80;
    }

    body.dashboard-modern .user-action-item {
        display: block;
        width: 100%;
        text-align: left;
        padding: 11px 12px;
        border-radius: 14px;
        background: #f8fafc;
        border: 0;
        color: #0f172a;
        text-decoration: none;
        cursor: pointer;
        font-weight: 700;
        transition: background .16s ease, transform .16s ease;
    }

    body.dashboard-modern .user-action-item:hover {
        background: #eef4ff;
        transform: translateX(2px);
    }

    body.dashboard-modern .user-action-item--danger {
        color: #b91c1c;
    }

    body.dashboard-modern .user-modal-backdrop {
        position: absolute;
        inset: 0;
        background: rgba(15, 23, 42, .58);
        z-index: 0;
    }

    body.dashboard-modern .user-modal-shell {
        position: fixed;
        inset: 0;
        z-index: 100;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 24px;
        isolation: isolate;
    }

    body.dashboard-modern .user-modal-panel,
    body.dashboard-modern .user-modal-dialog {
        position: relative;
        z-index: 1;
        width: min(100%, 920px);
        max-height: calc(100vh - 48px);
        overflow: auto;
        background: #fff;
        border: 1px solid #dfe7f2;
        border-radius: 22px;
        box-shadow: 0 30px 80px rgba(15, 23, 42, .2);
        padding: 22px;
    }

    body.dashboard-modern .user-modal-header {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        align-items: flex-start;
        margin-bottom: 18px;
    }

    body.dashboard-modern .user-modal-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
    }

    body.dashboard-modern .user-modal-grid .field-wide {
        grid-column: 1 / -1;
    }

    body.dashboard-modern .user-modal-grid .field-actions {
        grid-column: 1 / -1;
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        justify-content: flex-end;
    }

    body.dashboard-modern .user-modal-grid label {
        margin-bottom: 0;
    }

    body.dashboard-modern .user-modal-grid input,
    body.dashboard-modern .user-modal-grid select {
        margin-top: 6px;
    }

    body.dashboard-modern .group-row td {
        background: #f8fafc;
        border-top: 1px solid #e2e8f0;
        border-bottom: 1px solid #e2e8f0;
        padding: 0;
    }

    body.dashboard-modern .group-row td .users-group-header {
        margin: 12px 14px;
    }

    body.dashboard-modern .user-table tbody tr.user-record:hover td {
        background: #f8fbff;
    }

    @media (max-width: 720px) {
        body.dashboard-modern .user-modal-shell {
            padding: 12px;
        }

        body.dashboard-modern .user-modal-grid {
            grid-template-columns: 1fr;
        }

        body.dashboard-modern .user-modal-grid .field-wide,
        body.dashboard-modern .user-modal-grid .field-actions {
            grid-column: 1 / -1;
        }
    }
</style>
