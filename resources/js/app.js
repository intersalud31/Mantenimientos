document.addEventListener('DOMContentLoaded', () => {
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

    const closeUserActionMenus = () => {
        document.querySelectorAll('[data-user-actions]').forEach((menu) => {
            const trigger = menu.querySelector('[data-user-actions-trigger]');
            const panel = menu.querySelector('[data-user-actions-panel]');

            if (trigger) {
                trigger.setAttribute('aria-expanded', 'false');
            }

            if (panel) {
                panel.hidden = true;
            }
        });
    };

    document.querySelectorAll('[data-users-group-shell]').forEach((shell) => {
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
    });

    document.querySelectorAll('[data-user-actions]').forEach((menu) => {
        const trigger = menu.querySelector('[data-user-actions-trigger]');
        const panel = menu.querySelector('[data-user-actions-panel]');

        if (!trigger || !panel) {
            return;
        }

        trigger.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();

            const shouldOpen = panel.hidden;
            closeUserActionMenus();

            panel.hidden = !shouldOpen;
            trigger.setAttribute('aria-expanded', shouldOpen ? 'true' : 'false');
        });
    });

    document.addEventListener('click', (event) => {
        if (!event.target.closest('[data-user-actions]')) {
            closeUserActionMenus();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeUserActionMenus();
        }
    });

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

        const formatDateValue = (date) => {
            const local = normalizeDate(date);
            const year = local.getFullYear();
            const month = String(local.getMonth() + 1).padStart(2, '0');
            const day = String(local.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
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
            const pickerRect = datePicker.getBoundingClientRect();
            const padding = 12;
            const availableBelow = window.innerHeight - triggerRect.bottom - padding;
            const availableAbove = triggerRect.top - padding;
            const fitsBelow = pickerRect.height <= availableBelow;
            const fitsAbove = pickerRect.height <= availableAbove;

            const width = Math.max(triggerRect.width, 320);
            const maxWidth = Math.min(width, window.innerWidth - padding * 2);
            const left = Math.min(Math.max(padding, triggerRect.left), window.innerWidth - maxWidth - padding);

            let top = triggerRect.bottom + 8;
            if (!fitsBelow && fitsAbove) {
                top = Math.max(padding, triggerRect.top - pickerRect.height - 8);
            } else if (!fitsBelow && !fitsAbove) {
                top = Math.max(padding, Math.min(triggerRect.bottom + 8, window.innerHeight - pickerRect.height - padding));
            }

            datePicker.style.left = `${left}px`;
            datePicker.style.top = `${top}px`;
            datePicker.style.width = `${maxWidth}px`;
            datePicker.style.maxHeight = `${Math.max(240, window.innerHeight - padding * 2)}px`;
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

            dateInput.value = value;
            syncDateTrigger();

            const selected = parseDateValue(value);
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

                if (current.getTime() === today.getTime()) {
                    button.classList.add('maintenance-date-picker-day--today');
                }

                if (selectedDate && current.getTime() === selectedDate.getTime()) {
                    button.classList.add('maintenance-date-picker-day--selected');
                    button.setAttribute('aria-pressed', 'true');
                }

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
                const selectedButton = dateGrid.querySelector('.maintenance-date-picker-day--selected') || dateGrid.querySelector('.maintenance-date-picker-day');
                if (selectedButton) {
                    selectedButton.focus();
                }
            });
        };

        const moveGridFocus = (activeButton, offset) => {
            const buttons = Array.from(dateGrid.querySelectorAll('.maintenance-date-picker-day'));
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

            if (!typeSelect || !field || !dateInput) {
                return;
            }

            const syncVisibility = () => {
                const shouldShow = typeSelect.value === 'normal';
                field.hidden = !shouldShow;
                field.setAttribute('aria-hidden', shouldShow ? 'false' : 'true');
                dateInput.disabled = !shouldShow;
            };

            typeSelect.addEventListener('change', syncVisibility);
            modal.addEventListener('panel:user-type-sync', syncVisibility);
            syncVisibility();
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

                if (minDate && value && value < minDate) {
                    value = minDate;
                }

                if (maxDate && value && value > maxDate) {
                    value = maxDate;
                }

                dateInput.value = value;
            }

            if (hasMaintenanceDatePicker && dateInput) {
                if (target === 'programar' && !shouldPreserveMaintenanceDate) {
                    const initialDate = suggestedDate || dateInput.value || dateInput.defaultValue || formatDateValue(new Date());
                    dateInput.value = initialDate;
                } else if (target === 'hecho') {
                    const initialDate = programmedDate || suggestedDate || dateInput.value || dateInput.defaultValue || formatDateValue(new Date());
                    dateInput.value = initialDate;
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

            datePrev?.addEventListener('click', () => {
                if (!currentViewDate) {
                    syncViewToSelected();
                }

                currentViewDate = new Date(currentViewDate.getFullYear(), currentViewDate.getMonth() - 1, 1);
                renderDatePicker();
                focusSelectedDay();
            });

            dateNext?.addEventListener('click', () => {
                if (!currentViewDate) {
                    syncViewToSelected();
                }

                currentViewDate = new Date(currentViewDate.getFullYear(), currentViewDate.getMonth() + 1, 1);
                renderDatePicker();
                focusSelectedDay();
            });

            dateToday?.addEventListener('click', () => {
                updateSelectedDate(formatDateValue(new Date()));
            });

            dateClose?.addEventListener('click', () => closeDatePicker(true));

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

            dateGrid.addEventListener('click', (event) => {
                const button = event.target.closest('.maintenance-date-picker-day');
                if (!button) {
                    return;
                }

                updateSelectedDate(button.dataset.maintenanceDateValue || '');
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
    };

    setupModal({
        modalSelector: '[data-user-create-modal]',
        openSelector: '[data-user-create-open]',
        closeSelector: '[data-user-create-close]',
        idInputSelector: null,
        summarySelector: null,
        defaultSummary: '',
    });

    setupModal({
        modalSelector: '[data-user-edit-modal]',
        openSelector: '[data-user-edit-open]',
        closeSelector: '[data-user-edit-close]',
        idInputSelector: null,
        summarySelector: null,
        defaultSummary: '',
    });

    setupModal({
        modalSelector: '[data-maintenance-modal]',
        openSelector: '[data-open-maintenance-modal]',
        closeSelector: '[data-maintenance-modal-close]',
        idInputSelector: '[data-maintenance-id-input]',
        summarySelector: '[data-maintenance-summary]',
        defaultSummary: 'Selecciona una acción para este mantenimiento.',
    });
});
