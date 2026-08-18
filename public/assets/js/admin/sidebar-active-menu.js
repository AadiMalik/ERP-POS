/**
 * Automatically activates the sidebar menu link (and expands every ancestor
 * module/sub-module) that corresponds to the current page, by comparing each
 * menu-link href against window.location.pathname. No per-page markup needed -
 * new menu items/routes are picked up automatically.
 */
(function () {
    function normalizePath(pathname) {
        if (pathname.length > 1 && pathname.endsWith('/')) {
            pathname = pathname.slice(0, -1);
        }
        return pathname;
    }

    function initSidebarActiveState() {
        var menuEl = document.getElementById('layout-menu');
        if (!menuEl) return;

        var currentPath = normalizePath(window.location.pathname);
        var links = menuEl.querySelectorAll('.menu-link[href]');

        var bestMatch = null;
        var bestLength = -1;

        links.forEach(function (link) {
            var href = link.getAttribute('href');
            if (!href || href.indexOf('javascript:') === 0 || href === '#') return;

            var linkPath;
            try {
                linkPath = normalizePath(new URL(href, window.location.origin).pathname);
            } catch (e) {
                return;
            }

            var isMatch = currentPath === linkPath || currentPath.indexOf(linkPath + '/') === 0;
            if (isMatch && linkPath.length > bestLength) {
                bestMatch = link;
                bestLength = linkPath.length;
            }
        });

        if (!bestMatch) return;

        var item = bestMatch.closest('.menu-item');
        if (!item) return;

        item.classList.add('active');

        var parent = item.parentElement ? item.parentElement.closest('.menu-item') : null;
        while (parent) {
            parent.classList.add('active', 'open');
            parent = parent.parentElement ? parent.parentElement.closest('.menu-item') : null;
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSidebarActiveState);
    } else {
        initSidebarActiveState();
    }
})();
