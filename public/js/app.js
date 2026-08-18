/******/ (() => { // webpackBootstrap
/******/ 	var __webpack_modules__ = ({

/***/ "./resources/js/app.js":
/*!*****************************!*\
  !*** ./resources/js/app.js ***!
  \*****************************/
/***/ (() => {

function _regenerator() { /*! regenerator-runtime -- Copyright (c) 2014-present, Facebook, Inc. -- license (MIT): https://github.com/babel/babel/blob/main/packages/babel-helpers/LICENSE */ var e, t, r = "function" == typeof Symbol ? Symbol : {}, n = r.iterator || "@@iterator", o = r.toStringTag || "@@toStringTag"; function i(r, n, o, i) { var c = n && n.prototype instanceof Generator ? n : Generator, u = Object.create(c.prototype); return _regeneratorDefine2(u, "_invoke", function (r, n, o) { var i, c, u, f = 0, p = o || [], y = !1, G = { p: 0, n: 0, v: e, a: d, f: d.bind(e, 4), d: function d(t, r) { return i = t, c = 0, u = e, G.n = r, a; } }; function d(r, n) { for (c = r, u = n, t = 0; !y && f && !o && t < p.length; t++) { var o, i = p[t], d = G.p, l = i[2]; r > 3 ? (o = l === n) && (u = i[(c = i[4]) ? 5 : (c = 3, 3)], i[4] = i[5] = e) : i[0] <= d && ((o = r < 2 && d < i[1]) ? (c = 0, G.v = n, G.n = i[1]) : d < l && (o = r < 3 || i[0] > n || n > l) && (i[4] = r, i[5] = n, G.n = l, c = 0)); } if (o || r > 1) return a; throw y = !0, n; } return function (o, p, l) { if (f > 1) throw TypeError("Generator is already running"); for (y && 1 === p && d(p, l), c = p, u = l; (t = c < 2 ? e : u) || !y;) { i || (c ? c < 3 ? (c > 1 && (G.n = -1), d(c, u)) : G.n = u : G.v = u); try { if (f = 2, i) { if (c || (o = "next"), t = i[o]) { if (!(t = t.call(i, u))) throw TypeError("iterator result is not an object"); if (!t.done) return t; u = t.value, c < 2 && (c = 0); } else 1 === c && (t = i["return"]) && t.call(i), c < 2 && (u = TypeError("The iterator does not provide a '" + o + "' method"), c = 1); i = e; } else if ((t = (y = G.n < 0) ? u : r.call(n, G)) !== a) break; } catch (t) { i = e, c = 1, u = t; } finally { f = 1; } } return { value: t, done: y }; }; }(r, o, i), !0), u; } var a = {}; function Generator() {} function GeneratorFunction() {} function GeneratorFunctionPrototype() {} t = Object.getPrototypeOf; var c = [][n] ? t(t([][n]())) : (_regeneratorDefine2(t = {}, n, function () { return this; }), t), u = GeneratorFunctionPrototype.prototype = Generator.prototype = Object.create(c); function f(e) { return Object.setPrototypeOf ? Object.setPrototypeOf(e, GeneratorFunctionPrototype) : (e.__proto__ = GeneratorFunctionPrototype, _regeneratorDefine2(e, o, "GeneratorFunction")), e.prototype = Object.create(u), e; } return GeneratorFunction.prototype = GeneratorFunctionPrototype, _regeneratorDefine2(u, "constructor", GeneratorFunctionPrototype), _regeneratorDefine2(GeneratorFunctionPrototype, "constructor", GeneratorFunction), GeneratorFunction.displayName = "GeneratorFunction", _regeneratorDefine2(GeneratorFunctionPrototype, o, "GeneratorFunction"), _regeneratorDefine2(u), _regeneratorDefine2(u, o, "Generator"), _regeneratorDefine2(u, n, function () { return this; }), _regeneratorDefine2(u, "toString", function () { return "[object Generator]"; }), (_regenerator = function _regenerator() { return { w: i, m: f }; })(); }
function _regeneratorDefine2(e, r, n, t) { var i = Object.defineProperty; try { i({}, "", {}); } catch (e) { i = 0; } _regeneratorDefine2 = function _regeneratorDefine(e, r, n, t) { function o(r, n) { _regeneratorDefine2(e, r, function (e) { return this._invoke(r, n, e); }); } r ? i ? i(e, r, { value: n, enumerable: !t, configurable: !t, writable: !t }) : e[r] = n : (o("next", 0), o("throw", 1), o("return", 2)); }, _regeneratorDefine2(e, r, n, t); }
function asyncGeneratorStep(n, t, e, r, o, a, c) { try { var i = n[a](c), u = i.value; } catch (n) { return void e(n); } i.done ? t(u) : Promise.resolve(u).then(r, o); }
function _asyncToGenerator(n) { return function () { var t = this, e = arguments; return new Promise(function (r, o) { var a = n.apply(t, e); function _next(n) { asyncGeneratorStep(a, r, o, _next, _throw, "next", n); } function _throw(n) { asyncGeneratorStep(a, r, o, _next, _throw, "throw", n); } _next(void 0); }); }; }
document.addEventListener('DOMContentLoaded', function () {
  var appSidebar = document.querySelector('[data-app-sidebar]');
  var appSidebarBackdrop = document.querySelector('[data-app-sidebar-backdrop]');
  var appSidebarToggle = document.querySelector('[data-app-sidebar-toggle]');
  var appSidebarClose = document.querySelector('[data-app-sidebar-close]');
  var appNavGroups = Array.from(document.querySelectorAll('[data-app-nav-group]'));
  if (appSidebar && appSidebarToggle) {
    var desktopQuery = window.matchMedia('(min-width: 981px)');
    var setMobileOpen = function setMobileOpen(open) {
      document.body.classList.toggle('app-sidebar-mobile-open', open);
      appSidebarToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      if (appSidebarBackdrop) {
        appSidebarBackdrop.hidden = !open;
      }
    };
    var setDesktopCollapsed = function setDesktopCollapsed(collapsed) {
      document.body.classList.toggle('app-sidebar-collapsed', collapsed);
      appSidebarToggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
    };
    var closeMobileSidebar = function closeMobileSidebar() {
      return setMobileOpen(false);
    };
    appSidebarToggle.addEventListener('click', function (event) {
      event.preventDefault();
      if (desktopQuery.matches) {
        setDesktopCollapsed(!document.body.classList.contains('app-sidebar-collapsed'));
        return;
      }
      setMobileOpen(!document.body.classList.contains('app-sidebar-mobile-open'));
    });
    appSidebarClose === null || appSidebarClose === void 0 || appSidebarClose.addEventListener('click', function (event) {
      event.preventDefault();
      closeMobileSidebar();
    });
    appSidebarBackdrop === null || appSidebarBackdrop === void 0 || appSidebarBackdrop.addEventListener('click', closeMobileSidebar);
    appSidebar.querySelectorAll('[data-app-sidebar-link]').forEach(function (link) {
      link.addEventListener('click', function () {
        if (!desktopQuery.matches) {
          closeMobileSidebar();
        }
      });
    });
    appNavGroups.forEach(function (group) {
      var toggle = group.querySelector('[data-app-nav-toggle]');
      var panel = group.querySelector('[data-app-nav-panel]');
      if (!toggle || !panel) {
        return;
      }
      var setOpen = function setOpen(open) {
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        panel.hidden = !open;
        group.classList.toggle('app-nav-group--open', open);
      };
      setOpen(toggle.getAttribute('aria-expanded') === 'true');
      toggle.addEventListener('click', function (event) {
        event.preventDefault();
        if (desktopQuery.matches && document.body.classList.contains('app-sidebar-collapsed')) {
          setDesktopCollapsed(false);
          setOpen(true);
          return;
        }
        setOpen(toggle.getAttribute('aria-expanded') !== 'true');
      });
    });
    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') {
        closeMobileSidebar();
      }
    });
    var syncResponsiveSidebar = function syncResponsiveSidebar() {
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
  var topbar = document.querySelector('[data-topbar]');
  if (topbar) {
    var menus = Array.from(topbar.querySelectorAll('[data-topbar-menu]'));
    var closeMenu = function closeMenu(menu) {
      var button = menu.querySelector('[data-topbar-toggle]');
      var panel = menu.querySelector('[data-topbar-panel]');
      if (button) {
        button.setAttribute('aria-expanded', 'false');
      }
      if (panel) {
        panel.hidden = true;
      }
      menu.classList.remove('menu-group--open');
    };
    var closeAllMenus = function closeAllMenus() {
      return menus.forEach(closeMenu);
    };
    var openMenu = function openMenu(menu) {
      menus.forEach(function (other) {
        if (other !== menu) {
          closeMenu(other);
        }
      });
      var button = menu.querySelector('[data-topbar-toggle]');
      var panel = menu.querySelector('[data-topbar-panel]');
      if (button) {
        button.setAttribute('aria-expanded', 'true');
      }
      if (panel) {
        panel.hidden = false;
      }
      menu.classList.add('menu-group--open');
    };
    menus.forEach(function (menu) {
      var button = menu.querySelector('[data-topbar-toggle]');
      var panel = menu.querySelector('[data-topbar-panel]');
      if (!button || !panel) {
        return;
      }
      button.addEventListener('click', function (event) {
        event.preventDefault();
        event.stopPropagation();
        var isOpen = button.getAttribute('aria-expanded') === 'true';
        if (isOpen) {
          closeMenu(menu);
          return;
        }
        openMenu(menu);
      });
    });
    document.addEventListener('click', function (event) {
      if (!topbar.contains(event.target)) {
        closeAllMenus();
      }
    });
    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') {
        closeAllMenus();
      }
    });
  }
  var panelActionMenuSelector = '[data-panel-action-menu]';
  var panelActionMenuTriggerSelector = '[data-panel-action-menu-trigger]';
  var panelActionMenuPanelSelector = '[data-panel-action-menu-panel]';
  var panelActionMenuItemSelector = '[data-panel-action-menu-item]';
  var activePanelActionMenu = null;
  var getPanelActionMenuElements = function getPanelActionMenuElements(menu) {
    if (!menu) {
      return {
        trigger: null,
        panel: null
      };
    }
    return {
      trigger: menu.querySelector(panelActionMenuTriggerSelector),
      panel: menu.querySelector(panelActionMenuPanelSelector)
    };
  };
  var getPanelActionMenuItems = function getPanelActionMenuItems(menu) {
    return Array.from(menu.querySelectorAll(panelActionMenuItemSelector)).filter(function (item) {
      return item.getAttribute('aria-disabled') !== 'true' && !item.hidden;
    });
  };
  var closePanelActionMenus = function closePanelActionMenus() {
    var focusTrigger = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : false;
    document.querySelectorAll(panelActionMenuSelector).forEach(function (menu) {
      var _getPanelActionMenuEl = getPanelActionMenuElements(menu),
        trigger = _getPanelActionMenuEl.trigger,
        panel = _getPanelActionMenuEl.panel;
      if (trigger) {
        trigger.setAttribute('aria-expanded', 'false');
      }
      if (panel) {
        panel.hidden = true;
        panel.style.left = '';
        panel.style.top = '';
        panel.style.right = '';
        panel.style.bottom = '';
        panel.style.width = '';
        panel.style.position = '';
        panel.style.maxHeight = '';
        panel.style.visibility = '';
      }
      menu.classList.remove('panel-action-menu--open');
      menu.classList.remove('panel-action-menu--top', 'panel-action-menu--bottom');
    });
    if (focusTrigger && activePanelActionMenu) {
      var _getPanelActionMenuEl2 = getPanelActionMenuElements(activePanelActionMenu),
        trigger = _getPanelActionMenuEl2.trigger;
      trigger === null || trigger === void 0 || trigger.focus();
    }
    activePanelActionMenu = null;
  };
  var positionPanelActionMenu = function positionPanelActionMenu(menu) {
    var _getPanelActionMenuEl3 = getPanelActionMenuElements(menu),
      trigger = _getPanelActionMenuEl3.trigger,
      panel = _getPanelActionMenuEl3.panel;
    if (!trigger || !panel || panel.hidden) {
      return;
    }
    panel.style.visibility = 'hidden';
    panel.style.removeProperty('--panel-action-menu-max-height');
    var triggerRect = trigger.getBoundingClientRect();
    var viewportWidth = window.innerWidth;
    var viewportHeight = window.innerHeight;
    var panelRect = panel.getBoundingClientRect();
    var menuWidth = Math.min(Math.max(panelRect.width || 220, 220), viewportWidth - 24);
    var estimatedMenuHeight = Math.max(panelRect.height || 0, 180);
    var spaceBelow = viewportHeight - triggerRect.bottom - 12;
    var spaceAbove = triggerRect.top - 12;
    var openAbove = spaceBelow < estimatedMenuHeight && spaceAbove > spaceBelow;
    var availableHeight = Math.max(120, Math.min(estimatedMenuHeight, openAbove ? spaceAbove : spaceBelow, viewportHeight - 24));
    var alignStart = menu.classList.contains('panel-action-menu--start');
    var horizontalPadding = 12;
    var preferredLeft = alignStart ? triggerRect.left : triggerRect.right - menuWidth;
    var left = Math.max(horizontalPadding, Math.min(preferredLeft, viewportWidth - menuWidth - horizontalPadding));
    var top = openAbove ? Math.max(horizontalPadding, triggerRect.top - 8 - availableHeight) : Math.min(viewportHeight - availableHeight - horizontalPadding, triggerRect.bottom + 8);
    menu.classList.toggle('panel-action-menu--top', openAbove);
    menu.classList.toggle('panel-action-menu--bottom', !openAbove);
    panel.style.setProperty('--panel-action-menu-max-height', "".concat(availableHeight, "px"));
    panel.style.width = "".concat(menuWidth, "px");
    panel.style.position = 'fixed';
    panel.style.left = "".concat(left, "px");
    panel.style.top = "".concat(top, "px");
    panel.style.right = 'auto';
    panel.style.bottom = 'auto';
    panel.style.visibility = '';
  };
  var focusPanelActionMenuItem = function focusPanelActionMenuItem(menu) {
    var _items$index;
    var direction = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 0;
    var items = getPanelActionMenuItems(menu);
    if (!items.length) {
      return;
    }
    var index = 0;
    var currentIndex = items.indexOf(document.activeElement);
    if (direction === 0) {
      index = currentIndex >= 0 ? currentIndex : 0;
    } else if (direction > 0) {
      index = currentIndex >= 0 ? (currentIndex + 1) % items.length : 0;
    } else {
      index = currentIndex >= 0 ? (currentIndex - 1 + items.length) % items.length : items.length - 1;
    }
    (_items$index = items[index]) === null || _items$index === void 0 || _items$index.focus();
  };
  var openPanelActionMenu = function openPanelActionMenu(menu) {
    var focusDirection = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : null;
    var _getPanelActionMenuEl4 = getPanelActionMenuElements(menu),
      trigger = _getPanelActionMenuEl4.trigger,
      panel = _getPanelActionMenuEl4.panel;
    if (!trigger || !panel) {
      return;
    }
    closePanelActionMenus(false);
    activePanelActionMenu = menu;
    trigger.setAttribute('aria-expanded', 'true');
    panel.hidden = false;
    menu.classList.add('panel-action-menu--open');
    positionPanelActionMenu(menu);
    if (focusDirection !== null) {
      focusPanelActionMenuItem(menu, focusDirection);
    }
  };
  var togglePanelActionMenu = function togglePanelActionMenu(menu) {
    var _getPanelActionMenuEl5 = getPanelActionMenuElements(menu),
      trigger = _getPanelActionMenuEl5.trigger;
    var isOpen = (trigger === null || trigger === void 0 ? void 0 : trigger.getAttribute('aria-expanded')) === 'true';
    if (isOpen) {
      closePanelActionMenus(true);
      return;
    }
    openPanelActionMenu(menu);
  };
  document.addEventListener('click', function (event) {
    var trigger = event.target.closest(panelActionMenuTriggerSelector);
    if (trigger) {
      event.preventDefault();
      event.stopPropagation();
      var menu = trigger.closest(panelActionMenuSelector);
      if (!menu) {
        return;
      }
      togglePanelActionMenu(menu);
      return;
    }
    if (event.target.closest(panelActionMenuItemSelector)) {
      window.setTimeout(function () {
        return closePanelActionMenus(false);
      }, 0);
      return;
    }
    if (!event.target.closest(panelActionMenuSelector)) {
      closePanelActionMenus(false);
    }
  });
  document.addEventListener('keydown', function (event) {
    var trigger = event.target.closest(panelActionMenuTriggerSelector);
    if (trigger) {
      var _menu = trigger.closest(panelActionMenuSelector);
      if (!_menu) {
        return;
      }
      if (event.key === 'Escape') {
        event.preventDefault();
        closePanelActionMenus(true);
        return;
      }
      if (event.key === 'Enter' || event.key === ' ' || event.key === 'Spacebar') {
        event.preventDefault();
        togglePanelActionMenu(_menu);
        return;
      }
      if (event.key === 'ArrowDown' || event.key === 'ArrowUp' || event.key === 'Home' || event.key === 'End') {
        event.preventDefault();
        openPanelActionMenu(_menu, event.key === 'ArrowUp' || event.key === 'End' ? -1 : 0);
      }
      return;
    }
    var panel = event.target.closest(panelActionMenuPanelSelector);
    if (!panel) {
      if (event.key === 'Escape') {
        closePanelActionMenus(true);
      }
      return;
    }
    var menu = panel.closest(panelActionMenuSelector);
    if (!menu) {
      return;
    }
    var items = getPanelActionMenuItems(menu);
    if (!items.length) {
      return;
    }
    var currentIndex = items.indexOf(document.activeElement);
    if (event.key === 'Escape') {
      event.preventDefault();
      closePanelActionMenus(true);
      return;
    }
    if (event.key === 'ArrowDown') {
      var _items;
      event.preventDefault();
      (_items = items[(currentIndex + 1) % items.length]) === null || _items === void 0 || _items.focus();
      return;
    }
    if (event.key === 'ArrowUp') {
      var _items2;
      event.preventDefault();
      (_items2 = items[(currentIndex - 1 + items.length) % items.length]) === null || _items2 === void 0 || _items2.focus();
      return;
    }
    if (event.key === 'Home') {
      var _items$;
      event.preventDefault();
      (_items$ = items[0]) === null || _items$ === void 0 || _items$.focus();
      return;
    }
    if (event.key === 'End') {
      var _items3;
      event.preventDefault();
      (_items3 = items[items.length - 1]) === null || _items3 === void 0 || _items3.focus();
    }
  });
  document.addEventListener('scroll', function () {
    if (activePanelActionMenu) {
      closePanelActionMenus(false);
    }
  }, true);
  window.addEventListener('resize', function () {
    if (activePanelActionMenu) {
      closePanelActionMenus(false);
    }
  });
  var initializeUserGroupToggles = function initializeUserGroupToggles() {
    var scope = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : document;
    scope.querySelectorAll('[data-users-group-shell]').forEach(function (shell) {
      if (shell.dataset.usersGroupBound === 'true') {
        return;
      }
      var toggle = shell.querySelector('[data-users-group-toggle]');
      if (!toggle) {
        return;
      }
      var setOpen = function setOpen(open) {
        shell.dataset.open = open ? 'true' : 'false';
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      };
      setOpen(shell.dataset.open !== 'false');
      toggle.addEventListener('click', function (event) {
        event.preventDefault();
        setOpen(shell.dataset.open !== 'true');
      });
      shell.dataset.usersGroupBound = 'true';
    });
  };
  initializeUserGroupToggles();
  var refreshInteractiveBindings = function refreshInteractiveBindings() {
    closePanelActionMenus(false);
    initializeUserGroupToggles();
  };
  var setupLivePanelFilters = function setupLivePanelFilters() {
    document.querySelectorAll('[data-panel-live-search="true"], [data-panel-filter-live="true"]').forEach(function (form) {
      var targetSelector = form.dataset.panelLiveSearchTarget || form.dataset.panelFilterLiveTarget || '';
      var target = targetSelector ? document.querySelector(targetSelector) : null;
      if (!target) {
        return;
      }
      var loading = form.querySelector('[data-panel-live-search-loading], [data-panel-filter-loading]');
      var searchInput = form.querySelector('[data-panel-live-search-input], [data-panel-filter-search]');
      var liveFields = Array.from(form.querySelectorAll('[data-panel-filter-live-field], [data-panel-live-search-input], [data-panel-filter-search]'));
      var submitButton = form.querySelector('button[type="submit"]');
      var debounceDelay = Math.max(0, parseInt(form.dataset.panelLiveSearchDebounce || form.dataset.panelFilterDebounce || '500', 10) || 500);
      var controller = null;
      var debounceTimer = null;
      var sequence = 0;
      var setLoading = function setLoading(isLoading) {
        if (loading) {
          loading.hidden = !isLoading;
        }
        if (submitButton) {
          submitButton.disabled = isLoading;
        }
      };
      var buildRequestUrl = function buildRequestUrl() {
        var sourceUrl = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : null;
        if (sourceUrl) {
          return new URL(sourceUrl, window.location.origin);
        }
        var url = new URL(form.getAttribute('action') || window.location.href, window.location.origin);
        var formData = new FormData(form);
        url.search = new URLSearchParams(formData).toString();
        return url;
      };
      var syncUrl = function syncUrl(url) {
        window.history.replaceState({}, '', "".concat(url.pathname).concat(url.search).concat(url.hash));
      };
      var replaceTarget = function replaceTarget(html) {
        var parser = new DOMParser();
        var doc = parser.parseFromString(html, 'text/html');
        var nextTarget = targetSelector ? doc.querySelector(targetSelector) : null;
        if (!nextTarget) {
          return false;
        }
        target.outerHTML = nextTarget.outerHTML;
        target = targetSelector ? document.querySelector(targetSelector) : null;
        return true;
      };
      var runFetch = /*#__PURE__*/function () {
        var _ref = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee() {
          var sourceUrl,
            requestUrl,
            currentSequence,
            response,
            html,
            _args = arguments,
            _t;
          return _regenerator().w(function (_context) {
            while (1) switch (_context.p = _context.n) {
              case 0:
                sourceUrl = _args.length > 0 && _args[0] !== undefined ? _args[0] : null;
                requestUrl = buildRequestUrl(sourceUrl);
                currentSequence = ++sequence;
                if (controller) {
                  controller.abort();
                }
                controller = new AbortController();
                setLoading(true);
                _context.p = 1;
                _context.n = 2;
                return fetch(requestUrl.toString(), {
                  headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Cache-Control': 'no-cache'
                  },
                  signal: controller.signal
                });
              case 2:
                response = _context.v;
                if (response.ok) {
                  _context.n = 3;
                  break;
                }
                throw new Error("HTTP ".concat(response.status));
              case 3:
                _context.n = 4;
                return response.text();
              case 4:
                html = _context.v;
                if (!(currentSequence !== sequence)) {
                  _context.n = 5;
                  break;
                }
                return _context.a(2);
              case 5:
                if (replaceTarget(html)) {
                  refreshInteractiveBindings();
                  syncUrl(requestUrl);
                }
                _context.n = 8;
                break;
              case 6:
                _context.p = 6;
                _t = _context.v;
                if (!(_t && _t.name === 'AbortError')) {
                  _context.n = 7;
                  break;
                }
                return _context.a(2);
              case 7:
                console.error('No se pudo actualizar el filtro en vivo.', _t);
              case 8:
                _context.p = 8;
                if (currentSequence === sequence) {
                  setLoading(false);
                }
                return _context.f(8);
              case 9:
                return _context.a(2);
            }
          }, _callee, null, [[1, 6, 8, 9]]);
        }));
        return function runFetch() {
          return _ref.apply(this, arguments);
        };
      }();
      var scheduleFetch = function scheduleFetch() {
        window.clearTimeout(debounceTimer);
        debounceTimer = window.setTimeout(function () {
          runFetch();
        }, debounceDelay);
      };
      if (searchInput) {
        searchInput.addEventListener('input', function () {
          scheduleFetch();
        });
        searchInput.addEventListener('keydown', function (event) {
          if (event.key === 'Enter') {
            event.preventDefault();
            window.clearTimeout(debounceTimer);
            runFetch();
          }
        });
      }
      liveFields.forEach(function (field) {
        if (field === searchInput) {
          return;
        }
        field.addEventListener('change', function () {
          scheduleFetch();
        });
      });
      form.addEventListener('submit', function (event) {
        event.preventDefault();
        window.clearTimeout(debounceTimer);
        runFetch();
      });
      document.addEventListener('click', function (event) {
        var link = event.target.closest("".concat(targetSelector, " nav[role=\"navigation\"] a[href]"));
        if (!link) {
          return;
        }
        var linkUrl = link.getAttribute('href');
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
  var setupModal = function setupModal(options) {
    var modal = document.querySelector(options.modalSelector);
    if (!modal) {
      return;
    }
    if (modal.dataset.modalBound === 'true') {
      return;
    }
    var openButtons = document.querySelectorAll(options.openSelector);
    var closeButtons = modal.querySelectorAll(options.closeSelector);
    var idInput = options.idInputSelector ? modal.querySelector(options.idInputSelector) : null;
    var summary = options.summarySelector ? modal.querySelector(options.summarySelector) : null;
    var dateInput = modal.querySelector('[data-maintenance-date-input]');
    var programmedDateDisplay = modal.querySelector('[data-maintenance-programmed-date-display]');
    var form = modal.querySelector('form');
    var submitButton = form ? form.querySelector('button[type="submit"]') : null;
    var submitButtonLabel = submitButton ? submitButton.textContent || '' : '';
    var closeUrl = modal.dataset.modalCloseUrl || '';
    var fileDropzone = modal.querySelector('[data-maintenance-file-dropzone]');
    var dateField = modal.querySelector('[data-maintenance-date-field]');
    var dateTrigger = modal.querySelector('[data-maintenance-date-trigger]');
    var dateDisplay = modal.querySelector('[data-maintenance-date-display]');
    var datePicker = modal.querySelector('[data-maintenance-date-picker]');
    var dateMonthLabel = modal.querySelector('[data-maintenance-date-month]');
    var dateYearSelect = modal.querySelector('[data-maintenance-date-year]');
    var dateGrid = modal.querySelector('[data-maintenance-date-grid]');
    var datePrev = modal.querySelector('[data-maintenance-date-prev]');
    var dateNext = modal.querySelector('[data-maintenance-date-next]');
    var dateToday = modal.querySelector('[data-maintenance-date-today]');
    var dateClose = modal.querySelector('[data-maintenance-date-close]');
    var shouldPreserveMaintenanceDate = (dateInput === null || dateInput === void 0 ? void 0 : dateInput.dataset.maintenanceDatePreserve) === '1';
    var hasMaintenanceDatePicker = Boolean(dateField && dateTrigger && dateDisplay && datePicker && dateMonthLabel && dateYearSelect && dateGrid);
    var fileDropzoneInitialized = false;
    var datePickerInitialized = false;
    var datePickerOpen = false;
    var currentViewDate = null;
    var datePickerScrollHandler = null;
    var datePickerResizeHandler = null;
    var formatDateDisplay = function formatDateDisplay(value) {
      if (!value) {
        return '';
      }
      var match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(value);
      if (!match) {
        return value;
      }
      return "".concat(match[3], "/").concat(match[2], "/").concat(match[1]);
    };
    var normalizeDate = function normalizeDate(date) {
      return new Date(date.getFullYear(), date.getMonth(), date.getDate());
    };
    var parseDateValue = function parseDateValue(value) {
      var match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(String(value || '').trim());
      if (!match) {
        return null;
      }
      var year = parseInt(match[1], 10);
      var month = parseInt(match[2], 10) - 1;
      var day = parseInt(match[3], 10);
      return new Date(year, month, day);
    };
    var maintenanceDateMin = parseDateValue((dateInput === null || dateInput === void 0 ? void 0 : dateInput.dataset.maintenanceDateMin) || '');
    var maintenanceDateMax = parseDateValue((dateInput === null || dateInput === void 0 ? void 0 : dateInput.dataset.maintenanceDateMax) || '');
    var allowNonBusinessDays = (dateInput === null || dateInput === void 0 ? void 0 : dateInput.dataset.maintenanceDateAllowNonBusiness) === '1';
    var maintenanceHolidaySet = function () {
      try {
        var parsed = JSON.parse(String((dateInput === null || dateInput === void 0 ? void 0 : dateInput.dataset.maintenanceDateHolidays) || '[]'));
        if (!Array.isArray(parsed)) {
          return new Set();
        }
        return new Set(parsed.filter(function (value) {
          return typeof value === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(value);
        }));
      } catch (error) {
        return new Set();
      }
    }();
    var formatDateValue = function formatDateValue(date) {
      var local = normalizeDate(date);
      var year = local.getFullYear();
      var month = String(local.getMonth() + 1).padStart(2, '0');
      var day = String(local.getDate()).padStart(2, '0');
      return "".concat(year, "-").concat(month, "-").concat(day);
    };
    var isDateBeforeMin = function isDateBeforeMin(date) {
      return maintenanceDateMin !== null && date.getTime() < maintenanceDateMin.getTime();
    };
    var isDateAfterMax = function isDateAfterMax(date) {
      return maintenanceDateMax !== null && date.getTime() > maintenanceDateMax.getTime();
    };
    var isWeekendDay = function isWeekendDay(date) {
      return [0, 6].includes(date.getDay());
    };
    var isHoliday = function isHoliday(date) {
      return maintenanceHolidaySet.has(formatDateValue(date));
    };
    var isNonBusinessDay = function isNonBusinessDay(date) {
      return !allowNonBusinessDays && (isWeekendDay(date) || isHoliday(date));
    };
    var advanceToBusinessDay = function advanceToBusinessDay(date) {
      var current = normalizeDate(date);
      while (isNonBusinessDay(current)) {
        current = new Date(current.getFullYear(), current.getMonth(), current.getDate() + 1);
      }
      return current;
    };
    var clampDateValue = function clampDateValue(value) {
      var parsed = parseDateValue(value);
      if (!parsed) {
        return value;
      }
      var normalized = normalizeDate(parsed);
      if (isDateBeforeMin(normalized)) {
        normalized = normalizeDate(maintenanceDateMin);
      }
      if (isDateAfterMax(normalized)) {
        normalized = normalizeDate(maintenanceDateMax);
      }
      if (!allowNonBusinessDays) {
        normalized = advanceToBusinessDay(normalized);
      }
      return formatDateValue(normalized);
    };
    var formatDateLongEs = function formatDateLongEs(date) {
      return new Intl.DateTimeFormat('es-CO', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric'
      }).format(date);
    };
    var formatMonthYearEs = function formatMonthYearEs(date) {
      return new Intl.DateTimeFormat('es-CO', {
        month: 'long',
        year: 'numeric'
      }).format(date);
    };
    var capitalizeFirst = function capitalizeFirst(value) {
      return value ? value.charAt(0).toUpperCase() + value.slice(1) : value;
    };
    var getSelectedDate = function getSelectedDate() {
      return parseDateValue((dateInput === null || dateInput === void 0 ? void 0 : dateInput.value) || '') || normalizeDate(new Date());
    };
    var syncDateTrigger = function syncDateTrigger() {
      if (!dateDisplay || !dateInput) {
        return;
      }
      var selected = parseDateValue(dateInput.value);
      dateDisplay.textContent = selected ? formatDateDisplay(formatDateValue(selected)) : 'Selecciona una fecha';
    };
    var syncViewToSelected = function syncViewToSelected() {
      currentViewDate = normalizeDate(getSelectedDate());
      currentViewDate.setDate(1);
    };
    var positionDatePicker = function positionDatePicker() {
      if (!datePicker || !dateTrigger || datePicker.hidden) {
        return;
      }
      var triggerRect = dateTrigger.getBoundingClientRect();
      var padding = 16;
      var availableWidth = Math.max(0, window.innerWidth - padding * 2);
      var preferredWidth = Math.min(720, Math.max(triggerRect.width, 320));
      var finalWidth = Math.max(Math.min(preferredWidth, availableWidth), Math.min(availableWidth, 280));
      datePicker.style.width = "".concat(finalWidth, "px");
      datePicker.style.maxHeight = "".concat(Math.max(240, window.innerHeight - padding * 2), "px");
      var pickerRect = datePicker.getBoundingClientRect();
      var left = Math.max(padding, (window.innerWidth - finalWidth) / 2);
      var top = Math.max(padding, (window.innerHeight - pickerRect.height) / 2);
      datePicker.style.left = "".concat(left, "px");
      datePicker.style.top = "".concat(top, "px");
    };
    var closeDatePicker = function closeDatePicker() {
      var restoreFocus = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : true;
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
        window.requestAnimationFrame(function () {
          dateTrigger.focus();
        });
      }
    };
    var updateSelectedDate = function updateSelectedDate(value) {
      var _ref2 = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {},
        _ref2$keepView = _ref2.keepView,
        keepView = _ref2$keepView === void 0 ? false : _ref2$keepView,
        _ref2$keepOpen = _ref2.keepOpen,
        keepOpen = _ref2$keepOpen === void 0 ? false : _ref2$keepOpen;
      if (!dateInput) {
        return;
      }
      var selected = parseDateValue(value);
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
    var renderDatePicker = function renderDatePicker() {
      if (!hasMaintenanceDatePicker || !currentViewDate) {
        return;
      }
      var viewYear = currentViewDate.getFullYear();
      var viewMonth = currentViewDate.getMonth();
      var selectedDate = parseDateValue((dateInput === null || dateInput === void 0 ? void 0 : dateInput.value) || '');
      var today = normalizeDate(new Date());
      dateMonthLabel.textContent = capitalizeFirst(formatMonthYearEs(currentViewDate));
      dateYearSelect.innerHTML = '';
      var startYear = Math.max(1900, viewYear - 10);
      var endYear = viewYear + 10;
      for (var year = startYear; year <= endYear; year += 1) {
        var option = document.createElement('option');
        option.value = String(year);
        option.textContent = String(year);
        if (year === viewYear) {
          option.selected = true;
        }
        dateYearSelect.appendChild(option);
      }
      dateGrid.innerHTML = '';
      var start = new Date(viewYear, viewMonth, 1);
      var startOffset = (start.getDay() + 6) % 7;
      var firstCell = new Date(viewYear, viewMonth, 1 - startOffset);
      var _loop = function _loop() {
        var current = new Date(firstCell.getFullYear(), firstCell.getMonth(), firstCell.getDate() + index);
        var button = document.createElement('button');
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
        button.addEventListener('pointerdown', function (event) {
          event.preventDefault();
          event.stopPropagation();
          updateSelectedDate(button.dataset.maintenanceDateValue || '');
        });
        button.addEventListener('click', function (event) {
          event.preventDefault();
          event.stopPropagation();
          updateSelectedDate(button.dataset.maintenanceDateValue || '');
        });
        dateGrid.appendChild(button);
      };
      for (var index = 0; index < 42; index += 1) {
        _loop();
      }
      positionDatePicker();
    };
    var openDatePicker = function openDatePicker() {
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
      datePickerScrollHandler = function datePickerScrollHandler() {
        return positionDatePicker();
      };
      datePickerResizeHandler = function datePickerResizeHandler() {
        return positionDatePicker();
      };
      window.addEventListener('scroll', datePickerScrollHandler, true);
      window.addEventListener('resize', datePickerResizeHandler);
      window.requestAnimationFrame(function () {
        positionDatePicker();
        var selectedButton = dateGrid.querySelector('.maintenance-date-picker-day--selected') || dateGrid.querySelector('.maintenance-date-picker-day:not([disabled])');
        if (selectedButton) {
          selectedButton.focus();
        }
      });
    };
    var moveGridFocus = function moveGridFocus(activeButton, offset) {
      var buttons = Array.from(dateGrid.querySelectorAll('.maintenance-date-picker-day:not([disabled])'));
      var currentIndex = buttons.indexOf(activeButton);
      if (currentIndex === -1) {
        return;
      }
      var nextIndex = Math.max(0, Math.min(buttons.length - 1, currentIndex + offset));
      var nextButton = buttons[nextIndex];
      if (nextButton) {
        nextButton.focus();
      }
    };
    var getVisibleDateTrigger = function getVisibleDateTrigger() {
      if (!dateTrigger) {
        return null;
      }
      if (dateTrigger.disabled || dateTrigger.closest('[hidden]')) {
        return null;
      }
      return dateTrigger;
    };
    var focusFirstField = function focusFirstField() {
      var target = modal.querySelector('[aria-invalid="true"]') || getVisibleDateTrigger() || modal.querySelector('input:not([type="hidden"]):not([type="file"]), select, textarea, button[type="submit"]');
      if (target && typeof target.focus === 'function') {
        target.focus();
      }
    };
    var setupUserMaintenanceBaseField = function setupUserMaintenanceBaseField() {
      var typeSelect = modal.querySelector('[data-user-equipo-type-select]');
      var field = modal.querySelector('[data-user-last-maintenance-field]');
      var dateInput = (field === null || field === void 0 ? void 0 : field.querySelector('[data-user-last-maintenance-input]')) || null;
      var fieldLabel = (field === null || field === void 0 ? void 0 : field.querySelector('[data-user-last-maintenance-label]')) || null;
      var nativeDateInput = (field === null || field === void 0 ? void 0 : field.querySelector('[data-user-native-date-input]')) || null;
      var nativeDateTrigger = (field === null || field === void 0 ? void 0 : field.querySelector('[data-user-native-date-trigger]')) || null;
      var nativeDateDisplay = (field === null || field === void 0 ? void 0 : field.querySelector('[data-maintenance-date-display]')) || null;
      if (!typeSelect || !field || !dateInput || !fieldLabel) {
        return;
      }
      var syncNativeDateDisplay = function syncNativeDateDisplay() {
        if (!nativeDateInput || !nativeDateDisplay) {
          return;
        }
        var value = String(nativeDateInput.value || '').trim();
        dateInput.value = value;
        nativeDateDisplay.textContent = value ? formatDateDisplay(value) : 'Selecciona una fecha';
      };
      var syncFieldState = function syncFieldState() {
        var isNew = typeSelect.value === 'nuevo';
        field.hidden = false;
        field.setAttribute('aria-hidden', 'false');
        dateInput.disabled = false;
        fieldLabel.innerHTML = isNew ? fieldLabel.dataset.labelNuevo || 'Fecha de compra' : fieldLabel.dataset.labelNormal || 'Fecha de último mantenimiento';
      };
      if (nativeDateInput) {
        nativeDateInput.value = String(dateInput.value || nativeDateInput.value || '').trim();
        nativeDateInput.addEventListener('input', syncNativeDateDisplay);
        nativeDateInput.addEventListener('change', syncNativeDateDisplay);
        syncNativeDateDisplay();
      }
      if (nativeDateTrigger && nativeDateInput) {
        nativeDateTrigger.addEventListener('click', function (event) {
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
    var restoreSubmitButton = function restoreSubmitButton() {
      if (!submitButton) {
        return;
      }
      submitButton.disabled = false;
      submitButton.textContent = submitButtonLabel;
    };
    var setupFileDropzone = function setupFileDropzone() {
      if (!fileDropzone || fileDropzoneInitialized) {
        return;
      }
      var fileInput = fileDropzone.querySelector('[data-maintenance-file-input]');
      var fileName = fileDropzone.querySelector('[data-maintenance-file-name]');
      var clearButton = fileDropzone.querySelector('[data-maintenance-file-clear]');
      var defaultLabel = fileName ? fileName.textContent || 'Sin archivo seleccionado' : 'Sin archivo seleccionado';
      if (!fileInput || !fileName) {
        return;
      }
      var updateState = function updateState() {
        var file = fileInput.files && fileInput.files[0] ? fileInput.files[0] : null;
        if (file) {
          fileDropzone.classList.add('maintenance-upload--has-file');
          fileName.textContent = file.name;
        } else {
          fileDropzone.classList.remove('maintenance-upload--has-file');
          fileName.textContent = defaultLabel;
        }
      };
      var setFileList = function setFileList(files) {
        if (!files || files.length === 0) {
          fileInput.value = '';
          updateState();
          return;
        }
        var file = files[0];
        var isPdf = (file.type || '').toLowerCase().includes('pdf') || /\.pdf$/i.test(file.name || '');
        if (!isPdf) {
          return;
        }
        if (typeof DataTransfer !== 'undefined') {
          var transfer = new DataTransfer();
          transfer.items.add(file);
          fileInput.files = transfer.files;
        }
        updateState();
      };
      fileInput.addEventListener('change', updateState);
      if (clearButton) {
        clearButton.addEventListener('click', function () {
          fileInput.value = '';
          updateState();
          fileInput.focus();
        });
      }
      fileDropzone.addEventListener('dragover', function (event) {
        event.preventDefault();
        fileDropzone.classList.add('maintenance-upload--dragover');
      });
      fileDropzone.addEventListener('dragleave', function () {
        fileDropzone.classList.remove('maintenance-upload--dragover');
      });
      fileDropzone.addEventListener('drop', function (event) {
        event.preventDefault();
        fileDropzone.classList.remove('maintenance-upload--dragover');
        setFileList(event.dataTransfer ? event.dataTransfer.files : null);
      });
      updateState();
      form === null || form === void 0 || form.addEventListener('reset', function () {
        window.setTimeout(updateState, 0);
      });
      fileDropzoneInitialized = true;
    };
    var closeModal = function closeModal() {
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
        var fileInput = fileDropzone.querySelector('[data-maintenance-file-input]');
        if (fileInput) {
          fileInput.value = '';
        }
      }
    };
    var openModal = function openModal(button) {
      var mantenimientoId = button.dataset.maintenanceId || '';
      var resumen = button.dataset.maintenanceSummary || '';
      var suggestedDate = button.dataset.maintenanceSuggestedDate || '';
      var programmedDate = button.dataset.maintenanceProgrammedDate || '';
      var target = button.dataset.maintenanceTarget || '';
      var todayValue = formatDateValue(new Date());
      if (idInput) {
        idInput.value = mantenimientoId;
      }
      if (programmedDateDisplay) {
        programmedDateDisplay.value = formatDateDisplay(programmedDate || suggestedDate) || 'Sin fecha programada';
      }
      if (dateInput && !hasMaintenanceDatePicker) {
        var minDate = dateInput.getAttribute('min') || '';
        var maxDate = dateInput.getAttribute('max') || '';
        var value = '';
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
      window.requestAnimationFrame(function () {
        if (typeof modal.focus === 'function') {
          modal.focus();
        }
      });
      focusFirstField();
    };
    openButtons.forEach(function (button) {
      button.addEventListener('click', function () {
        return openModal(button);
      });
    });
    closeButtons.forEach(function (button) {
      button.addEventListener('click', closeModal);
    });
    if (hasMaintenanceDatePicker && !datePickerInitialized) {
      var focusSelectedDay = function focusSelectedDay() {
        var selectedButton = dateGrid.querySelector('.maintenance-date-picker-day--selected') || dateGrid.querySelector('.maintenance-date-picker-day');
        if (selectedButton) {
          selectedButton.focus();
        }
      };
      dateTrigger.addEventListener('click', function (event) {
        event.preventDefault();
        if (datePickerOpen) {
          closeDatePicker(true);
          return;
        }
        openDatePicker();
      });
      dateTrigger.addEventListener('keydown', function (event) {
        if (event.key === 'ArrowDown' || event.key === 'Enter' || event.key === ' ') {
          event.preventDefault();
          openDatePicker();
        }
      });
      datePrev === null || datePrev === void 0 || datePrev.addEventListener('click', function (event) {
        event.preventDefault();
        event.stopPropagation();
        if (!currentViewDate) {
          syncViewToSelected();
        }
        currentViewDate = new Date(currentViewDate.getFullYear(), currentViewDate.getMonth() - 1, 1);
        renderDatePicker();
        focusSelectedDay();
      });
      dateNext === null || dateNext === void 0 || dateNext.addEventListener('click', function (event) {
        event.preventDefault();
        event.stopPropagation();
        if (!currentViewDate) {
          syncViewToSelected();
        }
        currentViewDate = new Date(currentViewDate.getFullYear(), currentViewDate.getMonth() + 1, 1);
        renderDatePicker();
        focusSelectedDay();
      });
      dateToday === null || dateToday === void 0 || dateToday.addEventListener('click', function (event) {
        event.preventDefault();
        event.stopPropagation();
        updateSelectedDate(clampDateValue(formatDateValue(new Date())));
      });
      dateClose === null || dateClose === void 0 || dateClose.addEventListener('click', function (event) {
        event.preventDefault();
        event.stopPropagation();
        closeDatePicker(true);
      });
      dateYearSelect.addEventListener('change', function () {
        if (!currentViewDate) {
          syncViewToSelected();
        }
        var selectedYear = parseInt(dateYearSelect.value, 10);
        if (Number.isNaN(selectedYear)) {
          return;
        }
        currentViewDate = new Date(selectedYear, currentViewDate.getMonth(), 1);
        renderDatePicker();
        focusSelectedDay();
      });
      dateGrid.addEventListener('keydown', function (event) {
        var activeButton = event.target.closest('.maintenance-date-picker-day');
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
            moveGridFocus(activeButton, 6 - Array.from(dateGrid.querySelectorAll('.maintenance-date-picker-day')).indexOf(activeButton) % 7);
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
      datePicker.addEventListener('mousedown', function (event) {
        event.stopPropagation();
      });
      datePicker.addEventListener('click', function (event) {
        event.stopPropagation();
      });
      document.addEventListener('click', function (event) {
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
    modal.addEventListener('click', function (event) {
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
      form.addEventListener('submit', function () {
        submitButton.disabled = true;
        submitButton.textContent = 'Guardando...';
        closeDatePicker(false);
      });
    }
    if (form) {
      form.addEventListener('reset', function () {
        window.setTimeout(function () {
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
    document.addEventListener('keydown', function (event) {
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
      window.requestAnimationFrame(function () {
        focusFirstField();
      });
    }
    setupUserMaintenanceBaseField();
    modal.dataset.modalBound = 'true';
  };
  var userCreateModalOptions = {
    modalSelector: '[data-user-create-modal]',
    openSelector: '[data-user-create-open]',
    closeSelector: '[data-user-create-close]',
    idInputSelector: null,
    summarySelector: null,
    defaultSummary: ''
  };
  var userEditModalOptions = {
    modalSelector: '[data-user-edit-modal]',
    openSelector: '[data-user-edit-open]',
    closeSelector: '[data-user-edit-close]',
    idInputSelector: null,
    summarySelector: null,
    defaultSummary: ''
  };
  var maintenanceModalOptions = {
    modalSelector: '[data-maintenance-modal]',
    openSelector: '[data-open-maintenance-modal]',
    closeSelector: '[data-maintenance-modal-close]',
    idInputSelector: '[data-maintenance-id-input]',
    summarySelector: '[data-maintenance-summary]',
    defaultSummary: 'Selecciona una acción para este mantenimiento.'
  };
  refreshInteractiveBindings = function refreshInteractiveBindings() {
    closePanelActionMenus(false);
    initializeUserGroupToggles();
    setupModal(userCreateModalOptions);
    setupModal(userEditModalOptions);
    setupModal(maintenanceModalOptions);
  };
  refreshInteractiveBindings();
});

/***/ }),

/***/ "./resources/css/app.css":
/*!*******************************!*\
  !*** ./resources/css/app.css ***!
  \*******************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = __webpack_modules__;
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/chunk loaded */
/******/ 	(() => {
/******/ 		var deferred = [];
/******/ 		__webpack_require__.O = (result, chunkIds, fn, priority) => {
/******/ 			if(chunkIds) {
/******/ 				priority = priority || 0;
/******/ 				for(var i = deferred.length; i > 0 && deferred[i - 1][2] > priority; i--) deferred[i] = deferred[i - 1];
/******/ 				deferred[i] = [chunkIds, fn, priority];
/******/ 				return;
/******/ 			}
/******/ 			var notFulfilled = Infinity;
/******/ 			for (var i = 0; i < deferred.length; i++) {
/******/ 				var [chunkIds, fn, priority] = deferred[i];
/******/ 				var fulfilled = true;
/******/ 				for (var j = 0; j < chunkIds.length; j++) {
/******/ 					if ((priority & 1 === 0 || notFulfilled >= priority) && Object.keys(__webpack_require__.O).every((key) => (__webpack_require__.O[key](chunkIds[j])))) {
/******/ 						chunkIds.splice(j--, 1);
/******/ 					} else {
/******/ 						fulfilled = false;
/******/ 						if(priority < notFulfilled) notFulfilled = priority;
/******/ 					}
/******/ 				}
/******/ 				if(fulfilled) {
/******/ 					deferred.splice(i--, 1)
/******/ 					var r = fn();
/******/ 					if (r !== undefined) result = r;
/******/ 				}
/******/ 			}
/******/ 			return result;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/jsonp chunk loading */
/******/ 	(() => {
/******/ 		// no baseURI
/******/ 		
/******/ 		// object to store loaded and loading chunks
/******/ 		// undefined = chunk not loaded, null = chunk preloaded/prefetched
/******/ 		// [resolve, reject, Promise] = chunk loading, 0 = chunk loaded
/******/ 		var installedChunks = {
/******/ 			"/js/app": 0,
/******/ 			"css/app": 0
/******/ 		};
/******/ 		
/******/ 		// no chunk on demand loading
/******/ 		
/******/ 		// no prefetching
/******/ 		
/******/ 		// no preloaded
/******/ 		
/******/ 		// no HMR
/******/ 		
/******/ 		// no HMR manifest
/******/ 		
/******/ 		__webpack_require__.O.j = (chunkId) => (installedChunks[chunkId] === 0);
/******/ 		
/******/ 		// install a JSONP callback for chunk loading
/******/ 		var webpackJsonpCallback = (parentChunkLoadingFunction, data) => {
/******/ 			var [chunkIds, moreModules, runtime] = data;
/******/ 			// add "moreModules" to the modules object,
/******/ 			// then flag all "chunkIds" as loaded and fire callback
/******/ 			var moduleId, chunkId, i = 0;
/******/ 			if(chunkIds.some((id) => (installedChunks[id] !== 0))) {
/******/ 				for(moduleId in moreModules) {
/******/ 					if(__webpack_require__.o(moreModules, moduleId)) {
/******/ 						__webpack_require__.m[moduleId] = moreModules[moduleId];
/******/ 					}
/******/ 				}
/******/ 				if(runtime) var result = runtime(__webpack_require__);
/******/ 			}
/******/ 			if(parentChunkLoadingFunction) parentChunkLoadingFunction(data);
/******/ 			for(;i < chunkIds.length; i++) {
/******/ 				chunkId = chunkIds[i];
/******/ 				if(__webpack_require__.o(installedChunks, chunkId) && installedChunks[chunkId]) {
/******/ 					installedChunks[chunkId][0]();
/******/ 				}
/******/ 				installedChunks[chunkIds[i]] = 0;
/******/ 			}
/******/ 			return __webpack_require__.O(result);
/******/ 		}
/******/ 		
/******/ 		var chunkLoadingGlobal = self["webpackChunk"] = self["webpackChunk"] || [];
/******/ 		chunkLoadingGlobal.forEach(webpackJsonpCallback.bind(null, 0));
/******/ 		chunkLoadingGlobal.push = webpackJsonpCallback.bind(null, chunkLoadingGlobal.push.bind(chunkLoadingGlobal));
/******/ 	})();
/******/ 	
/************************************************************************/
/******/ 	
/******/ 	// startup
/******/ 	// Load entry module and return exports
/******/ 	// This entry module depends on other loaded chunks and execution need to be delayed
/******/ 	__webpack_require__.O(undefined, ["css/app"], () => (__webpack_require__("./resources/js/app.js")))
/******/ 	var __webpack_exports__ = __webpack_require__.O(undefined, ["css/app"], () => (__webpack_require__("./resources/css/app.css")))
/******/ 	__webpack_exports__ = __webpack_require__.O(__webpack_exports__);
/******/ 	
/******/ })()
;