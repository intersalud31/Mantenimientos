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
        display: none;
    }

    body.dashboard-modern .user-table {
        border-collapse: separate;
        border-spacing: 0;
        background: #fff;
    }

    body.dashboard-modern .user-table th:nth-child(5),
    body.dashboard-modern .user-table td:nth-child(5) {
        width: 190px;
        min-width: 190px;
    }

    body.dashboard-modern .users-group-shell {
        position: relative;
    }

    body.dashboard-modern .users-group-title {
        margin: 0;
        color: #0f172a;
        font-size: .92rem;
        font-weight: 700;
        line-height: 1.2;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    body.dashboard-modern .users-group-count {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-left: auto;
        padding: 3px 8px;
        border-radius: 999px;
        background: #f3f6fb;
        border: 1px solid #e1e8f0;
        color: #526072;
        font-size: .74rem;
        font-weight: 700;
        line-height: 1;
        white-space: nowrap;
        flex: none;
    }

    body.dashboard-modern .users-group-toggle {
        display: block;
        width: 100%;
        min-height: 46px;
        padding: 0 14px;
        cursor: pointer;
        color: inherit;
        text-align: left;
        background: transparent;
        border: 0;
        border-radius: 0;
        box-shadow: none;
        transition: background-color .14s ease;
    }

    body.dashboard-modern .users-group-toggle:hover {
        background: rgba(15, 23, 42, .018);
    }

    body.dashboard-modern .users-group-toggle:focus-visible {
        outline: none;
        background: rgba(37, 99, 235, .05);
        box-shadow: inset 0 0 0 1px rgba(37, 99, 235, .22);
    }

    body.dashboard-modern .users-group-toggle::before {
        content: none;
    }

    body.dashboard-modern .users-group-toggle-main {
        display: flex;
        align-items: center;
        gap: 10px;
        width: 100%;
        min-height: 46px;
        min-width: 0;
    }

    body.dashboard-modern .users-group-toggle-main > span:last-child {
        display: flex;
        align-items: center;
        gap: 10px;
        width: 100%;
        min-width: 0;
    }

    body.dashboard-modern .users-group-caret {
        position: relative;
        width: 14px;
        height: 14px;
        border: 0;
        flex: none;
    }

    body.dashboard-modern .users-group-caret::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 6px;
        height: 6px;
        border-right: 1.5px solid #6b7a90;
        border-bottom: 1.5px solid #6b7a90;
        transform: translate(-58%, -50%) rotate(-45deg);
        transition: transform .18s ease, border-color .18s ease;
    }

    body.dashboard-modern .users-group-shell[data-open='true'] .users-group-caret::before {
        transform: translate(-50%, -62%) rotate(45deg);
        border-color: #315ea8;
    }

    body.dashboard-modern .users-group-shell[data-open='true'] .users-group-toggle {
        background: #f7faff;
    }

    body.dashboard-modern .users-group-shell[data-open='true'] .users-group-count {
        background: #eaf1ff;
        border-color: #d5e2fb;
        color: #315ea8;
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
        white-space: nowrap;
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

    body.dashboard-modern .users-group-shell .group-row + .user-record .user-actions-panel {
        top: calc(100% + 8px);
        bottom: auto;
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

    body.dashboard-modern .user-modal-grid .user-maintenance-base-field {
        position: relative;
    }

    body.dashboard-modern .user-modal-grid .user-maintenance-base-field > label {
        display: block;
        margin-bottom: 6px;
        font-weight: 600;
        color: #334155;
    }

    body.dashboard-modern .user-native-date-input {
        position: absolute;
        inset: auto 0 0 0;
        width: 100%;
        height: 54px;
        margin: 0;
        opacity: 0;
        cursor: pointer;
        z-index: 2;
        border: 0;
    }

    body.dashboard-modern .user-native-date-input::-webkit-calendar-picker-indicator {
        width: 100%;
        height: 100%;
        margin: 0;
        opacity: 0;
        cursor: pointer;
    }

    body.dashboard-modern .user-modal-grid [aria-invalid='true'] {
        border-color: rgba(220, 38, 38, .42);
        box-shadow: 0 0 0 3px rgba(220, 38, 38, .08);
    }

    body.dashboard-modern .field-error {
        display: block;
        margin-top: 6px;
        color: #b91c1c;
        font-size: .82rem;
        font-weight: 600;
    }

    body.dashboard-modern .group-row td {
        padding: 0;
        background: #fff;
        border-top: 1px solid #e7edf5;
        border-bottom: 1px solid #e7edf5;
    }

    body.dashboard-modern .users-group-shell:first-of-type .group-row td {
        border-top: 0;
    }

    body.dashboard-modern .users-group-shell[data-open='true'] .group-row td {
        background: #f7faff;
        border-bottom-color: #edf2f8;
    }

    body.dashboard-modern .group-row td .users-group-header {
        margin: 0;
        display: none;
    }

    body.dashboard-modern .group-row td .users-group-toggle {
        margin: 0;
    }

    body.dashboard-modern .users-group-shell[data-open='true'] .user-record:first-of-type td {
        border-top-color: #edf2f8;
    }

    body.dashboard-modern .users-group-shell[data-open='true'] .user-record td {
        background: #fff;
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

        body.dashboard-modern .users-group-toggle {
            padding: 0 12px;
        }

        body.dashboard-modern .users-group-toggle-main {
            min-height: 44px;
            gap: 8px;
        }

        body.dashboard-modern .users-group-toggle-main > span:last-child {
            gap: 8px;
        }

        body.dashboard-modern .users-group-title {
            font-size: .88rem;
        }

        body.dashboard-modern .users-group-count {
            padding: 3px 7px;
            font-size: .7rem;
        }
    }
</style>
