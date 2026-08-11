@extends('layouts.app')

@section('css')
    <style>
        .setting-nav .nav-link {
            text-align: left;
            color: #495057;
            padding: 12px 15px;
        }

        .setting-nav .nav-link.active {
            background: #696cff;
            color: #fff;
        }

        .setting-card {
            min-height: 650px;
        }
    </style>
@endsection
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold mb-4">
            Settings
        </h4>
        <div class="card settings-card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3" style="padding:0px;">
                        <div class="nav flex-column nav-pills settings-nav" id="settings-tab" role="tablist">
                            <button class="nav-link active" style="text-align: left; border-radius:0px;"
                                data-bs-toggle="pill" data-bs-target="#business">
                                Business
                            </button>
                            <button class="nav-link" style="text-align: left; border-radius:0px;" data-bs-toggle="pill"
                                data-bs-target="#accounting">
                                Accounting
                            </button>
                            <button class="nav-link" style="text-align: left; border-radius:0px;" data-bs-toggle="pill"
                                data-bs-target="#inventory">
                                Inventory
                            </button>
                            <button class="nav-link" style="text-align: left; border-radius:0px;" data-bs-toggle="pill"
                                data-bs-target="#customer">
                                Customer
                            </button>
                            <button class="nav-link" style="text-align: left; border-radius:0px;" data-bs-toggle="pill"
                                data-bs-target="#supplier">
                                Supplier
                            </button>
                            <button class="nav-link" style="text-align: left; border-radius:0px;" data-bs-toggle="pill"
                                data-bs-target="#email">
                                Email
                            </button>
                            <button class="nav-link" style="text-align: left; border-radius:0px;" data-bs-toggle="pill"
                                data-bs-target="#sms">
                                SMS
                            </button>
                            <button class="nav-link" style="text-align: left; border-radius:0px;" data-bs-toggle="pill"
                                data-bs-target="#whatsapp">
                                WhatsApp
                            </button>
                            <button class="nav-link" style="text-align: left; border-radius:0px;" data-bs-toggle="pill"
                                data-bs-target="#fbr">
                                FBR
                            </button>
                            <button class="nav-link" style="text-align: left; border-radius:0px;" data-bs-toggle="pill"
                                data-bs-target="#print">
                                Print
                            </button>
                            <button class="nav-link" style="text-align: left; border-radius:0px;" data-bs-toggle="pill"
                                data-bs-target="#barcode">
                                Barcode &amp; QR
                            </button>
                        </div>
                    </div>
                    <div class="col-md-9">
                        <div class="tab-content">
                            <div class="tab-pane fade show active" id="business">
                                @include('admin.setting.tabs.business')
                            </div>
                            <div class="tab-pane fade" id="accounting">
                                @include('admin.setting.tabs.accounting')
                            </div>
                            <div class="tab-pane fade" id="inventory">
                                @include('admin.setting.tabs.inventory')
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
                            <div class="tab-pane fade" id="fbr">
                                @include('admin.setting.tabs.fbr')
                            </div>
                            <div class="tab-pane fade" id="print">
                                @include('admin.setting.tabs.print')
                            </div>
                            <div class="tab-pane fade" id="barcode">
                                @include('admin.setting.tabs.barcode')
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

        function saveSetting(form, url) {
            ajaxRequest({
                url: url,
                method: 'POST',
                data: new FormData($(form)[0]),
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
@endsection
