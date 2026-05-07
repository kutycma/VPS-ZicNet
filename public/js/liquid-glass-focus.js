(function () {
    var focusTargetClass = 'liquid-glass-focus-target';
    var bodyClass = 'liquid-glass-focus-mode';
    var backdropClass = 'liquid-glass-focus-backdrop';
    var backdrop = null;

    function ensureBackdrop() {
        if (backdrop) {
            return backdrop;
        }

        backdrop = document.querySelector('.' + backdropClass);
        if (!backdrop) {
            backdrop = document.createElement('div');
            backdrop.className = backdropClass;
            document.body.appendChild(backdrop);
        }

        backdrop.addEventListener('click', hideOpenDropdowns);

        return backdrop;
    }

    function clearFocusTargets() {
        var targets = document.querySelectorAll('.' + focusTargetClass);
        targets.forEach(function (target) {
            target.classList.remove(focusTargetClass);
        });
    }

    function activateFocus(target) {
        if (!target) {
            return;
        }

        ensureBackdrop();
        clearFocusTargets();
        target.classList.add(focusTargetClass);
        document.body.classList.add(bodyClass);
    }

    function deactivateFocusWhenClosed() {
        window.setTimeout(function () {
            var hasOpenLayer = document.querySelector('.dropdown.show, .modal.show');
            if (!hasOpenLayer) {
                clearFocusTargets();
                document.body.classList.remove(bodyClass);
            }
        }, 80);
    }

    function hideOpenDropdowns() {
        if (window.jQuery && window.jQuery.fn && window.jQuery.fn.dropdown) {
            window.jQuery('.dropdown.show .dropdown-toggle').dropdown('hide');
            return;
        }

        var openToggles = document.querySelectorAll('.dropdown.show [data-toggle="dropdown"], .dropdown.show [data-bs-toggle="dropdown"]');
        openToggles.forEach(function (toggle) {
            toggle.click();
        });
    }

    function bindBootstrapWithJquery() {
        if (!window.jQuery) {
            return;
        }

        window.jQuery(document)
            .on('show.bs.dropdown', '.dropdown', function () {
                activateFocus(this);
            })
            .on('hidden.bs.dropdown', '.dropdown', deactivateFocusWhenClosed)
            .on('show.bs.modal', '.modal', function () {
                activateFocus(this);
            })
            .on('hidden.bs.modal', '.modal', deactivateFocusWhenClosed);
    }

    function bindBootstrapNativeEvents() {
        document.addEventListener('show.bs.dropdown', function (event) {
            var dropdown = event.target && event.target.closest ? event.target.closest('.dropdown') : null;
            activateFocus(dropdown || event.target);
        });

        document.addEventListener('hidden.bs.dropdown', deactivateFocusWhenClosed);

        document.addEventListener('show.bs.modal', function (event) {
            activateFocus(event.target);
        });

        document.addEventListener('hidden.bs.modal', deactivateFocusWhenClosed);
    }

    document.addEventListener('DOMContentLoaded', function () {
        ensureBackdrop();
        bindBootstrapWithJquery();
        bindBootstrapNativeEvents();
    });
})();
