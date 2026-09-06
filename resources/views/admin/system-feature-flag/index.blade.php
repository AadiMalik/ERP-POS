@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">{{ __('System Feature Controls') }}</h4>
        <div class="card">
            <div class="card-header">
                <p class="mb-0 text-muted">
                    Platform-wide on/off switches for system features, services, and integrations - independent of
                    each business's subscription package. Turning one off affects every business at once.
                </p>
            </div>
            <div class="table-responsive p-4">
                <table id="system_feature_flag_table" class="table datatables">
                    <thead>
                        <tr>
                            <th>Key</th>
                            <th>Label</th>
                            <th>Category</th>
                            <th>Description</th>
                            <th>Enabled</th>
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
        {data:'key',name:'key'},
        {data:'label',name:'label'},
        {data:'category',name:'category'},
        {data:'description',name:'description'},
        {data:'is_enabled',name:'is_enabled',searchable:false,orderable:false}",
        'route' => 'system-feature-flags/data',
        'buttons' => false,
        'pageLength' => 25,
        'class' => 'system_feature_flag_table',
        'variable' => 'system_feature_flag_table',
    ])

    <script>
        updateStatus({
            buttonClass: ".toggleSystemFeatureFlag",
            url: url_local + "/admin/system-feature-flags/toggle",
            tableCallback: function() {
                initDataTablesystem_feature_flag_table();
            }
        });
    </script>
@endsection
