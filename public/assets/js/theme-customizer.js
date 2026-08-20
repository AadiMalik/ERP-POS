/**
 * Theme / Appearance live preview engine.
 *
 * applyThemeToDOM(theme) mirrors, in JS, what
 * resources/views/layouts/theme-vars.blade.php computes server-side on every
 * page load (--erp-* CSS variables, data-* attributes on <body>, and Sneat's
 * own layout-* toggle classes). It is only used for the *live preview* on the
 * Theme setting page, before Save/Apply-preset persists the change and the
 * server re-renders every page with the saved values - normal page loads
 * never depend on this file.
 *
 * Any field left out of the passed `theme` object is simply left untouched,
 * so partial updates (e.g. only colors changed) never reset the rest of the
 * live preview.
 */
(function (window, document) {
    'use strict';

    var RADIUS_MAP = { none: '0', sm: '.25rem', md: '.5rem', lg: '.75rem' };
    var SHADOW_MAP = {
        none: 'none',
        sm: '0 1px 3px rgba(0, 0, 0, .08)',
        md: '0 .25rem .75rem rgba(0, 0, 0, .1)',
        lg: '0 .5rem 1.5rem rgba(0, 0, 0, .15)'
    };
    var FONT_SIZE_MAP = { sm: '13px', md: '14px', lg: '15px' };
    var SIDEBAR_WIDTH_MAP = { compact: '200px', wide: '300px', default: '' };

    function hexToRgb(hex) {
        hex = (hex || '#696cff').replace('#', '');
        if (hex.length !== 6) {
            hex = '696cff';
        }
        var r = parseInt(hex.substring(0, 2), 16);
        var g = parseInt(hex.substring(2, 4), 16);
        var b = parseInt(hex.substring(4, 6), 16);
        return r + ', ' + g + ', ' + b;
    }

    function setVar(name, value) {
        if (value !== undefined && value !== null && value !== '') {
            document.documentElement.style.setProperty(name, value);
        }
    }

    function setAttr(el, name, value) {
        if (value !== undefined && value !== null && value !== '') {
            el.setAttribute(name, value);
        }
    }

    function applyThemeToDOM(theme) {
        if (!theme) {
            return;
        }

        var body = document.body;
        var sidebar = theme.sidebar_config || {};
        var header = theme.header_config || {};
        var footer = theme.footer_config || {};
        var content = theme.content_config || {};

        if (theme.primary_color) {
            setVar('--erp-primary', theme.primary_color);
            setVar('--erp-primary-rgb', hexToRgb(theme.primary_color));
            setVar('--bs-primary', theme.primary_color);
            setVar('--bs-primary-rgb', hexToRgb(theme.primary_color));
            setVar('--bs-link-color', theme.primary_color);
            setVar('--bs-link-hover-color', theme.primary_color);
        }
        if (theme.secondary_color) {
            setVar('--erp-secondary', theme.secondary_color);
            setVar('--erp-secondary-rgb', hexToRgb(theme.secondary_color));
            setVar('--bs-secondary', theme.secondary_color);
            setVar('--bs-secondary-rgb', hexToRgb(theme.secondary_color));
        }
        if (theme.accent_color) {
            setVar('--erp-accent', theme.accent_color);
            setVar('--erp-accent-rgb', hexToRgb(theme.accent_color));
            setVar('--bs-info', theme.accent_color);
            setVar('--bs-info-rgb', hexToRgb(theme.accent_color));
        }
        if (theme.font_family) {
            setVar('--erp-font-family', theme.font_family);
            setVar('--bs-body-font-family', theme.font_family);
        }
        if (theme.font_size_base && FONT_SIZE_MAP[theme.font_size_base]) {
            setVar('--erp-font-size-base', FONT_SIZE_MAP[theme.font_size_base]);
        }
        if (content.border_radius && RADIUS_MAP[content.border_radius]) {
            setVar('--erp-radius', RADIUS_MAP[content.border_radius]);
        }
        if (content.shadow_level && SHADOW_MAP[content.shadow_level]) {
            setVar('--erp-shadow', SHADOW_MAP[content.shadow_level]);
        }
        if (sidebar.width && SIDEBAR_WIDTH_MAP[sidebar.width] !== undefined) {
            setVar('--erp-sidebar-width', SIDEBAR_WIDTH_MAP[sidebar.width] || null);
        }

        setAttr(body, 'data-theme-style', theme.preset);
        setAttr(body, 'data-sidebar-skin', sidebar.skin);
        setAttr(body, 'data-header-style', header.style);
        setAttr(body, 'data-header-type', header.type);
        setAttr(body, 'data-footer-style', footer.style);
        setAttr(body, 'data-content-bg', content.background);
        setAttr(body, 'data-content-spacing', content.spacing);
        setAttr(body, 'data-card-style', content.card_style);
        setAttr(body, 'data-table-style', content.table_style);
        setAttr(body, 'data-button-style', content.button_style);
        setAttr(body, 'data-form-style', content.form_style);
        setAttr(body, 'data-filter-style', content.filter_style);
        setAttr(body, 'data-content-style', content.content_display_style);
        setAttr(body, 'data-animation-level', content.animation_level);

        if (window.config && window.config.colors) {
            if (theme.primary_color) { window.config.colors.primary = theme.primary_color; }
            if (theme.secondary_color) { window.config.colors.secondary = theme.secondary_color; }
            if (theme.accent_color) { window.config.colors.info = theme.accent_color; }
        }
    }

    window.applyThemeToDOM = applyThemeToDOM;
})(window, document);
