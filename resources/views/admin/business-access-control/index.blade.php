@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">{{ __('Business Access Control') }}</h4>
        <div class="card">
            <div class="card-header">
                <p class="mb-0 text-muted">
                    Enable or disable a business's access to each platform. All platforms are ON by default -
                    switching one OFF blocks that platform for the entire business (every branch and user), everywhere:
                    login, API, and application access. Website and Mobile App share one switch since they use the
                    same customer API today.
                </p>
            </div>
            <div class="table-responsive p-4">
                <table id="business_access_control_table" class="table datatables">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Name</th>
                            <th>ERP</th>
                            <th>Website &amp; Mobile App</th>
                            <th>POS</th>
                            <th>Offline POS</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
@endsection
@section('js')
    @include('admin.partials.datatable', [
        'columns' => "
        {data:'code',name:'code'},
        {data:'name',name:'name'},
        {data:'erp_access_enabled',name:'erp_access_enabled',searchable:false,orderable:false},
        {data:'storefront_access_enabled',name:'storefront_access_enabled',searchable:false,orderable:false},
        {data:'pos_access_enabled',name:'pos_access_enabled',searchable:false,orderable:false},
        {data:'offline_pos_access_enabled',name:'offline_pos_access_enabled',searchable:false,orderable:false}",
        'route' => 'business-access-control/data',
        'buttons' => false,
        'pageLength' => 25,
        'class' => 'business_access_control_table',
        'variable' => 'business_access_control_table',
    ])

    <script>
        updateStatus({
            buttonClass: ".togglePlatformAccess",
            url: url_local + "/admin/business-access-control",
            tableCallback: function() {
                initDataTablebusiness_access_control_table();
            }
        });
    </script>
@endsection
