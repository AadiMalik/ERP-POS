(function () {
    'use strict';

    function toggle(trigger) {
        var group = trigger.closest('.input-group');
        if (!group) {
            return;
        }
        var input = group.querySelector('input');
        if (!input) {
            return;
        }
        var hideIcon = trigger.querySelector('.js-password-icon-hide');
        var showIcon = trigger.querySelector('.js-password-icon-show');
        var showing = input.getAttribute('type') === 'text';
        input.setAttribute('type', showing ? 'password' : 'text');
        trigger.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
        if (hideIcon) {
            hideIcon.classList.toggle('d-none', !showing);
        }
        if (showIcon) {
            showIcon.classList.toggle('d-none', showing);
        }
    }

    document.addEventListener('click', function (e) {
        var trigger = e.target.closest('.js-password-toggle');
        if (!trigger) {
            return;
        }
        e.preventDefault();
        toggle(trigger);
    });

    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Enter' && e.key !== ' ') {
            return;
        }
        var trigger = e.target.closest('.js-password-toggle');
        if (!trigger) {
            return;
        }
        e.preventDefault();
        toggle(trigger);
    });
})();
