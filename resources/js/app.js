document.addEventListener('DOMContentLoaded', () => {
    const appSidebar = document.querySelector('[data-app-sidebar]');
    const appSidebarBackdrop = document.querySelector('[data-app-sidebar-backdrop]');
    const appSidebarToggle = document.querySelector('[data-app-sidebar-toggle]');
    const appSidebarClose = document.querySelector('[data-app-sidebar-close]');
    const appNavGroups = Array.from(document.querySelectorAll('[data-app-nav-group]'));

    if (appSidebar && appSidebarToggle) {
        const desktopQuery = window.matchMedia('(min-width: 981px)');

        const setMobileOpen = (open) => {
            document.body.classList.toggle('app-sidebar-mobile-open', open);
            appSidebarToggle.setAttribute('aria-expanded', open ? 'true' : 'false');

            if (appSidebarBackdrop) {
                appSidebarBackdrop.hidden = !open;
            }
        };

        const setDesktopCollapsed = (collapsed) => {
            document.body.classList.toggle('app-sidebar-collapsed', collapsed);
            appSidebarToggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
        };

        const closeMobileSidebar = () => setMobileOpen(false);

        appSidebarToggle.addEventListener('click', (event) => {
            event.preventDefault();

            if (desktopQuery.matches) {
                setDesktopCollapsed(!document.body.classList.contains('app-sidebar-collapsed'));
                return;
            }

            setMobileOpen(!document.body.classList.contains('app-sidebar-mobile-open'));
        });

        appSidebarClose?.addEventListener('click', (event) => {
            event.preventDefault();
            closeMobileSidebar();
        });

        appSidebarBackdrop?.addEventListener('click', closeMobileSidebar);

        appSidebar.querySelectorAll('[data-app-sidebar-link]').forEach((link) => {
            link.addEventListener('click', () => {
                if (!desktopQuery.matches) {
                    closeMobileSidebar();
                }
            });
        });

        appNavGroups.forEach((group) => {
            const toggle = group.querySelector('[data-app-nav-toggle]');
            const panel = group.querySelector('[data-app-nav-panel]');

            if (!toggle || !panel) {
                return;
            }

            const setOpen = (open) => {
                toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
                panel.hidden = !open;
                group.classList.toggle('app-nav-group--open', open);
            };

            setOpen(toggle.getAttribute('aria-expanded') === 'true');

            toggle.addEventListener('click', (event) => {
                event.preventDefault();

                if (desktopQuery.matches && document.body.classList.contains('app-sidebar-collapsed')) {
                    setDesktopCollapsed(false);
                    setOpen(true);
                    return;
                }

                setOpen(toggle.getAttribute('aria-expanded') !== 'true');
            });
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeMobileSidebar();
            }
        });

        const syncResponsiveSidebar = () => {
            closeMobileSidebar();
            if (!desktopQuery.matches) {
                document.body.classList.remove('app-sidebar-collapsed');
            }
        };

        if (typeof desktopQuery.addEventListener === 'function') {
            desktopQuery.addEventListener('change', syncResponsiveSidebar);
        } else if (typeof desktopQuery.addListener === 'function') {
            desktopQuery.addListener(syncResponsiveSidebar);
        }

        setMobileOpen(false);
        setDesktopCollapsed(document.body.classList.contains('app-sidebar-collapsed'));
    }

    const topbar = document.querySelector('[data-topbar]');

    if (topbar) {
        const menus = Array.from(topbar.querySelectorAll('[data-topbar-menu]'));

        const closeMenu = (menu) => {
            const button = menu.querySelector('[data-topbar-toggle]');
            const panel = menu.querySelector('[data-topbar-panel]');

            if (button) {
                button.setAttribute('aria-expanded', 'false');
            }

            if (panel) {
                panel.hidden = true;
            }

            menu.classList.remove('menu-group--open');
        };

        const closeAllMenus = () => menus.forEach(closeMenu);

        const openMenu = (menu) => {
            menus.forEach((other) => {
                if (other !== menu) {
                    closeMenu(other);
                }
            });

            const button = menu.querySelector('[data-topbar-toggle]');
            const panel = menu.querySelector('[data-topbar-panel]');

            if (button) {
                button.setAttribute('aria-expanded', 'true');
            }

            if (panel) {
                panel.hidden = false;
            }

            menu.classList.add('menu-group--open');
        };

        menus.forEach((menu) => {
            const button = menu.querySelector('[data-topbar-toggle]');
            const panel = menu.querySelector('[data-topbar-panel]');

            if (!button || !panel) {
                return;
            }

            button.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();

                const isOpen = button.getAttribute('aria-expanded') === 'true';

                if (isOpen) {
                    closeMenu(menu);
                    return;
                }

                openMenu(menu);
            });
        });

        document.addEventListener('click', (event) => {
            if (!topbar.contains(event.target)) {
                closeAllMenus();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeAllMenus();
            }
        });
    }

    const panelActionMenuSelector = '[data-panel-action-menu]';
    const panelActionMenuTriggerSelector = '[data-panel-action-menu-trigger]';
    const panelActionMenuPanelSelector = '[data-panel-action-menu-panel]';
    const panelActionMenuItemSelector = '[data-panel-action-menu-item]';

    let activePanelActionMenu = null;

    const getPanelActionMenuElements = (menu) => {
        if (!menu) {
            return {
                trigger: null,
                panel: null,
            };
        }

        return {
            trigger: menu.querySelector(panelActionMenuTriggerSelector),
            panel: menu.querySelector(panelActionMenuPanelSelector),
        };
    };

    const getPanelActionMenuItems = (menu) => Array.from(
        menu.querySelectorAll(panelActionMenuItemSelector),
    ).filter((item) => item.getAttribute('aria-disabled') !== 'true' && !item.hidden);

    const clamp = (value, min, max) => Math.min(Math.max(value, min), max);

    const closePanelActionMenus = (focusTrigger = false) => {
        document.querySelectorAll(panelActionMenuSelector).forEach((menu) => {
            const { trigger, panel } = getPanelActionMenuElements(menu);

            if (trigger) {
                trigger.setAttribute('aria-expanded', 'false');
            }

            if (panel) {
                panel.hidden = true;
                panel.style.left = '';
                panel.style.top = '';
                panel.style.width = '';
                panel.style.visibility = '';
            }

            menu.classList.remove('panel-action-menu--open');
        });

        if (focusTrigger && activePanelActionMenu) {
            const { trigger } = getPanelActionMenuElements(activePanelActionMenu);
            trigger?.focus();
        }

        activePanelActionMenu = null;
    };

    const positionPanelActionMenu = (menu) => {
        const { trigger, panel } = getPanelActionMenuElements(menu);

        if (!trigger || !panel || panel.hidden) {
            return;
        }

        panel.style.visibility = 'hidden';
        panel.style.left = '0px';
        panel.style.top = '0px';
        panel.style.width = '';

        const triggerRect = trigger.getBoundingClientRect();
        const panelRect = panel.getBoundingClientRect();
        const viewportWidth = window.innerWidth;
        const viewportHeight = window.innerHeight;
        const menuWidth = Math.min(Math.max(panelRect.width || 220, 220), viewportWidth - 24);
        const menuHeight = panelRect.height || 0;
        const isStartAligned = menu.classList.contains('panel-action-menu--start');
        const spaceBelow = viewportHeight - triggerRect.bottom - 12;
        const spaceAbove = triggerRect.top - 12;
        const openAbove = menuHeight > 0 && spaceBelow < menuHeight && spaceAbove > spaceBelow;
        const top = openAbove
            ? clamp(triggerRect.top - 8 - menuHeight, 12, Math.max(12, viewportHeight - menuHeight - 12))
            : clamp(triggerRect.bottom + 8, 12, Math.max(12, viewportHeight - menuHeight - 12));
        const leftAnchor = isStartAligned ? triggerRect.left : triggerRect.right - menuWidth;
        const left = clamp(leftAnchor, 12, Math.max(12, viewportWidth - menuWidth - 12));

        panel.style.width = `${menuWidth}px`;
        panel.style.left = `${left}px`;
        panel.style.top = `${top}px`;
        panel.dataset.panelPlacement = openAbove ? 'top' : 'bottom';
        panel.style.visibility = '';
    };

    const focusPanelActionMenuItem = (menu, direction = 0) => {
        const items = getPanelActionMenuItems(menu);

        if (!items.length) {
            return;
        }

        let index = 0;
        const currentIndex = items.indexOf(document.activeElement);

        if (direction === 0) {
            index = currentIndex >= 0 ? currentIndex : 0;
        } else if (direction > 0) {
            index = currentIndex >= 0 ? (currentIndex + 1) % items.length : 0;
        } else {
            index = currentIndex >= 0 ? (currentIndex - 1 + items.length) % items.length : items.length - 1;
        }

        items[index]?.focus();
    };

    const openPanelActionMenu = (menu, focusDirection = null) => {
        const { trigger, panel } = getPanelActionMenuElements(menu);

        if (!trigger || !panel) {
            return;
        }

        closePanelActionMenus(false);

        activePanelActionMenu = menu;
        trigger.setAttribute('aria-expanded', 'true');
        panel.hidden = false;
        panel.style.left = '';
        panel.style.top = '';
        panel.style.width = '';
        panel.style.visibility = '';
        menu.classList.add('panel-action-menu--open');

        if (focusDirection !== null) {
            focusPanelActionMenuItem(menu, focusDirection);
        }
    };

    const togglePanelActionMenu = (menu) => {
        const { trigger } = getPanelActionMenuElements(menu);
        const isOpen = trigger?.getAttribute('aria-expanded') === 'true';

        if (isOpen) {
            closePanelActionMenus(true);
            return;
        }

        openPanelActionMenu(menu);
    };

    document.addEventListener('click', (event) => {
        const trigger = event.target.closest(panelActionMenuTriggerSelector);

        if (trigger) {
            event.preventDefault();
            event.stopPropagation();
            const menu = trigger.closest(panelActionMenuSelector);

            if (!menu) {
                return;
            }

            togglePanelActionMenu(menu);
            return;
        }

        if (event.target.closest(panelActionMenuItemSelector)) {
            window.setTimeout(() => closePanelActionMenus(false), 0);
            return;
        }

        if (!event.target.closest(panelActionMenuSelector)) {
            closePanelActionMenus(false);
        }
    });

    document.addEventListener('keydown', (event) => {
        const trigger = event.target.closest(panelActionMenuTriggerSelector);

        if (trigger) {
            const menu = trigger.closest(panelActionMenuSelector);

            if (!menu) {
                return;
            }

            if (event.key === 'Escape') {
                event.preventDefault();
                closePanelActionMenus(true);
                return;
            }

            if (event.key === 'Enter' || event.key === ' ' || event.key === 'Spacebar') {
                event.preventDefault();
                togglePanelActionMenu(menu);
                return;
            }

            if (event.key === 'ArrowDown' || event.key === 'ArrowUp' || event.key === 'Home' || event.key === 'End') {
                event.preventDefault();
                openPanelActionMenu(menu, event.key === 'ArrowUp' || event.key === 'End' ? -1 : 0);
            }

            return;
        }

        const panel = event.target.closest(panelActionMenuPanelSelector);

        if (!panel) {
            if (event.key === 'Escape') {
                closePanelActionMenus(true);
            }

            return;
        }

        const menu = panel.closest(panelActionMenuSelector);

        if (!menu) {
            return;
        }

        const items = getPanelActionMenuItems(menu);

        if (!items.length) {
            return;
        }

        const currentIndex = items.indexOf(document.activeElement);

        if (event.key === 'Escape') {
            event.preventDefault();
            closePanelActionMenus(true);
            return;
        }

        if (event.key === 'ArrowDown') {
            event.preventDefault();
            items[(currentIndex + 1) % items.length]?.focus();
            return;
        }

        if (event.key === 'ArrowUp') {
            event.preventDefault();
            items[(currentIndex - 1 + items.length) % items.length]?.focus();
            return;
        }

        if (event.key === 'Home') {
            event.preventDefault();
            items[0]?.focus();
            return;
        }

        if (event.key === 'End') {
            event.preventDefault();
            items[items.length - 1]?.focus();
        }
    });

    window.addEventListener('scroll', () => {
        if (activePanelActionMenu) {
            closePanelActionMenus(false);
        }
    });

    window.addEventListener('resize', () => {
        if (activePanelActionMenu) {
            closePanelActionMenus(false);
        }
    });

    const initializeUserGroupToggles = (scope = document) => {
        scope.querySelectorAll('[data-users-group-shell]').forEach((shell) => {
            if (shell.dataset.usersGroupBound === 'true') {
                return;
            }

            const toggle = shell.querySelector('[data-users-group-toggle]');

            if (!toggle) {
                return;
            }

            const setOpen = (open) => {
                shell.dataset.open = open ? 'true' : 'false';
                toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            };

            setOpen(shell.dataset.open !== 'false');

            toggle.addEventListener('click', (event) => {
                event.preventDefault();
                setOpen(shell.dataset.open !== 'true');
            });

            shell.dataset.usersGroupBound = 'true';
        });
    };

    initializeUserGroupToggles();

    let refreshInteractiveBindings = () => {
        closePanelActionMenus(false);
        initializeUserGroupToggles();
    };

    const setupLivePanelFilters = () => {
        document.querySelectorAll('[data-panel-live-search="true"], [data-panel-filter-live="true"]').forEach((form) => {
            const targetSelector = form.dataset.panelLiveSearchTarget || form.dataset.panelFilterLiveTarget || '';
            let target = targetSelector ? document.querySelector(targetSelector) : null;

            if (!target) {
                return;
            }

            const loading = form.querySelector('[data-panel-live-search-loading], [data-panel-filter-loading]');
            const searchInput = form.querySelector('[data-panel-live-search-input], [data-panel-filter-search]');
            const liveFields = Array.from(form.querySelectorAll('[data-panel-filter-live-field], [data-panel-live-search-input], [data-panel-filter-search]'));
            const submitButton = form.querySelector('button[type="submit"]');
            const debounceDelay = Math.max(0, parseInt(form.dataset.panelLiveSearchDebounce || form.dataset.panelFilterDebounce || '500', 10) || 500);
            let controller = null;
            let debounceTimer = null;
            let sequence = 0;

            const setLoading = (isLoading) => {
                if (loading) {
                    loading.hidden = !isLoading;
                }

                if (submitButton) {
                    submitButton.disabled = isLoading;
                }
            };

            const buildRequestUrl = (sourceUrl = null) => {
                if (sourceUrl) {
                    return new URL(sourceUrl, window.location.origin);
                }

                const url = new URL(form.getAttribute('action') || window.location.href, window.location.origin);
                const formData = new FormData(form);
                url.search = new URLSearchParams(formData).toString();
                return url;
            };

            const syncUrl = (url) => {
                window.history.replaceState({}, '', `${url.pathname}${url.search}${url.hash}`);
            };

            const replaceTarget = (html) => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const nextTarget = targetSelector ? doc.querySelector(targetSelector) : null;

                if (!nextTarget) {
                    return false;
                }

                target.outerHTML = nextTarget.outerHTML;
                target = targetSelector ? document.querySelector(targetSelector) : null;
                return true;
            };

            const runFetch = async (sourceUrl = null) => {
                const requestUrl = buildRequestUrl(sourceUrl);
                const currentSequence = ++sequence;

                if (controller) {
                    controller.abort();
                }

                controller = new AbortController();
                setLoading(true);

                try {
                    const response = await fetch(requestUrl.toString(), {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Cache-Control': 'no-cache',
                        },
                        signal: controller.signal,
                    });

                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}`);
                    }

                    const html = await response.text();

                    if (currentSequence !== sequence) {
                        return;
                    }

                    if (replaceTarget(html)) {
                        refreshInteractiveBindings();
                        syncUrl(requestUrl);
                    }
                } catch (error) {
                    if (error && error.name === 'AbortError') {
                        return;
                    }

                    console.error('No se pudo actualizar el filtro en vivo.', error);
                } finally {
                    if (currentSequence === sequence) {
                        setLoading(false);
                    }
                }
            };

            const scheduleFetch = () => {
                window.clearTimeout(debounceTimer);
                debounceTimer = window.setTimeout(() => {
                    runFetch();
                }, debounceDelay);
            };

            if (searchInput) {
                searchInput.addEventListener('input', () => {
                    scheduleFetch();
                });

                searchInput.addEventListener('keydown', (event) => {
                    if (event.key === 'Enter') {
                        event.preventDefault();
                        window.clearTimeout(debounceTimer);
                        runFetch();
                    }
                });
            }

            liveFields.forEach((field) => {
                if (field === searchInput) {
                    return;
                }

                field.addEventListener('change', () => {
                    scheduleFetch();
                });
            });

            form.addEventListener('submit', (event) => {
                event.preventDefault();
                window.clearTimeout(debounceTimer);
                runFetch();
            });

            document.addEventListener('click', (event) => {
                const link = event.target.closest(`${targetSelector} nav[role="navigation"] a[href]`);

                if (!link) {
                    return;
                }

                const linkUrl = link.getAttribute('href');
                if (!linkUrl) {
                    return;
                }

                event.preventDefault();
                window.clearTimeout(debounceTimer);
                runFetch(linkUrl);
            });
        });
    };

    setupLivePanelFilters();

    const setupModal = (options) => {
        const modal = document.querySelector(options.modalSelector);

        if (!modal) {
            return;
        }

        if (modal.dataset.modalBound === 'true') {
            return;
        }

        const openButtons = document.querySelectorAll(options.openSelector);
        const closeButtons = modal.querySelectorAll(options.closeSelector);
        const idInput = options.idInputSelector ? modal.querySelector(options.idInputSelector) : null;
        const summary = options.summarySelector ? modal.querySelector(options.summarySelector) : null;
        const dateInput = modal.querySelector('[data-maintenance-date-input]');
        const programmedDateDisplay = modal.querySelector('[data-maintenance-programmed-date-display]');
        const form = modal.querySelector('form');
        const submitButton = form ? form.querySelector('button[type="submit"]') : null;
        const submitButtonLabel = submitButton ? submitButton.textContent || '' : '';
        const closeUrl = modal.dataset.modalCloseUrl || '';
        const fileDropzone = modal.querySelector('[data-maintenance-file-dropzone]');
        const dateField = modal.querySelector('[data-maintenance-date-field]');
        const dateTrigger = modal.querySelector('[data-maintenance-date-trigger]');
        const dateDisplay = modal.querySelector('[data-maintenance-date-display]');
        const datePicker = modal.querySelector('[data-maintenance-date-picker]');
        const dateMonthLabel = modal.querySelector('[data-maintenance-date-month]');
        const dateYearSelect = modal.querySelector('[data-maintenance-date-year]');
        const dateGrid = modal.querySelector('[data-maintenance-date-grid]');
        const datePrev = modal.querySelector('[data-maintenance-date-prev]');
        const dateNext = modal.querySelector('[data-maintenance-date-next]');
        const dateToday = modal.querySelector('[data-maintenance-date-today]');
        const dateClose = modal.querySelector('[data-maintenance-date-close]');
        const shouldPreserveMaintenanceDate = dateInput?.dataset.maintenanceDatePreserve === '1';
        const hasMaintenanceDatePicker = Boolean(dateField && dateTrigger && dateDisplay && datePicker && dateMonthLabel && dateYearSelect && dateGrid);
        let fileDropzoneInitialized = false;
        let datePickerInitialized = false;
        let datePickerOpen = false;
        let currentViewDate = null;
        let datePickerScrollHandler = null;
        let datePickerResizeHandler = null;

        const formatDateDisplay = (value) => {
            if (!value) {
                return '';
            }

            const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(value);
            if (!match) {
                return value;
            }

            return `${match[3]}/${match[2]}/${match[1]}`;
        };

        const normalizeDate = (date) => new Date(date.getFullYear(), date.getMonth(), date.getDate());

        const parseDateValue = (value) => {
            const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(String(value || '').trim());
            if (!match) {
                return null;
            }

            const year = parseInt(match[1], 10);
            const month = parseInt(match[2], 10) - 1;
            const day = parseInt(match[3], 10);

            return new Date(year, month, day);
        };

        const maintenanceDateMin = parseDateValue(dateInput?.dataset.maintenanceDateMin || '');
        const maintenanceDateMax = parseDateValue(dateInput?.dataset.maintenanceDateMax || '');
        const maintenanceHolidaySet = (() => {
            try {
                const parsed = JSON.parse(String(dateInput?.dataset.maintenanceDateHolidays || '[]'));
                if (!Array.isArray(parsed)) {
                    return new Set();
                }

                return new Set(parsed.filter((value) => typeof value === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(value)));
            } catch (error) {
                return new Set();
            }
        })();

        const formatDateValue = (date) => {
            const local = normalizeDate(date);
            const year = local.getFullYear();
            const month = String(local.getMonth() + 1).padStart(2, '0');
            const day = String(local.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        };

        const isDateBeforeMin = (date) => maintenanceDateMin !== null && date.getTime() < maintenanceDateMin.getTime();

        const isDateAfterMax = (date) => maintenanceDateMax !== null && date.getTime() > maintenanceDateMax.getTime();

        const isWeekendDay = (date) => [0, 6].includes(date.getDay());

        const isHoliday = (date) => maintenanceHolidaySet.has(formatDateValue(date));

        const isNonBusinessDay = (date) => isWeekendDay(date) || isHoliday(date);

        const advanceToBusinessDay = (date) => {
            let current = normalizeDate(date);

            while (isNonBusinessDay(current)) {
                current = new Date(current.getFullYear(), current.getMonth(), current.getDate() + 1);
            }

            return current;
        };

        const clampDateValue = (value) => {
            const parsed = parseDateValue(value);
            if (!parsed) {
                return value;
            }

            let normalized = normalizeDate(parsed);

            if (isDateBeforeMin(normalized)) {
                normalized = normalizeDate(maintenanceDateMin);
            }

            if (isDateAfterMax(normalized)) {
                normalized = normalizeDate(maintenanceDateMax);
            }

            normalized = advanceToBusinessDay(normalized);

            return formatDateValue(normalized);
        };

        const formatDateLongEs = (date) => new Intl.DateTimeFormat('es-CO', {
            weekday: 'long',
            day: 'numeric',
            month: 'long',
            year: 'numeric',
        }).format(date);

        const formatMonthYearEs = (date) => new Intl.DateTimeFormat('es-CO', {
            month: 'long',
            year: 'numeric',
        }).format(date);

        const capitalizeFirst = (value) => value ? value.charAt(0).toUpperCase() + value.slice(1) : value;

        const getSelectedDate = () => parseDateValue(dateInput?.value || '') || normalizeDate(new Date());

        const syncDateTrigger = () => {
            if (!dateDisplay || !dateInput) {
                return;
            }

            const selected = parseDateValue(dateInput.value);
            dateDisplay.textContent = selected ? formatDateDisplay(formatDateValue(selected)) : 'Selecciona una fecha';
        };

        const syncViewToSelected = () => {
            currentViewDate = normalizeDate(getSelectedDate());
            currentViewDate.setDate(1);
        };

        const positionDatePicker = () => {
            if (!datePicker || !dateTrigger || datePicker.hidden) {
                return;
            }

            const triggerRect = dateTrigger.getBoundingClientRect();
            const padding = 16;
            const availableWidth = Math.max(0, window.innerWidth - (padding * 2));
            const preferredWidth = Math.min(720, Math.max(triggerRect.width, 320));
            const finalWidth = Math.max(Math.min(preferredWidth, availableWidth), Math.min(availableWidth, 280));

            datePicker.style.width = `${finalWidth}px`;
            datePicker.style.maxHeight = `${Math.max(240, window.innerHeight - padding * 2)}px`;

            const pickerRect = datePicker.getBoundingClientRect();
            const left = Math.max(padding, (window.innerWidth - finalWidth) / 2);
            const top = Math.max(padding, (window.innerHeight - pickerRect.height) / 2);

            datePicker.style.left = `${left}px`;
            datePicker.style.top = `${top}px`;
        };

        const closeDatePicker = (restoreFocus = true) => {
            if (!hasMaintenanceDatePicker || datePicker.hidden) {
                return;
            }

            datePicker.hidden = true;
            datePicker.setAttribute('aria-hidden', 'true');
            dateTrigger.setAttribute('aria-expanded', 'false');
            datePickerOpen = false;

            if (datePickerScrollHandler) {
                window.removeEventListener('scroll', datePickerScrollHandler, true);
                datePickerScrollHandler = null;
            }

            if (datePickerResizeHandler) {
                window.removeEventListener('resize', datePickerResizeHandler);
                datePickerResizeHandler = null;
            }

            if (restoreFocus) {
                window.requestAnimationFrame(() => {
                    dateTrigger.focus();
                });
            }
        };

        const updateSelectedDate = (value, { keepView = false, keepOpen = false } = {}) => {
            if (!dateInput) {
                return;
            }

            const selected = parseDateValue(value);
            if (selected && (isDateBeforeMin(selected) || isDateAfterMax(selected) || isNonBusinessDay(selected))) {
                return;
            }

            dateInput.value = value;
            syncDateTrigger();
            if (selected && !keepView) {
                currentViewDate = normalizeDate(selected);
                currentViewDate.setDate(1);
            }

            if (datePickerInitialized) {
                renderDatePicker();
            }

            if (!keepOpen) {
                closeDatePicker(true);
            }
        };

        const renderDatePicker = () => {
            if (!hasMaintenanceDatePicker || !currentViewDate) {
                return;
            }

            const viewYear = currentViewDate.getFullYear();
            const viewMonth = currentViewDate.getMonth();
            const selectedDate = parseDateValue(dateInput?.value || '');
            const today = normalizeDate(new Date());

            dateMonthLabel.textContent = capitalizeFirst(formatMonthYearEs(currentViewDate));
            dateYearSelect.innerHTML = '';

            const startYear = Math.max(1900, viewYear - 10);
            const endYear = viewYear + 10;
            for (let year = startYear; year <= endYear; year += 1) {
                const option = document.createElement('option');
                option.value = String(year);
                option.textContent = String(year);
                if (year === viewYear) {
                    option.selected = true;
                }
                dateYearSelect.appendChild(option);
            }

            dateGrid.innerHTML = '';
            const start = new Date(viewYear, viewMonth, 1);
            const startOffset = (start.getDay() + 6) % 7;
            const firstCell = new Date(viewYear, viewMonth, 1 - startOffset);

            for (let index = 0; index < 42; index += 1) {
                const current = new Date(firstCell.getFullYear(), firstCell.getMonth(), firstCell.getDate() + index);
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'maintenance-date-picker-day';
                button.dataset.maintenanceDateValue = formatDateValue(current);
                button.setAttribute('aria-label', capitalizeFirst(formatDateLongEs(current)));
                button.textContent = String(current.getDate());

                if (current.getMonth() !== viewMonth) {
                    button.classList.add('maintenance-date-picker-day--outside');
                }

                if (isDateBeforeMin(current) || isDateAfterMax(current) || isNonBusinessDay(current)) {
                    button.disabled = true;
                    button.classList.add('maintenance-date-picker-day--disabled');
                    button.setAttribute('aria-disabled', 'true');
                }

                if (current.getTime() === today.getTime()) {
                    button.classList.add('maintenance-date-picker-day--today');
                }

                if (selectedDate && current.getTime() === selectedDate.getTime()) {
                    button.classList.add('maintenance-date-picker-day--selected');
                    button.setAttribute('aria-pressed', 'true');
                }

                button.addEventListener('pointerdown', (event) => {
                    event.preventDefault();
                    event.stopPropagation();
                    updateSelectedDate(button.dataset.maintenanceDateValue || '');
                });

                button.addEventListener('click', (event) => {
                    event.preventDefault();
                    event.stopPropagation();
                    updateSelectedDate(button.dataset.maintenanceDateValue || '');
                });

                dateGrid.appendChild(button);
            }

            positionDatePicker();
        };

        const openDatePicker = () => {
            if (!hasMaintenanceDatePicker) {
                return;
            }

            if (datePickerOpen) {
                closeDatePicker(true);
                return;
            }

            syncViewToSelected();
            datePicker.hidden = false;
            datePicker.setAttribute('aria-hidden', 'false');
            dateTrigger.setAttribute('aria-expanded', 'true');
            datePickerOpen = true;

            renderDatePicker();

            datePickerScrollHandler = () => positionDatePicker();
            datePickerResizeHandler = () => positionDatePicker();
            window.addEventListener('scroll', datePickerScrollHandler, true);
            window.addEventListener('resize', datePickerResizeHandler);

            window.requestAnimationFrame(() => {
                positionDatePicker();
                const selectedButton = dateGrid.querySelector('.maintenance-date-picker-day--selected') || dateGrid.querySelector('.maintenance-date-picker-day:not([disabled])');
                if (selectedButton) {
                    selectedButton.focus();
                }
            });
        };

        const moveGridFocus = (activeButton, offset) => {
            const buttons = Array.from(dateGrid.querySelectorAll('.maintenance-date-picker-day:not([disabled])'));
            const currentIndex = buttons.indexOf(activeButton);
            if (currentIndex === -1) {
                return;
            }

            const nextIndex = Math.max(0, Math.min(buttons.length - 1, currentIndex + offset));
            const nextButton = buttons[nextIndex];
            if (nextButton) {
                nextButton.focus();
            }
        };

        const getVisibleDateTrigger = () => {
            if (!dateTrigger) {
                return null;
            }

            if (dateTrigger.disabled || dateTrigger.closest('[hidden]')) {
                return null;
            }

            return dateTrigger;
        };

        const focusFirstField = () => {
            const target =
                modal.querySelector('[aria-invalid="true"]') ||
                getVisibleDateTrigger() ||
                modal.querySelector('input:not([type="hidden"]):not([type="file"]), select, textarea, button[type="submit"]');

            if (target && typeof target.focus === 'function') {
                target.focus();
            }
        };

        const setupUserMaintenanceBaseField = () => {
            const typeSelect = modal.querySelector('[data-user-equipo-type-select]');
            const field = modal.querySelector('[data-user-last-maintenance-field]');
            const dateInput = field?.querySelector('[data-user-last-maintenance-input]') || null;
            const fieldLabel = field?.querySelector('[data-user-last-maintenance-label]') || null;
            const nativeDateInput = field?.querySelector('[data-user-native-date-input]') || null;
            const nativeDateTrigger = field?.querySelector('[data-user-native-date-trigger]') || null;
            const nativeDateDisplay = field?.querySelector('[data-maintenance-date-display]') || null;

            if (!typeSelect || !field || !dateInput || !fieldLabel) {
                return;
            }

            const syncNativeDateDisplay = () => {
                if (!nativeDateInput || !nativeDateDisplay) {
                    return;
                }

                const value = String(nativeDateInput.value || '').trim();
                dateInput.value = value;
                nativeDateDisplay.textContent = value ? formatDateDisplay(value) : 'Selecciona una fecha';
            };

            const syncFieldState = () => {
                const isNew = typeSelect.value === 'nuevo';
                field.hidden = false;
                field.setAttribute('aria-hidden', 'false');
                dateInput.disabled = false;
                fieldLabel.innerHTML = isNew
                    ? fieldLabel.dataset.labelNuevo || 'Fecha de compra'
                    : fieldLabel.dataset.labelNormal || 'Fecha de último mantenimiento';
            };

            if (nativeDateInput) {
                nativeDateInput.value = String(dateInput.value || nativeDateInput.value || '').trim();
                nativeDateInput.addEventListener('input', syncNativeDateDisplay);
                nativeDateInput.addEventListener('change', syncNativeDateDisplay);
                syncNativeDateDisplay();
            }

            if (nativeDateTrigger && nativeDateInput) {
                nativeDateTrigger.addEventListener('click', (event) => {
                    event.preventDefault();

                    if (typeof nativeDateInput.showPicker === 'function') {
                        nativeDateInput.showPicker();
                        return;
                    }

                    nativeDateInput.focus();
                    nativeDateInput.click();
                });
            }

            typeSelect.addEventListener('change', syncFieldState);
            modal.addEventListener('panel:user-type-sync', syncFieldState);
            syncFieldState();
        };

        const restoreSubmitButton = () => {
            if (!submitButton) {
                return;
            }

            submitButton.disabled = false;
            submitButton.textContent = submitButtonLabel;
        };

        const setupFileDropzone = () => {
            if (!fileDropzone || fileDropzoneInitialized) {
                return;
            }

            const fileInput = fileDropzone.querySelector('[data-maintenance-file-input]');
            const fileName = fileDropzone.querySelector('[data-maintenance-file-name]');
            const clearButton = fileDropzone.querySelector('[data-maintenance-file-clear]');
            const defaultLabel = fileName ? fileName.textContent || 'Sin archivo seleccionado' : 'Sin archivo seleccionado';

            if (!fileInput || !fileName) {
                return;
            }

            const updateState = () => {
                const file = fileInput.files && fileInput.files[0] ? fileInput.files[0] : null;

                if (file) {
                    fileDropzone.classList.add('maintenance-upload--has-file');
                    fileName.textContent = file.name;
                } else {
                    fileDropzone.classList.remove('maintenance-upload--has-file');
                    fileName.textContent = defaultLabel;
                }
            };

            const setFileList = (files) => {
                if (!files || files.length === 0) {
                    fileInput.value = '';
                    updateState();
                    return;
                }

                const file = files[0];
                const isPdf = (file.type || '').toLowerCase().includes('pdf') || /\.pdf$/i.test(file.name || '');
                if (!isPdf) {
                    return;
                }

                if (typeof DataTransfer !== 'undefined') {
                    const transfer = new DataTransfer();
                    transfer.items.add(file);
                    fileInput.files = transfer.files;
                }

                updateState();
            };

            fileInput.addEventListener('change', updateState);

            if (clearButton) {
                clearButton.addEventListener('click', () => {
                    fileInput.value = '';
                    updateState();
                    fileInput.focus();
                });
            }

            fileDropzone.addEventListener('dragover', (event) => {
                event.preventDefault();
                fileDropzone.classList.add('maintenance-upload--dragover');
            });

            fileDropzone.addEventListener('dragleave', () => {
                fileDropzone.classList.remove('maintenance-upload--dragover');
            });

            fileDropzone.addEventListener('drop', (event) => {
                event.preventDefault();
                fileDropzone.classList.remove('maintenance-upload--dragover');
                setFileList(event.dataTransfer ? event.dataTransfer.files : null);
            });

            updateState();

            form?.addEventListener('reset', () => {
                window.setTimeout(updateState, 0);
            });

            fileDropzoneInitialized = true;
        };

        const closeModal = () => {
            if (closeUrl !== '') {
                window.location.href = closeUrl;
                return;
            }

            modal.hidden = true;
            modal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('modal-open');

            if (form) {
                form.reset();
            }

            if (idInput) {
                idInput.value = '';
            }

            if (hasMaintenanceDatePicker) {
                closeDatePicker(false);
                syncDateTrigger();
            }

            restoreSubmitButton();

            if (fileDropzone) {
                const fileInput = fileDropzone.querySelector('[data-maintenance-file-input]');
                if (fileInput) {
                    fileInput.value = '';
                }
            }
        };

        const openModal = (button) => {
            const mantenimientoId = button.dataset.maintenanceId || '';
            const resumen = button.dataset.maintenanceSummary || '';
            const suggestedDate = button.dataset.maintenanceSuggestedDate || '';
            const programmedDate = button.dataset.maintenanceProgrammedDate || '';
            const target = button.dataset.maintenanceTarget || '';
            const todayValue = formatDateValue(new Date());

            if (idInput) {
                idInput.value = mantenimientoId;
            }

            if (programmedDateDisplay) {
                programmedDateDisplay.value = formatDateDisplay(programmedDate || suggestedDate) || 'Sin fecha programada';
            }

            if (dateInput && !hasMaintenanceDatePicker) {
                const minDate = dateInput.getAttribute('min') || '';
                const maxDate = dateInput.getAttribute('max') || '';
                let value = '';

                if (target === 'programar') {
                    value = suggestedDate;
                } else if (minDate && maxDate && minDate === maxDate) {
                    value = minDate;
                } else {
                    value = minDate || maxDate || suggestedDate || dateInput.defaultValue || dateInput.value;
                }

                dateInput.value = clampDateValue(value);
            }

            if (hasMaintenanceDatePicker && dateInput) {
                if (target === 'programar' && !shouldPreserveMaintenanceDate) {
                    dateInput.value = clampDateValue(suggestedDate || dateInput.value || dateInput.defaultValue || todayValue);
                } else if (target === 'hecho') {
                    dateInput.value = clampDateValue(programmedDate || suggestedDate || dateInput.value || dateInput.defaultValue || todayValue);
                }
            }

            if (hasMaintenanceDatePicker) {
                syncDateTrigger();
                syncViewToSelected();
                renderDatePicker();
            }

            if (summary) {
                summary.textContent = resumen || options.defaultSummary;
            }

            restoreSubmitButton();
            setupFileDropzone();
            if (hasMaintenanceDatePicker) {
                dateTrigger.setAttribute('aria-expanded', 'false');
            }

            modal.hidden = false;
            modal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('modal-open');
            modal.dispatchEvent(new CustomEvent('panel:user-type-sync'));

            window.requestAnimationFrame(() => {
                if (typeof modal.focus === 'function') {
                    modal.focus();
                }
            });

            focusFirstField();
        };

        openButtons.forEach((button) => {
            button.addEventListener('click', () => openModal(button));
        });

        closeButtons.forEach((button) => {
            button.addEventListener('click', closeModal);
        });

        if (hasMaintenanceDatePicker && !datePickerInitialized) {
            const focusSelectedDay = () => {
                const selectedButton = dateGrid.querySelector('.maintenance-date-picker-day--selected') || dateGrid.querySelector('.maintenance-date-picker-day');
                if (selectedButton) {
                    selectedButton.focus();
                }
            };

            dateTrigger.addEventListener('click', (event) => {
                event.preventDefault();
                if (datePickerOpen) {
                    closeDatePicker(true);
                    return;
                }

                openDatePicker();
            });

            dateTrigger.addEventListener('keydown', (event) => {
                if (event.key === 'ArrowDown' || event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    openDatePicker();
                }
            });

            datePrev?.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();

                if (!currentViewDate) {
                    syncViewToSelected();
                }

                currentViewDate = new Date(currentViewDate.getFullYear(), currentViewDate.getMonth() - 1, 1);
                renderDatePicker();
                focusSelectedDay();
            });

            dateNext?.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();

                if (!currentViewDate) {
                    syncViewToSelected();
                }

                currentViewDate = new Date(currentViewDate.getFullYear(), currentViewDate.getMonth() + 1, 1);
                renderDatePicker();
                focusSelectedDay();
            });

            dateToday?.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();
                updateSelectedDate(clampDateValue(formatDateValue(new Date())));
            });

            dateClose?.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();
                closeDatePicker(true);
            });

            dateYearSelect.addEventListener('change', () => {
                if (!currentViewDate) {
                    syncViewToSelected();
                }

                const selectedYear = parseInt(dateYearSelect.value, 10);
                if (Number.isNaN(selectedYear)) {
                    return;
                }

                currentViewDate = new Date(selectedYear, currentViewDate.getMonth(), 1);
                renderDatePicker();
                focusSelectedDay();
            });

            dateGrid.addEventListener('keydown', (event) => {
                const activeButton = event.target.closest('.maintenance-date-picker-day');
                if (!activeButton) {
                    return;
                }

                switch (event.key) {
                    case 'ArrowLeft':
                        event.preventDefault();
                        moveGridFocus(activeButton, -1);
                        break;
                    case 'ArrowRight':
                        event.preventDefault();
                        moveGridFocus(activeButton, 1);
                        break;
                    case 'ArrowUp':
                        event.preventDefault();
                        moveGridFocus(activeButton, -7);
                        break;
                    case 'ArrowDown':
                        event.preventDefault();
                        moveGridFocus(activeButton, 7);
                        break;
                    case 'Home':
                        event.preventDefault();
                        moveGridFocus(activeButton, -(Array.from(dateGrid.querySelectorAll('.maintenance-date-picker-day')).indexOf(activeButton) % 7));
                        break;
                    case 'End':
                        event.preventDefault();
                        moveGridFocus(activeButton, 6 - (Array.from(dateGrid.querySelectorAll('.maintenance-date-picker-day')).indexOf(activeButton) % 7));
                        break;
                    case 'PageUp':
                        event.preventDefault();
                        currentViewDate = new Date(currentViewDate.getFullYear(), currentViewDate.getMonth() - 1, 1);
                        renderDatePicker();
                        focusSelectedDay();
                        break;
                    case 'PageDown':
                        event.preventDefault();
                        currentViewDate = new Date(currentViewDate.getFullYear(), currentViewDate.getMonth() + 1, 1);
                        renderDatePicker();
                        focusSelectedDay();
                        break;
                    default:
                        break;
                }
            });

            datePicker.addEventListener('mousedown', (event) => {
                event.stopPropagation();
            });

            datePicker.addEventListener('click', (event) => {
                event.stopPropagation();
            });

            document.addEventListener('click', (event) => {
                if (!datePickerOpen || modal.hidden) {
                    return;
                }

                if (datePicker.contains(event.target) || dateTrigger.contains(event.target)) {
                    return;
                }

                closeDatePicker(false);
            });

            datePickerInitialized = true;
        }

        modal.addEventListener('click', (event) => {
            if (event.target.matches(options.closeSelector)) {
                closeModal();
                return;
            }

            if (!hasMaintenanceDatePicker || !datePickerOpen) {
                return;
            }

            if (datePicker.contains(event.target) || dateTrigger.contains(event.target)) {
                return;
            }

            closeDatePicker(false);
        });

        if (form && submitButton) {
            form.addEventListener('submit', () => {
                submitButton.disabled = true;
                submitButton.textContent = 'Guardando...';
                closeDatePicker(false);
            });
        }

        if (form) {
            form.addEventListener('reset', () => {
                window.setTimeout(() => {
                    restoreSubmitButton();
                    modal.dispatchEvent(new CustomEvent('panel:user-type-sync'));
                    if (hasMaintenanceDatePicker) {
                        syncDateTrigger();
                        syncViewToSelected();
                        renderDatePicker();
                        closeDatePicker(false);
                    }
                }, 0);
            });
        }

        document.addEventListener('keydown', (event) => {
            if (event.key !== 'Escape' || modal.hidden) {
                return;
            }

            if (hasMaintenanceDatePicker && datePickerOpen) {
                event.preventDefault();
                event.stopImmediatePropagation();
                closeDatePicker(true);
                return;
            }

            if (!datePickerOpen) {
                closeModal();
            }
        });

        if (!modal.hidden) {
            modal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('modal-open');
            window.requestAnimationFrame(() => {
                focusFirstField();
            });
        }

        setupUserMaintenanceBaseField();
        modal.dataset.modalBound = 'true';
    };

    const userCreateModalOptions = {
        modalSelector: '[data-user-create-modal]',
        openSelector: '[data-user-create-open]',
        closeSelector: '[data-user-create-close]',
        idInputSelector: null,
        summarySelector: null,
        defaultSummary: '',
    };

    const userEditModalOptions = {
        modalSelector: '[data-user-edit-modal]',
        openSelector: '[data-user-edit-open]',
        closeSelector: '[data-user-edit-close]',
        idInputSelector: null,
        summarySelector: null,
        defaultSummary: '',
    };

    const maintenanceModalOptions = {
        modalSelector: '[data-maintenance-modal]',
        openSelector: '[data-open-maintenance-modal]',
        closeSelector: '[data-maintenance-modal-close]',
        idInputSelector: '[data-maintenance-id-input]',
        summarySelector: '[data-maintenance-summary]',
        defaultSummary: 'Selecciona una acción para este mantenimiento.',
    };

    refreshInteractiveBindings = () => {
        closePanelActionMenus(false);
        initializeUserGroupToggles();
        setupModal(userCreateModalOptions);
        setupModal(userEditModalOptions);
        setupModal(maintenanceModalOptions);
    };

    refreshInteractiveBindings();
});
