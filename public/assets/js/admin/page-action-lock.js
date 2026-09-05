/**
 * Global page-level action lock with button-only visual feedback.
 *
 * - Locks actionable controls on the CURRENT page/document only.
 * - Shows an inline spinner ONLY on the control that started the action.
 * - Does NOT add page loaders, overlays, or content blockers.
 * - Confirmation dialogs (SweetAlert / data-confirm): soft-gate only until confirmed.
 */
(function (window, document) {
    'use strict';

    if (window.PageActionLock) {
        return;
    }

    var GESTURE_MS = 4000;
    var NO_REQUEST_UNLOCK_MS = 800;
    var HARD_LOCK_MAX_MS = 90000;

    function processingLabel() {
        return (window.i18n && window.i18n.loading) || 'Processing...';
    }

    // Icon-only controls (row action buttons: .btn-icon, or any button/link whose
    // whole content is an icon with no visible text) can't fit "Processing..."
    // without breaking their layout — show a small inline spinner instead.
    function isIconOnlyButton(el) {
        if (el.classList.contains('btn-icon')) {
            return true;
        }
        return (el.textContent || '').replace(/\s+/g, '') === '';
    }

    var IGNORE_URL_RE = /select2|global-search|notifications?\/|barcode|heartbeat|sanctum\/csrf|telescope|horizon|_debugbar|livewire\/update/i;

    var ACTIONABLE_SELECTOR = [
        'button.btn',
        'button[type="submit"]',
        'input[type="submit"]',
        'input[type="button"].btn',
        'a.btn',
        'a[id^="edit"]',
        'a[id^="Edit"]',
        'a[id^="delete"]',
        'a[id^="Delete"]',
        'a[id^="view"]',
        'a[id^="View"]',
        'a[id^="approve"]',
        'a[id^="reject"]',
        'a[id^="restore"]',
        'a[id^="print"]',
        'a[id^="export"]',
        'a[id^="import"]',
        '[data-action-lock="on"]',
        '.import-export-import-btn',
        '.import-export-export-btn',
        'input.form-check-input[class*="status"]',
        'input.form-check-input[class*="Status"]',
        '#saveBtn',
        '#btnSave',
        '#search_btn',
        '#reset_filter',
        '#btn_pdf',
        '#btn_excel',
        '#btn_csv',
        'button[id^="createNew"]',
        'a[id^="createNew"]',
        '.dt-button',
        '.dt-paging-button',
        '.paginate_button',
        'a.page-link',
    ].join(',');

    var CONFIRM_GATE_RE = /(?:^|[\s_-])(delete|remove|destroy|reject|terminate|clearance|approve)(?:$|[\s_-])/i;
    // Also match common id/class prefixes: deleteCategory, btn-delete, #delete*
    var CONFIRM_GATE_ID_RE = /^(delete|remove|destroy|reject|approve|restore)/i;

    var state = {
        hardLocked: false,
        softGated: false,
        inFlight: 0,
        trigger: null,
        lastActionable: null,
        lastGestureAt: 0,
        lockedNodes: [],
        softGateTimer: null,
        hardLockTimer: null,
        noRequestTimer: null,
        originalHtml: null,
        originalValue: null,
        originalDisabled: false,
        originalAriaBusy: null,
        navigationPending: false,
    };

    function now() {
        return Date.now();
    }

    function closestActionable(target) {
        if (!target || !target.closest) {
            return null;
        }
        if (target.closest('.swal2-container')) {
            return null;
        }
        var el = target.closest(ACTIONABLE_SELECTOR);
        if (!el) {
            return null;
        }
        if (el.id === 'toggleFilter') {
            return null;
        }
        if (el.matches('[data-action-lock="off"], [data-no-action-lock]')) {
            return null;
        }
        if (el.matches('[data-bs-dismiss], [data-dismiss], [data-bs-toggle="dropdown"], [data-toggle="dropdown"], [data-bs-toggle="tab"], [data-toggle="tab"], [data-bs-toggle="collapse"], [data-toggle="collapse"], [data-bs-toggle="modal"], [data-toggle="modal"], .js-password-toggle')) {
            return null;
        }
        if (el.closest('.layout-menu') && !el.classList.contains('btn')) {
            return null;
        }
        if (el.closest('.dataTables_length, .dataTables_filter, .dt-length, .dt-search')) {
            return null;
        }
        return el;
    }

    function needsConfirmation(el) {
        if (!el) {
            return false;
        }
        if (el.matches('[data-confirm], [data-action-confirm], [data-swal-confirm], [data-confirm-gate]')) {
            return true;
        }
        if (el.getAttribute('data-confirm') != null) {
            return true;
        }
        var id = el.id || '';
        var cls = typeof el.className === 'string' ? el.className : '';
        if (CONFIRM_GATE_ID_RE.test(id)) {
            return true;
        }
        if (CONFIRM_GATE_RE.test(id) || CONFIRM_GATE_RE.test(cls)) {
            return true;
        }
        return false;
    }

    function isRealNavigation(el) {
        if (!el || el.tagName !== 'A') {
            return false;
        }
        var href = el.getAttribute('href');
        if (!href || href === '#' || href.indexOf('javascript:') === 0) {
            return false;
        }
        if (href.indexOf('void(0)') !== -1) {
            return false;
        }
        if (el.getAttribute('target') === '_blank') {
            return false;
        }
        return true;
    }

    function isUiOnlyButton(el) {
        if (!el) {
            return true;
        }
        if (el.matches('[data-action-lock="off"], [data-no-action-lock]')) {
            return true;
        }
        if (el.matches('[data-bs-toggle="modal"], [data-toggle="modal"]')) {
            return true;
        }
        // Import opener only shows a modal; lock starts on Upload/Confirm.
        if (el.matches('.import-export-import-btn')) {
            return true;
        }
        // "Add New" that only opens an empty modal (no AJAX until save)
        if (/^createNew/i.test(el.id || '') && !isRealNavigation(el)) {
            return true;
        }
        return false;
    }

    function getScopeRoots() {
        var roots = [];
        var main = document.querySelector(
            '.content-wrapper, .pos-content-wrapper, .authentication-wrapper, main, .layout-page'
        );
        if (main) {
            roots.push(main);
        } else if (document.body) {
            roots.push(document.body);
        }
        document.querySelectorAll('.modal').forEach(function (modal) {
            roots.push(modal);
        });
        return roots;
    }

    function collectActionables() {
        var set = [];
        var seen = typeof WeakSet !== 'undefined' ? new WeakSet() : null;
        getScopeRoots().forEach(function (root) {
            if (!root || !root.querySelectorAll) {
                return;
            }
            root.querySelectorAll(ACTIONABLE_SELECTOR).forEach(function (el) {
                if (seen) {
                    if (seen.has(el)) {
                        return;
                    }
                    seen.add(el);
                }
                if (el.matches('[data-action-lock="off"], [data-no-action-lock]')) {
                    return;
                }
                if (el.closest('.swal2-container')) {
                    return;
                }
                if (el.closest('.layout-menu') && !el.classList.contains('btn')) {
                    return;
                }
                set.push(el);
            });
        });
        return set;
    }

    function preserveSize(el) {
        var rect = el.getBoundingClientRect();
        if (rect.width > 0) {
            el.style.minWidth = rect.width + 'px';
        }
        if (rect.height > 0) {
            el.style.minHeight = rect.height + 'px';
        }
    }

    function markProcessing(el, options) {
        options = options || {};
        if (!el) {
            return;
        }
        state.trigger = el;
        state.originalDisabled = !!el.disabled;
        state.originalAriaBusy = el.getAttribute('aria-busy');
        state.originalHtml = null;
        state.originalValue = null;

        el.classList.add('pal-processing', 'pal-locked-action');
        el.setAttribute('aria-busy', 'true');
        el.setAttribute('aria-disabled', 'true');

        if (el.tagName === 'A') {
            el.setAttribute('tabindex', '-1');
        }

        // Checkbox / radio / select: disable only (no label swap)
        if (el.tagName === 'SELECT' || el.tagName === 'TEXTAREA' ||
            (el.tagName === 'INPUT' && el.type !== 'submit' && el.type !== 'button')) {
            if ('disabled' in el) {
                el.disabled = true;
            }
            return;
        }

        preserveSize(el);

        if (el.tagName === 'INPUT' && (el.type === 'submit' || el.type === 'button')) {
            state.originalValue = el.value;
            el.value = processingLabel();
        } else {
            state.originalHtml = el.innerHTML;
            if (isIconOnlyButton(el)) {
                el.innerHTML = '<span class="pal-spinner" aria-hidden="true"></span>';
            } else {
                el.textContent = processingLabel();
            }
        }

        // Defer disable so native form submit (login) is not cancelled by a
        // disabled submitter during the same click→submit sequence.
        function disableTrigger() {
            if (state.trigger === el && state.hardLocked && 'disabled' in el) {
                el.disabled = true;
            }
        }
        if (options.deferDisable) {
            setTimeout(disableTrigger, 0);
        } else {
            disableTrigger();
        }
    }

    function restoreProcessing(el) {
        if (!el) {
            return;
        }
        el.classList.remove('pal-processing', 'pal-has-spinner', 'pal-locked-action');

        if (state.originalValue != null && el.tagName === 'INPUT') {
            el.value = state.originalValue;
        } else if (state.originalHtml != null && el === state.trigger && el.tagName !== 'INPUT') {
            el.innerHTML = state.originalHtml;
        }

        el.style.minWidth = '';
        el.style.minHeight = '';

        if (state.originalAriaBusy == null) {
            el.removeAttribute('aria-busy');
        } else {
            el.setAttribute('aria-busy', state.originalAriaBusy);
        }
        el.removeAttribute('aria-disabled');

        if ('disabled' in el) {
            el.disabled = state.originalDisabled;
        }
        if (el.tagName === 'A') {
            el.removeAttribute('tabindex');
        }
    }

    function disableOthers(trigger) {
        state.lockedNodes = [];
        collectActionables().forEach(function (el) {
            if (el === trigger) {
                return;
            }
            if (el.classList.contains('pal-locked-action') && el !== trigger) {
                // may already be marked; still track for restore if not in list
            }
            var entry = {
                el: el,
                disabled: !!el.disabled,
                ariaDisabled: el.getAttribute('aria-disabled'),
            };
            el.classList.add('pal-locked-action');
            el.setAttribute('aria-disabled', 'true');
            if ('disabled' in el && el.type !== 'checkbox' && el.type !== 'radio') {
                el.disabled = true;
            }
            if (el.type === 'checkbox' || el.type === 'radio') {
                el.style.pointerEvents = 'none';
            }
            if (el.tagName === 'A') {
                el.setAttribute('data-pal-tabindex', el.getAttribute('tabindex') || '');
                el.setAttribute('tabindex', '-1');
            }
            state.lockedNodes.push(entry);
        });
    }

    function restoreOthers() {
        state.lockedNodes.forEach(function (entry) {
            var el = entry.el;
            if (!el) {
                return;
            }
            el.classList.remove('pal-locked-action');
            if (entry.ariaDisabled == null) {
                el.removeAttribute('aria-disabled');
            } else {
                el.setAttribute('aria-disabled', entry.ariaDisabled);
            }
            if ('disabled' in el && el.type !== 'checkbox' && el.type !== 'radio') {
                el.disabled = entry.disabled;
            }
            if (el.type === 'checkbox' || el.type === 'radio') {
                el.style.pointerEvents = '';
            }
            if (el.tagName === 'A') {
                var prev = el.getAttribute('data-pal-tabindex');
                el.removeAttribute('data-pal-tabindex');
                if (prev === '' || prev == null) {
                    el.removeAttribute('tabindex');
                } else {
                    el.setAttribute('tabindex', prev);
                }
            }
        });
        state.lockedNodes = [];
    }

    function clearSoftGateTimer() {
        if (state.softGateTimer) {
            clearTimeout(state.softGateTimer);
            state.softGateTimer = null;
        }
    }

    function clearHardLockTimer() {
        if (state.hardLockTimer) {
            clearTimeout(state.hardLockTimer);
            state.hardLockTimer = null;
        }
    }

    function clearNoRequestTimer() {
        if (state.noRequestTimer) {
            clearTimeout(state.noRequestTimer);
            state.noRequestTimer = null;
        }
    }

    function scheduleNoRequestUnlock() {
        clearNoRequestTimer();
        state.noRequestTimer = setTimeout(function () {
            if (state.hardLocked && state.inFlight === 0 && !state.navigationPending) {
                unlock('no-request');
            }
        }, NO_REQUEST_UNLOCK_MS);
    }

    function softGate(trigger) {
        state.softGated = true;
        state.lastActionable = trigger || state.lastActionable;
        state.lastGestureAt = now();
        clearSoftGateTimer();
        state.softGateTimer = setTimeout(function () {
            if (!state.hardLocked && state.inFlight === 0 && !document.querySelector('.swal2-container')) {
                state.softGated = false;
            }
        }, NO_REQUEST_UNLOCK_MS + 400);
    }

    function hardLock(trigger, options) {
        options = options || {};
        var el = trigger || state.lastActionable || state.trigger;

        if (state.hardLocked) {
            if (el && !state.trigger && !options.skipSpinner) {
                markProcessing(el);
            }
            document.body.classList.add('pal-page-locked');
            return;
        }

        state.hardLocked = true;
        state.softGated = false;
        clearSoftGateTimer();
        state.lastGestureAt = now();

        if (el && !options.skipSpinner) {
            markProcessing(el, { deferDisable: !!options.deferDisable });
        } else if (el) {
            state.trigger = el;
            el.classList.add('pal-locked-action');
            el.setAttribute('aria-busy', 'true');
            el.setAttribute('aria-disabled', 'true');
            if ('disabled' in el) {
                el.disabled = true;
            }
        }

        disableOthers(state.trigger || el);
        document.body.classList.add('pal-page-locked');

        clearHardLockTimer();
        state.hardLockTimer = setTimeout(function () {
            if (state.hardLocked && !state.navigationPending) {
                unlock('timeout');
            }
        }, HARD_LOCK_MAX_MS);

        if (state.inFlight === 0 && !options.expectsNavigation) {
            scheduleNoRequestUnlock();
        }
    }

    function unlock(reason) {
        // Native navigation in progress — keep locked until pageshow/force.
        // ajax-idle must always unlock (navigationPending can be set wrongly
        // before jQuery preventDefault on AJAX forms).
        if (
            state.navigationPending &&
            reason !== 'pageshow' &&
            reason !== 'force' &&
            reason !== 'ajax-idle' &&
            reason !== 'api'
        ) {
            return;
        }

        clearSoftGateTimer();
        clearHardLockTimer();
        clearNoRequestTimer();

        restoreOthers();
        restoreProcessing(state.trigger);

        state.hardLocked = false;
        state.softGated = false;
        state.inFlight = 0;
        state.trigger = null;
        state.originalHtml = null;
        state.originalValue = null;
        state.originalDisabled = false;
        state.originalAriaBusy = null;
        state.navigationPending = false;

        if (document.body) {
            document.body.classList.remove('pal-page-locked');
        }
    }

    function shouldIgnoreAjaxUrl(url) {
        if (!url) {
            return false;
        }
        return IGNORE_URL_RE.test(String(url));
    }

    function recentGesture() {
        return now() - state.lastGestureAt <= GESTURE_MS;
    }

    function onAjaxStart(url, settings) {
        settings = settings || {};
        if (settings.skipActionLock || settings.actionLock === false) {
            return false;
        }
        if (shouldIgnoreAjaxUrl(url)) {
            return false;
        }

        if (!state.hardLocked && !state.softGated && !recentGesture()) {
            return false;
        }

        clearNoRequestTimer();
        state.inFlight += 1;
        // AJAX means we are staying on this page — clear any premature nav flag
        state.navigationPending = false;

        if (!state.hardLocked) {
            hardLock(state.lastActionable || state.trigger);
        }

        return true;
    }

    function onAjaxEnd() {
        if (state.inFlight > 0) {
            state.inFlight -= 1;
        }
        if (state.inFlight <= 0 && state.hardLocked && !state.navigationPending) {
            state.inFlight = 0;
            setTimeout(function () {
                if (state.inFlight === 0 && state.hardLocked && !state.navigationPending) {
                    unlock('ajax-idle');
                }
            }, 50);
        }
    }

    function onCaptureClick(e) {
        var el = closestActionable(e.target);
        if (!el) {
            return;
        }

        // Heal stale soft-gate (e.g. handler never ran / dialog never opened)
        if (
            state.softGated &&
            !state.hardLocked &&
            state.inFlight === 0 &&
            !document.querySelector('.swal2-container')
        ) {
            state.softGated = false;
            clearSoftGateTimer();
        }

        // Heal stale hard-lock (unlock was refused while navigationPending stuck)
        if (state.hardLocked && state.inFlight === 0 && !state.navigationPending) {
            unlock('force');
        } else if (state.hardLocked && state.inFlight === 0 && state.navigationPending) {
            // Still on page with no request — not actually navigating
            state.navigationPending = false;
            unlock('force');
        }

        // Delete / approve / reject / other confirm-gated controls:
        // do NOT soft-gate or stop the event here — that can prevent delegated
        // jQuery handlers (deleteRecord) from opening SweetAlert.
        // softGate runs inside deleteRecord (or page scripts) after the handler starts;
        // hard lock runs only after the user confirms.
        if (needsConfirmation(el)) {
            if (state.hardLocked || state.softGated) {
                e.preventDefault();
                e.stopImmediatePropagation();
                return;
            }
            state.lastActionable = el;
            state.lastGestureAt = now();
            return;
        }

        // Block subsequent clicks while an action is gated/locked
        if (state.hardLocked || state.softGated) {
            e.preventDefault();
            e.stopImmediatePropagation();
            return;
        }

        if (isUiOnlyButton(el)) {
            return;
        }

        state.lastActionable = el;
        state.lastGestureAt = now();

        if (isRealNavigation(el)) {
            state.navigationPending = true;
            hardLock(el, { expectsNavigation: true });
            return;
        }

        softGate(el);

        // Never hard-lock type=submit / #saveBtn on click — disabling the
        // submitter before the browser fires `submit` cancels native posts (login).
        // Those lock on the submit event / AJAX start instead.
        if (el.matches(
            '#search_btn, #reset_filter, #btn_pdf, #btn_excel, #btn_csv, .import-export-export-btn, #importDownloadSample, #importUploadBtn, #importConfirmBtn'
        )) {
            hardLock(el);
        }

        // Export / sample download via JS location assign
        if (el.matches('.import-export-export-btn, #importDownloadSample')) {
            state.navigationPending = true;
            clearNoRequestTimer();
        }
    }

    function onCaptureSubmit(e) {
        var form = e.target;
        if (!form || form.tagName !== 'FORM') {
            return;
        }
        if (form.matches('[data-action-lock="off"], [data-no-action-lock]')) {
            return;
        }

        state.lastGestureAt = now();

        var submitter =
            e.submitter ||
            state.lastActionable ||
            form.querySelector('#saveBtn, #btnSave, button[type="submit"], input[type="submit"]');

        if (state.hardLocked && state.trigger && submitter && submitter !== state.trigger && state.inFlight > 0) {
            e.preventDefault();
            e.stopImmediatePropagation();
            return;
        }

        // deferDisable: keep submitter enabled until after this event so login/native POST works
        hardLock(submitter, { deferDisable: true });
    }

    function onBubbleSubmit(e) {
        var form = e.target;
        if (!form || form.tagName !== 'FORM') {
            return;
        }
        if (form.matches('[data-action-lock="off"], [data-no-action-lock]')) {
            return;
        }

        // Defer until after jQuery/other submit handlers (they register later and
        // may preventDefault for AJAX forms — checking now would be too early).
        setTimeout(function () {
            if (!e.defaultPrevented) {
                state.navigationPending = true;
                clearNoRequestTimer();
                return;
            }
            state.navigationPending = false;
            if (state.inFlight === 0) {
                scheduleNoRequestUnlock();
            }
        }, 0);
    }

    function onKeydown(e) {
        if (e.key !== 'Enter' && e.keyCode !== 13) {
            return;
        }
        var tag = (e.target && e.target.tagName) || '';
        if (tag === 'TEXTAREA') {
            return;
        }
        if (state.hardLocked) {
            var form = e.target && e.target.form;
            if (form) {
                e.preventDefault();
                e.stopPropagation();
            }
        }
    }

    function observeSweetAlert() {
        if (!document.body || typeof MutationObserver === 'undefined') {
            return;
        }
        var observer = new MutationObserver(function () {
            var open = !!document.querySelector('.swal2-container');
            if (open && state.softGated && !state.hardLocked) {
                clearSoftGateTimer();
            }
            if (!open && state.softGated && !state.hardLocked && state.inFlight === 0) {
                clearSoftGateTimer();
                state.softGateTimer = setTimeout(function () {
                    if (!state.hardLocked && state.inFlight === 0) {
                        state.softGated = false;
                    }
                }, 200);
            }
        });
        observer.observe(document.body, { childList: true, subtree: true });
    }

    function patchJqueryAjax() {
        if (!window.jQuery) {
            return;
        }
        var $ = window.jQuery;

        $(document).on('ajaxSend.pageActionLock', function (_e, _jqXHR, settings) {
            if (!settings) {
                return;
            }
            settings.__palTracked = onAjaxStart(settings.url, settings);
        });

        $(document).on('ajaxComplete.pageActionLock', function (_e, _jqXHR, settings) {
            if (!settings || !settings.__palTracked) {
                return;
            }
            onAjaxEnd();
        });
    }

    function patchFetch() {
        if (!window.fetch || window.fetch.__palPatched) {
            return;
        }
        var original = window.fetch.bind(window);
        function patchedFetch(input, init) {
            init = init || {};
            var url = typeof input === 'string' ? input : (input && input.url) || '';
            if (init.skipActionLock || shouldIgnoreAjaxUrl(url)) {
                return original(input, init);
            }
            var tracked = onAjaxStart(url, init);
            if (!tracked) {
                return original(input, init);
            }
            return original(input, init).then(
                function (res) {
                    onAjaxEnd();
                    return res;
                },
                function (err) {
                    onAjaxEnd();
                    throw err;
                }
            );
        }
        patchedFetch.__palPatched = true;
        window.fetch = patchedFetch;
    }

    function onPageShow(e) {
        if (e.persisted || state.hardLocked || state.softGated) {
            unlock('pageshow');
        }
    }

    function onBeforeUnload() {
        if (state.hardLocked) {
            state.navigationPending = true;
            clearNoRequestTimer();
        }
    }

    function bind() {
        document.addEventListener('click', onCaptureClick, true);
        document.addEventListener('submit', onCaptureSubmit, true);
        document.addEventListener('submit', onBubbleSubmit, false);
        document.addEventListener('keydown', onKeydown, true);
        window.addEventListener('pageshow', onPageShow);
        window.addEventListener('beforeunload', onBeforeUnload);
        observeSweetAlert();
        patchJqueryAjax();
        patchFetch();

        window.addEventListener('unhandledrejection', function () {
            if (state.hardLocked && state.inFlight === 0) {
                setTimeout(function () {
                    if (state.hardLocked && state.inFlight === 0 && !state.navigationPending) {
                        unlock('unhandledrejection');
                    }
                }, 100);
            }
        });
    }

    var api = {
        suppressPageLoader: true,
        isLocked: function () {
            return state.hardLocked;
        },
        isSoftGated: function () {
            return state.softGated;
        },
        getTrigger: function () {
            return state.trigger;
        },
        lock: function (el, options) {
            state.lastActionable = el || state.lastActionable;
            state.lastGestureAt = now();
            hardLock(el, options);
        },
        softGate: function (el) {
            softGate(el);
        },
        unlock: function () {
            unlock('api');
        },
        forceUnlock: function () {
            state.navigationPending = false;
            unlock('force');
        },
        noteGesture: function (el) {
            state.lastActionable = el || state.lastActionable;
            state.lastGestureAt = now();
        },
        confirmAccepted: function (el) {
            var target = el || state.lastActionable;
            state.lastGestureAt = now();
            clearNoRequestTimer();
            hardLock(target);
        },
        shouldIgnoreUrl: shouldIgnoreAjaxUrl,
    };

    window.PageActionLock = api;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bind);
    } else {
        bind();
    }
})(window, document);
