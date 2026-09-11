@extends('layouts.app')

@section('css')
    <style>
        .settings-header-icon {
            width: 46px;
            height: 46px;
            border-radius: 12px;
            background: linear-gradient(135deg, #3833C8, #6f6af0);
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.15rem;
            box-shadow: 0 6px 16px rgba(56, 51, 200, .25);
        }

        .settings-business-card {
            border: none;
            border-radius: 16px;
            background: linear-gradient(135deg, rgba(56, 51, 200, .06), rgba(111, 106, 240, .03));
            box-shadow: 0 2px 10px rgba(0, 0, 0, .04);
        }

        .settings-shell {
            border: none;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, .06);
            overflow: hidden;
        }

        .settings-nav-col {
            background: rgba(0, 0, 0, .015);
            border-right: 1px solid rgba(0, 0, 0, .06);
        }

        .settings-nav {
            position: sticky;
            top: 1rem;
            padding: .75rem;
            max-height: calc(100vh - 2rem);
            overflow-y: auto;
        }

        .settings-nav .nav-link {
            display: flex;
            align-items: center;
            gap: .65rem;
            text-align: left;
            color: #495057;
            padding: .65rem .9rem;
            border-radius: 10px;
            margin-bottom: 2px;
            font-size: .89rem;
            font-weight: 500;
            border: 1px solid transparent;
            transition: background-color .18s ease, color .18s ease, transform .18s ease, box-shadow .18s ease;
        }

        .settings-nav .nav-link i {
            width: 18px;
            text-align: center;
            font-size: .95rem;
            opacity: .75;
            transition: opacity .18s ease;
        }

        .settings-nav .nav-link:hover {
            background: rgba(56, 51, 200, .08);
            color: #3833C8;
            transform: translateX(2px);
        }

        .settings-nav .nav-link:hover i {
            opacity: 1;
        }

        .settings-nav .nav-link.active {
            background: linear-gradient(135deg, #3833C8, #4a45d6);
            color: #fff;
            box-shadow: 0 4px 12px rgba(56, 51, 200, .3);
        }

        .settings-nav .nav-link.active i {
            opacity: 1;
        }

        .settings-content-col {
            padding: 1.75rem;
        }

        .setting-card,
        .settings-content-col {
            min-height: 650px;
        }

        .settings-content-col .tab-pane {
            animation: settingsFadeIn .25s ease;
        }

        @keyframes settingsFadeIn {
            from {
                opacity: 0;
                transform: translateY(6px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 767.98px) {
            .settings-nav {
                position: static;
                max-height: none;
                display: flex;
                flex-wrap: nowrap;
                overflow-x: auto;
                gap: .4rem;
            }

            .settings-nav .nav-link {
                white-space: nowrap;
            }

            .settings-nav-col {
                border-right: none;
                border-bottom: 1px solid rgba(0, 0, 0, .06);
            }
        }
    </style>
@endsection
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="d-flex align-items-center gap-3 mb-4">
            <span class="settings-header-icon"><i class="fa fa-sliders"></i></span>
            <div>
                <h4 class="fw-bold mb-0">{{ __('settings.title') }}</h4>
            </div>
        </div>
        @if (getRoleName() === \App\Enums\RoleNames::SUPERADMIN)
            <div class="card settings-business-card mb-4">
                <div class="card-body d-flex align-items-center gap-3 flex-wrap">
                    <span class="settings-header-icon" style="width:38px;height:38px;font-size:.95rem;">
                        <i class="fa fa-building"></i>
                    </span>
                    <label class="form-label mb-0 fw-semibold" for="settingsBusinessSelect">{{ __('common.business') }}</label>
                    <select id="settingsBusinessSelect" class="form-select select2" style="max-width:350px;">
                        @foreach ($business as $item)
                            <option value="{{ $item->business_id }}" {{ $target_business_id == $item->business_id ? 'selected' : '' }}>
                                {{ $item->code ?? '' }} {{ $item->name ?? '' }}
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted mb-0">{{ __('settings.superadmin_business_selector_help') }}</small>
                </div>
            </div>
        @endif
        <div class="card settings-card settings-shell">
            <div class="card-body p-0">
                <div class="row g-0">
                    <div class="col-md-3 settings-nav-col">
                        <div class="nav flex-column nav-pills settings-nav" id="settings-tab" role="tablist">
                            <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#business">
                                <i class="fa fa-building"></i> {{ __('settings.tab_business') }}
                            </button>
                            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#localization">
                                <i class="fa fa-globe"></i> {{ __('settings.tab_localization') }}
                            </button>
                            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tax">
                                <i class="fa fa-percent"></i> {{ __('settings.tab_tax') }}
                            </button>
                            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#accounting">
                                <i class="fa fa-calculator"></i> {{ __('settings.tab_accounting') }}
                            </button>
                            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#inventory">
                                <i class="fa fa-boxes-stacked"></i> {{ __('settings.tab_inventory') }}
                            </button>
                            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#notification">
                                <i class="fa fa-bell"></i> {{ __('settings.tab_notification') }}
                            </button>
                            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#business_intelligence">
                                <i class="fa fa-heartbeat"></i> {{ __('settings.tab_business_intelligence') }}
                            </button>
                            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#customer">
                                <i class="fa fa-users"></i> {{ __('settings.tab_customer') }}
                            </button>
                            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#supplier">
                                <i class="fa fa-truck"></i> {{ __('settings.tab_supplier') }}
                            </button>
                            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#email">
                                <i class="fa fa-envelope"></i> {{ __('settings.tab_email') }}
                            </button>
                            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#sms">
                                <i class="fa fa-comment-sms"></i> {{ __('settings.tab_sms') }}
                            </button>
                            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#whatsapp">
                                <i class="fa fa-comments"></i> {{ __('settings.tab_whatsapp') }}
                            </button>
                            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#firebase">
                                <i class="fa fa-fire"></i> {{ __('settings.tab_firebase') }}
                            </button>
                            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#login_security">
                                <i class="fa fa-shield-halved"></i> {{ __('settings.tab_login_security') }}
                            </button>
                            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#fbr">
                                <i class="fa fa-file-invoice"></i> {{ __('settings.tab_fbr') }}
                            </button>
                            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#pra">
                                <i class="fa fa-file-invoice-dollar"></i> {{ __('settings.tab_pra') }}
                            </button>
                            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#pos">
                                <i class="fa fa-cash-register"></i> {{ __('settings.tab_pos') }}
                            </button>
                            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#print">
                                <i class="fa fa-print"></i> {{ __('settings.tab_print') }}
                            </button>
                            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#thermal_print">
                                <i class="fa fa-receipt"></i> {{ __('settings.tab_thermal_print') }}
                            </button>
                            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#barcode">
                                <i class="fa fa-barcode"></i> {{ __('settings.tab_barcode') }}
                            </button>
                            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#theme">
                                <i class="fa fa-paintbrush"></i> {{ __('settings.tab_theme') }}
                            </button>
                            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#website_theme">
                                <i class="fa fa-desktop"></i> {{ __('settings.tab_website_theme') }}
                            </button>
                            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#website_settings">
                                <i class="fa fa-gears"></i> {{ __('settings.tab_website_settings') }}
                            </button>
                        </div>
                    </div>
                    <div class="col-md-9 settings-content-col">
                        <div class="tab-content">
                            <div class="tab-pane fade show active" id="business">
                                @include('admin.setting.tabs.business')
                            </div>
                            <div class="tab-pane fade" id="localization">
                                @include('admin.setting.tabs.localization')
                            </div>
                            <div class="tab-pane fade" id="tax">
                                @include('admin.setting.tabs.tax')
                            </div>
                            <div class="tab-pane fade" id="accounting">
                                @include('admin.setting.tabs.accounting')
                            </div>
                            <div class="tab-pane fade" id="inventory">
                                @include('admin.setting.tabs.inventory')
                            </div>
                            <div class="tab-pane fade" id="notification">
                                @include('admin.setting.tabs.notification')
                            </div>
                            <div class="tab-pane fade" id="business_intelligence">
                                @include('admin.setting.tabs.business_intelligence')
                            </div>
                            <div class="tab-pane fade" id="customer">
                                @include('admin.setting.tabs.customer')
                            </div>
                            <div class="tab-pane fade" id="supplier">
                                @include('admin.setting.tabs.supplier')
                            </div>
                            <div class="tab-pane fade" id="email">
                                @include('admin.setting.tabs.email')
                            </div>
                            <div class="tab-pane fade" id="sms">
                                @include('admin.setting.tabs.sms')
                            </div>
                            <div class="tab-pane fade" id="whatsapp">
                                @include('admin.setting.tabs.whatsapp')
                            </div>
                            <div class="tab-pane fade" id="firebase">
                                @include('admin.setting.tabs.firebase')
                            </div>
                            <div class="tab-pane fade" id="login_security">
                                @include('admin.setting.tabs.login-security')
                            </div>
                            <div class="tab-pane fade" id="fbr">
                                @include('admin.setting.tabs.fbr')
                            </div>
                            <div class="tab-pane fade" id="pra">
                                @include('admin.setting.tabs.pra')
                            </div>
                            <div class="tab-pane fade" id="pos">
                                @include('admin.setting.tabs.pos')
                            </div>
                            <div class="tab-pane fade" id="print">
                                @include('admin.setting.tabs.print')
                            </div>
                            <div class="tab-pane fade" id="thermal_print">
                                @include('admin.setting.tabs.thermal_print')
                            </div>
                            <div class="tab-pane fade" id="barcode">
                                @include('admin.setting.tabs.barcode')
                            </div>
                            <div class="tab-pane fade" id="theme">
                                @include('admin.setting.tabs.theme')
                            </div>
                            <div class="tab-pane fade" id="website_theme">
                                @include('admin.setting.tabs.website_theme')
                            </div>
                            <div class="tab-pane fade" id="website_settings">
                                @include('admin.setting.tabs.website_settings')
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('js')
    <script>
        $(function() {
            $('.select2').select2({
                width: '100%'
            });
        });

        $('#settingsBusinessSelect').on('change', function() {
            window.location.href = '{{ url('admin/setting') }}?business_id=' + encodeURIComponent($(this).val());
        });

        // Super Admin only: the business the selector dropdown targets, read
        // from the query string set on reload (see #settingsBusinessSelect
        // below) - every save call attaches it so it writes the selected
        // business, not the Super Admin's own (null) business_id.
        function currentSettingsBusinessId() {
            return new URLSearchParams(window.location.search).get('business_id') || '';
        }

        function buildSettingFormData(formEl) {
            const fd = new FormData(formEl);
            const businessId = currentSettingsBusinessId();
            if (businessId) {
                fd.set('business_id', businessId);
            }
            return fd;
        }

        function saveLocalizationSetting(form) {
            ajaxRequest({
                url: '{{ route('localization.update') }}',
                method: 'POST',
                data: buildSettingFormData($(form)[0]),
                isFormData: true
            }).then(res => {
                successMessage(res.Message);
                setTimeout(() => location.reload(), 800);
            }).catch(err => {
                errorMessage(err.Message);
            });
        }

        function saveSetting(form, url) {
            ajaxRequest({
                url: url,
                method: 'POST',
                data: buildSettingFormData($(form)[0]),
                isFormData: true
            }).then(res => {
                successMessage(res.Message);
            }).catch(err => {
                errorMessage(err.Message);
            });
        }
    </script>

    {{-- customer setting js --}}
    <script>
        function toggleCustomerSettings() {
            let credit = $('[name="customer_enable_credit_limit"]').val();
            $('[name="customer_credit_limit"]').prop('disabled', credit != 1);

            let loyalty = $('[name="loyalty_program"]').val();

            $('[name="loyalty_every_amount"]').prop('disabled', loyalty != 1);
            $('[name="loyalty_point_rate"]').prop('disabled', loyalty != 1);
            $('[name="loyalty_min_order_amount"]').prop('disabled', loyalty != 1);
        }

        $(document).on('change',
            '[name="customer_enable_credit_limit"], [name="loyalty_program"]',
            toggleCustomerSettings
        );

        $(document).ready(function() {
            toggleCustomerSettings();
        });
    </script>

    {{-- Supplier setting js --}}
    <script>
        function toggleSupplierSettings() {
            let credit = $('[name="supplier_enable_credit_limit"]').val();

            $('[name="supplier_credit_limit"]').prop('disabled', credit != 1);
        }

        $(document).on('change', '[name="supplier_enable_credit_limit"]', function() {
            toggleSupplierSettings();
        });

        $(document).ready(function() {
            toggleSupplierSettings();
        });
    </script>

    {{-- Email setting js --}}
    <script>
        function toggleEmailSettings() {
            let enabled = $('[name="enable_email_notifications"]').val();

            $('.email-config-field').prop('disabled', enabled != 1);

            $('.email-config-field.select2').trigger('change.select2');
        }

        $(document).on('change', '[name="enable_email_notifications"]', function() {
            toggleEmailSettings();
        });

        $(document).ready(function() {
            toggleEmailSettings();
        });
    </script>

    {{-- SMS setting js --}}
    <script>
        function toggleSmsSettings() {
            let enabled = $('[name="enable_sms"]').val();

            $('.sms-config-field').prop('disabled', enabled != 1);
        }

        $(document).on('change', '[name="enable_sms"]', function() {
            toggleSmsSettings();
        });

        $(document).ready(function() {
            toggleSmsSettings();
        });

        function loadSMSFields() {

            $('.provider-field').hide();

            let provider = $('#sms_provider').val();

            $('.provider-' + provider).show();
        }

        $(document).ready(function() {

            loadSMSFields();

            $('#sms_provider').on('change', function() {

                loadSMSFields();

            });

        });
    </script>

    {{-- Whatsapp setting js --}}
    <script>
        function toggleWhatsappSettings() {
            let enabled = $('[name="enable_whatsapp"]').val();

            $('.whatsapp-config-field').prop('disabled', enabled != 1);
        }

        $(document).on('change', '[name="enable_whatsapp"]', function() {
            toggleWhatsappSettings();
        });

        $(document).ready(function() {
            toggleWhatsappSettings();
        });
    </script>

    {{-- FBR setting js --}}
    <script>
        function toggleFbrSettings() {
            let enabled = $('[name="enable_fbr"]').val();

            $('.fbr-config-field').prop('disabled', enabled != 1);
        }

        $(document).on('change', '[name="enable_fbr"]', function() {
            toggleFbrSettings();
        });

        $(document).ready(function() {
            toggleFbrSettings();
        });
    </script>

    {{-- Social Login & Security setting js --}}
    <script>
        function toggleLoginSecuritySettings() {
            let google = $('[name="is_google_enabled"]').val();
            $('.google-config-field').prop('disabled', google != 1);

            let facebook = $('[name="is_facebook_enabled"]').val();
            $('.facebook-config-field').prop('disabled', facebook != 1);

            let captcha = $('[name="is_captcha_enabled"]').val();
            $('.captcha-config-field').prop('disabled', captcha != 1);
        }

        $(document).on('change',
            '[name="is_google_enabled"], [name="is_facebook_enabled"], [name="is_captcha_enabled"]',
            toggleLoginSecuritySettings
        );

        $(document).ready(function() {
            toggleLoginSecuritySettings();
        });
    </script>

    {{-- PRA setting js --}}
    <script>
        function togglePraSettings() {
            let enabled = $('[name="enable_pra"]').val();

            $('.pra-config-field').prop('disabled', enabled != 1);
        }

        $(document).on('change', '[name="enable_pra"]', function() {
            togglePraSettings();
        });

        $(document).ready(function() {
            togglePraSettings();
        });
    </script>

    {{-- POS setting js --}}
    <script>
        function togglePosSettings() {
            let backdated = $('[name="allow_backdated_sale"]').is(':checked');

            $('[name="backdated_sale_max_days"]').prop('disabled', !backdated);

            let automatic = $('[name="register_mode"]').val() === 'automatic';

            $('.register-mode-automatic-field').toggle(automatic);
        }

        $(document).on('change', '[name="allow_backdated_sale"], [name="register_mode"]', function() {
            togglePosSettings();
        });

        $(document).ready(function() {
            togglePosSettings();
        });
    </script>

    {{-- Theme / Appearance setting js --}}
    <script src="{{ asset('public/assets/js/theme-customizer.js') }}"></script>
    <script>
        window.THEME_PRESETS = @json($theme_presets);

        function saveThemeSetting(form) {
            ajaxRequest({
                url: '{{ route('theme.update') }}',
                method: 'POST',
                data: buildSettingFormData($(form)[0]),
                isFormData: true
            }).then(res => {
                successMessage(res.Message);
                setTimeout(() => location.reload(), 800);
            }).catch(err => {
                errorMessage(err.Message);
            });
        }

        function applyThemePreset(presetKey) {
            let preset = window.THEME_PRESETS[presetKey];
            if (preset && window.applyThemeToDOM) {
                window.applyThemeToDOM(preset);
            }

            ajaxRequest({
                url: '{{ route('theme.preset') }}',
                method: 'POST',
                data: {
                    preset: presetKey,
                    business_id: currentSettingsBusinessId(),
                    _token: '{{ csrf_token() }}'
                }
            }).then(res => {
                successMessage(res.Message);
                setTimeout(() => location.reload(), 600);
            }).catch(err => {
                errorMessage(err.Message);
            });
        }

        $(document).on('click', '.btn-apply-preset, .theme-preset-card', function(e) {
            e.stopPropagation();
            applyThemePreset($(this).data('preset'));
        });

        // Live preview: instantly reflect field changes on this very page before Save
        $(document).on('input change', '#themeSettingForm input, #themeSettingForm select', function() {
            if (!window.applyThemeToDOM) return;

            let data = new FormData($('#themeSettingForm')[0]);
            window.applyThemeToDOM({
                primary_color: data.get('primary_color'),
                secondary_color: data.get('secondary_color'),
                accent_color: data.get('accent_color'),
                font_family: data.get('font_family'),
                font_size_base: data.get('font_size_base'),
                sidebar_config: {
                    skin: data.get('sidebar_config[skin]'),
                    width: data.get('sidebar_config[width]'),
                },
                header_config: {
                    style: data.get('header_config[style]'),
                },
                footer_config: {
                    style: data.get('footer_config[style]'),
                },
                content_config: {
                    background: data.get('content_config[background]'),
                    spacing: data.get('content_config[spacing]'),
                    card_style: data.get('content_config[card_style]'),
                    border_radius: data.get('content_config[border_radius]'),
                    shadow_level: data.get('content_config[shadow_level]'),
                    table_style: data.get('content_config[table_style]'),
                    button_style: data.get('content_config[button_style]'),
                    form_style: data.get('content_config[form_style]'),
                    filter_style: data.get('content_config[filter_style]'),
                    content_display_style: data.get('content_config[content_display_style]'),
                },
            });
        });
    </script>

    {{-- Website Theme setting js --}}
    <script>
        window.WEBSITE_THEME_PRESETS = @json($website_theme_presets);

        function saveWebsiteThemeSetting(form) {
            ajaxRequest({
                url: '{{ route('website_theme.update') }}',
                method: 'POST',
                data: buildSettingFormData($(form)[0]),
                isFormData: true
            }).then(res => {
                successMessage(res.Message);
                setTimeout(() => location.reload(), 800);
            }).catch(err => {
                errorMessage(err.Message);
            });
        }

        function applyWebsiteThemePreset(presetKey) {
            ajaxRequest({
                url: '{{ route('website_theme.preset') }}',
                method: 'POST',
                data: {
                    preset: presetKey,
                    business_id: currentSettingsBusinessId(),
                    _token: '{{ csrf_token() }}'
                }
            }).then(res => {
                successMessage(res.Message);
                setTimeout(() => location.reload(), 600);
            }).catch(err => {
                errorMessage(err.Message);
            });
        }

        $(document).on('click', '.btn-apply-website-theme-preset, .website-theme-preset-card', function(e) {
            e.stopPropagation();
            applyWebsiteThemePreset($(this).data('preset'));
        });
    </script>

    {{-- Website Settings js --}}
    <script>
        function saveWebsiteSettings(form) {
            ajaxRequest({
                url: '{{ route('website_settings.update') }}',
                method: 'POST',
                data: buildSettingFormData($(form)[0]),
                isFormData: true
            }).then(res => {
                successMessage(res.Message);
                setTimeout(() => location.reload(), 800);
            }).catch(err => {
                errorMessage(err.Message);
            });
        }
    </script>
@endsection
