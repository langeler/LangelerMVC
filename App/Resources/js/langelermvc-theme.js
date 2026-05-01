(function () {
    'use strict';

    var root = document.documentElement;
    var body = document.body;
    var toggle = document.querySelector('[data-theme-toggle]');
    var storageKey = body ? body.getAttribute('data-theme-storage-key') : 'langelermvc.theme';
    var defaultMode = body ? body.getAttribute('data-theme-default-mode') || 'system' : 'system';
    var allowed = ['light', 'dark', 'system'];

    function safeGet() {
        try {
            return window.localStorage.getItem(storageKey || 'langelermvc.theme');
        } catch (error) {
            return null;
        }
    }

    function safeSet(value) {
        try {
            window.localStorage.setItem(storageKey || 'langelermvc.theme', value);
        } catch (error) {
        }
    }

    function normalize(value) {
        value = String(value || '').toLowerCase();
        return allowed.indexOf(value) !== -1 ? value : defaultMode;
    }

    function resolvedLabel(mode) {
        if (mode === 'dark') {
            return 'Dark';
        }

        if (mode === 'light') {
            return 'Light';
        }

        return 'System';
    }

    function apply(mode) {
        mode = normalize(mode);
        root.setAttribute('data-theme-mode', mode);

        if (body) {
            body.setAttribute('data-theme-mode', mode);
        }

        if (toggle) {
            toggle.setAttribute('aria-pressed', mode === 'dark' ? 'true' : 'false');
            var label = toggle.querySelector('[data-theme-toggle-label]');

            if (label) {
                label.textContent = resolvedLabel(mode);
            }
        }
    }

    function next(mode) {
        if (mode === 'system') {
            return 'light';
        }

        if (mode === 'light') {
            return 'dark';
        }

        return 'system';
    }

    apply(safeGet() || (body ? body.getAttribute('data-theme-mode') : defaultMode));

    if (toggle) {
        toggle.addEventListener('click', function () {
            var mode = next(root.getAttribute('data-theme-mode') || defaultMode);
            safeSet(mode);
            apply(mode);
        });
    }
})();
