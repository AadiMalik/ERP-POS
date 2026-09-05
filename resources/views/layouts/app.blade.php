@php
    $theme = resolved_theme_setting();

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

    $__localization = session('localization_setting') ?: [];
    $__inputLanguage = config('languages.' . ($__localization['input_language'] ?? 'en'));
    $inputDirection = $__inputLanguage['direction'] ?? 'ltr';

    $__i18nCommon = [
        'confirm_delete_title'   => __('common.confirm_delete_title'),
        'confirm_delete_text'    => __('common.confirm_delete_text'),
        'confirm_delete_button'  => __('common.confirm_delete_button'),
        'delete_failed'          => __('common.delete_failed'),
        'record_not_found'       => __('common.record_not_found'),
        'something_went_wrong'   => __('common.something_went_wrong'),
        'no_notifications'       => __('common.no_notifications'),
        'please_enter_name'      => __('common.please_enter_name'),
        'please_select'          => __('common.please_select'),
        'save'                   => __('common.save'),
        'cancel'                 => __('common.cancel'),
        'close'                  => __('common.close'),
        'confirm'                => __('common.confirm'),
        'loading'                => __('common.loading'),
        'success'                => __('common.success'),
        'error'                  => __('common.error'),
        'import'                 => __('common.import'),
        'export'                 => __('common.export'),
        'confirm_import'         => __('common.confirm_import'),
        'valid_rows'             => __('common.valid_rows'),
        'invalid_rows'           => __('common.invalid_rows'),
    ];
@endphp
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ resolved_text_direction() }}" class="{{ $layoutClasses }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>
    @include('partials.favicon')
    @include('layouts/css')
    @yield('css')

</head>

<body
    data-theme-style="{{ $theme['preset'] ?? 'sneat_default' }}"
    data-sidebar-skin="{{ $sidebar_theme['skin'] ?? 'light' }}"
    data-header-style="{{ $header_theme['style'] ?? 'light' }}"
    data-header-type="{{ $header_theme['type'] ?? 'detached' }}"
    data-footer-style="{{ $footer_theme['style'] ?? 'light' }}"
    data-content-bg="{{ $content_theme['background'] ?? 'default' }}"
    data-content-spacing="{{ $content_theme['spacing'] ?? 'comfortable' }}"
    data-card-style="{{ $content_theme['card_style'] ?? 'shadow' }}"
    data-table-style="{{ $content_theme['table_style'] ?? 'default' }}"
    data-button-style="{{ $content_theme['button_style'] ?? 'default' }}"
    data-form-style="{{ $content_theme['form_style'] ?? 'default' }}"
    data-filter-style="{{ $content_theme['filter_style'] ?? 'compact' }}"
    data-content-style="{{ $content_theme['content_display_style'] ?? 'card' }}"
    data-animation-level="{{ $content_theme['animation_level'] ?? 'subtle' }}"
    data-input-dir="{{ $inputDirection }}"
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
        @include('admin.partials.view-jv-modal')
        @include('admin.partials.stock-consumption-modal')
        <!-- Overlay -->
        <div class="layout-overlay layout-menu-toggle"></div>
    </div>
    <!-- / Layout wrapper -->
    <script>
        let decimal_points = "{{ session('accounting_setting.decimal_points', 2) }}";
        let currency_symbol = "{{ session('accounting_setting.currency_symbol', 'Rs') }}";
        let currency_position = "{{ session('accounting_setting.currency_position', 'left') }}";

        window.i18n = @json($__i18nCommon);
    </script>
    @include('layouts/js')
    @yield('js')
    {{-- Additive to @yield('js') (a different registry): lets multiple
         dashboard partials each push their own deferred chart script
         without clobbering home.blade.php's own @section('js'). Placed
         after layouts/js so ApexCharts/config.js are always loaded first. --}}
    @stack('js')
</body>

</html>
