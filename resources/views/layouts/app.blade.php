@php
    $theme = array_replace_recursive(
        config('theme_presets.sneat_default'),
        session('theme_setting') ?: []
    );

    $sidebar_theme = $theme['sidebar_config'] ?? [];
    $header_theme  = $theme['header_config'] ?? [];
    $footer_theme  = $theme['footer_config'] ?? [];
    $content_theme = $theme['content_config'] ?? [];

    $layoutClasses = [];
    if (($sidebar_theme['position'] ?? 'static') === 'fixed') {
        $layoutClasses[] = 'layout-menu-fixed';
    } elseif (($sidebar_theme['position'] ?? 'static') === 'offcanvas') {
        $layoutClasses[] = 'layout-menu-offcanvas';
    }
    if (($sidebar_theme['collapsed_behavior'] ?? 'expanded') === 'collapsed') {
        $layoutClasses[] = 'layout-menu-collapsed';
    } elseif (($sidebar_theme['collapsed_behavior'] ?? 'expanded') === 'hover') {
        $layoutClasses[] = 'layout-menu-collapsed layout-menu-hover';
    }
    if (($header_theme['position'] ?? 'static') === 'sticky') {
        $layoutClasses[] = 'layout-navbar-fixed';
    }
    if ($footer_theme['sticky'] ?? false) {
        $layoutClasses[] = 'layout-footer-fixed';
    }
    $layoutClasses = implode(' ', $layoutClasses);
@endphp
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('public/assets/img/favicon/favicon.ico') }}" />
    @include('layouts/css')
    @yield('css')

</head>

<body
    data-sidebar-skin="{{ $sidebar_theme['skin'] ?? 'light' }}"
    data-header-style="{{ $header_theme['style'] ?? 'light' }}"
    data-footer-style="{{ $footer_theme['style'] ?? 'light' }}"
    data-content-bg="{{ $content_theme['background'] ?? 'default' }}"
    data-content-spacing="{{ $content_theme['spacing'] ?? 'comfortable' }}"
    data-card-style="{{ $content_theme['card_style'] ?? 'shadow' }}"
    data-table-style="{{ $content_theme['table_style'] ?? 'default' }}"
    data-button-style="{{ $content_theme['button_style'] ?? 'default' }}"
    data-form-style="{{ $content_theme['form_style'] ?? 'default' }}"
    data-filter-style="{{ $content_theme['filter_style'] ?? 'compact' }}"
    data-content-style="{{ $content_theme['content_display_style'] ?? 'card' }}"
>
    <!-- ======== Preloader =========== -->
    <div id="preloader">
        <div class="spinner"></div>
    </div>
    <!-- Layout wrapper -->
    <div class="layout-wrapper layout-content-navbar {{ $layoutClasses }}">
        <div class="layout-container">
            @include('layouts/sidebar')
            <!-- ======== Preloader =========== -->
            <!-- Layout container -->
            <div class="layout-page">
                @include('layouts/navbar')
                <!-- Content wrapper -->
                <div class="content-wrapper">
                    @yield('content')

                    @if($footer_theme['visible'] ?? true)
                        @include('layouts/footer')
                    @endif
                    <div class="content-backdrop fade"></div>
                </div>
                <!-- Content wrapper -->
            </div>
            <!-- / Layout page -->
        </div>
        @include('admin.partials.custom_date_modal')
        <!-- Overlay -->
        <div class="layout-overlay layout-menu-toggle"></div>
    </div>
    <!-- / Layout wrapper -->
    <script>
        let decimal_points = "{{ session('accounting_setting.decimal_points', 2) }}";
        let currency_symbol = "{{ session('accounting_setting.currency_symbol', 'Rs') }}";
        let currency_position = "{{ session('accounting_setting.currency_position', 'left') }}";
    </script>
    @include('layouts/js')
    @yield('js')
</body>

</html>
