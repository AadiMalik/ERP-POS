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
@endsection
