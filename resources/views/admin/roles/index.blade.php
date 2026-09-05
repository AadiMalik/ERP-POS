@php
    use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('css')
@endsection
@section('content')
    <!-- ========== table components start ========== -->
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">{{ __('roles.title') }}</h4>

        <!-- Basic Bootstrap Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <h5></h5>
                <div class="d-flex gap-2">
                    @if (RoleNames::SUPERADMIN == getRoleName())
                        <a href="{{ url('admin/roles/create') }}" class="btn rounded-pill btn-primary">
                            <i class="icon-base fa fa-plus mr-5"></i>{{ __('common.add_new') }}</a>
                    @endif
                    <button type="button" id="resetRolesBtn" class="btn rounded-pill btn-info">
                        <i class="fa fa-refresh"></i> {{ __('roles.reset_roles') }}
                    </button>
                </div>
            </div>
            <div class="table-responsive text-nowrap p-4">
                <table id="role_table" class="table display datatables" style="width:100%">
                    <thead>
                        <tr>
                            <th></th>
                            <th>{{ __('roles.singular') }}</th>
                            <th>{{ __('common.description') }}</th>
                            <th>{{ __('roles.permissions') }}</th>
                            <th>{{ __('common.business') }}</th>
                            <th>{{ __('common.action') }}</th>
                        </tr>
                        <!-- end table row-->
                    </thead>
                    <tbody>
                    </tbody>
                </table>
                <!-- end table -->
            </div>
        </div>
    </div>
    <!-- ========== table components end ========== -->
@endsection
@section('js')
    @php
        $__i18nRoles = [
            'reset_success' => __('roles.reset_success'),
        ];
    @endphp
    <script>window.i18n_roles = @json($__i18nRoles);</script>
    @include('admin.partials.datatable', [
        'columns' => "
    {data: null , defaultContent: ''},
    {data: 'name' , name: 'name'},
    {data: 'description' , name: 'description'},
    {data: 'permissions' , name: 'permissions' , 'sortable': false , searchable: false},
    {data: 'business' , name: 'business' , 'sortable': false , searchable: false},
    {data: 'action' , name: 'action' , 'sortable': false , searchable: false},",
        'route' => 'roles/data',
        'buttons' => false,
        'pageLength' => 10,
        'class' => 'role_table',
        'variable' => 'role_table',
        'detail' => true,
        'order' => "[[1, 'asc']]",
    ])

    <script>
        @if (session('success'))

            successMessage("{{ session('success') }}");
        @endif



        $("#resetRolesBtn").on("click", function() {

            let btn = $(this);

            btn.prop("disabled", true);

            $.ajax({
                url: "{{ url('admin/roles/reset') }}",
                type: "POST",

                headers: {
                    "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content")
                },

                beforeSend: function() {
                    $("#preloader").show();
                },

                success: function(response) {

                    $("#preloader").hide();

                    successMessage(response.Message || window.i18n_roles.reset_success);

                    if (typeof role_table !== "undefined") {
                        initDataTablerole_table();
                    }

                    btn.prop("disabled", false);
                },

                error: function(xhr) {

                    $("#preloader").hide();

                    errorMessage(
                        xhr.responseJSON?.Message ||
                        xhr.responseJSON?.message ||
                        "Something went wrong"
                    );

                    btn.prop("disabled", false);
                }
            });

        });
    </script>
@endsection
