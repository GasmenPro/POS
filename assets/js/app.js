(function () {
    'use strict';

    function initializeAppUi() {
        var main = document.querySelector('main');
        if (main) {
            main.id = main.id || 'main-content';
            main.classList.add('app-main');
            main.setAttribute('tabindex', '-1');
        }

        document.querySelectorAll('.alert').forEach(function (alert) {
            alert.setAttribute('role', alert.classList.contains('alert-danger') ? 'alert' : 'status');
            alert.setAttribute('aria-live', alert.classList.contains('alert-danger') ? 'assertive' : 'polite');
            if (!alert.querySelector('.btn-close')) {
                alert.classList.add('alert-dismissible', 'fade', 'show');
                var closeButton = document.createElement('button');
                closeButton.type = 'button';
                closeButton.className = 'btn-close';
                closeButton.setAttribute('data-bs-dismiss', 'alert');
                closeButton.setAttribute('aria-label', 'Dismiss message');
                alert.appendChild(closeButton);
            }
        });

        var requiredFieldIndex = 0;
        document.querySelectorAll('input[required], select[required], textarea[required]').forEach(function (field) {
            if (!field.id) {
                requiredFieldIndex += 1;
                field.id = 'required-field-' + requiredFieldIndex;
            }
            var label = document.querySelector('label[for="' + CSS.escape(field.id) + '"]');
            if (!label && field.parentElement) {
                label = field.parentElement.querySelector('label.form-label');
                if (label) { label.setAttribute('for', field.id); }
            }
            if (label) {
                label.classList.add('required-label');
                label.title = 'Required field';
            }
        });

        document.querySelectorAll('form').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (event.defaultPrevented || !form.checkValidity()) { return; }
                var actionField = form.querySelector('input[name="action"]');
                var action = actionField ? actionField.value : '';
                if ((action === 'deactivate' || action === 'delete') && form.dataset.confirmed !== 'true') {
                    var message = action === 'delete'
                        ? 'Delete this record? This action cannot be undone.'
                        : 'Deactivate this record? It will no longer be available for normal use.';
                    if (!window.confirm(message)) {
                        event.preventDefault();
                        return;
                    }
                    form.dataset.confirmed = 'true';
                }
                var submitter = event.submitter || form.querySelector('button[type="submit"], input[type="submit"]');
                if (submitter && !submitter.dataset.keepEnabled) {
                    submitter.classList.add('is-submitting');
                    submitter.setAttribute('aria-busy', 'true');
                    submitter.disabled = true;
                }
            });
        });

        document.querySelectorAll('#appSidebar .nav-link').forEach(function (link) {
            link.addEventListener('click', function () {
                if (window.innerWidth >= 992 || !window.bootstrap) { return; }
                var sidebar = document.getElementById('appSidebar');
                var instance = bootstrap.Offcanvas.getInstance(sidebar);
                if (instance) { instance.hide(); }
            });
        });

        document.addEventListener('keydown', function (event) {
            if (event.altKey || event.ctrlKey || event.metaKey || event.shiftKey) { return; }
            if (event.key === 'F2') {
                var search = document.querySelector('[data-pos-search]');
                if (search) { event.preventDefault(); search.focus(); search.select(); return; }
            }
            if (event.key === 'F4') {
                var payment = document.querySelector('[data-pos-payment]');
                if (payment) { event.preventDefault(); payment.focus(); payment.select(); }
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeAppUi);
    } else {
        initializeAppUi();
    }
})();
