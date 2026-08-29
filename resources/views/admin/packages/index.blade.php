@extends('layouts.app')
@section('css')
@endsection
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">
            Packages
        </h4>
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <h5></h5>
                <a href="{{ url('admin/packages/create') }}" class="btn rounded-pill btn-primary"><i
                        class="icon-base fa fa-plus"></i>Add New</a>
            </div>
            <div class="table-responsive text-nowrap p-4">
                <table id="package_table" class="table display datatables" style="width:100%">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Price</th>
                            <th>Duration</th>
                            <th>Users</th>
                            <th>Branches</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
        @include('admin/packages/model/view')
    </div>
@endsection

@section('js')
    @if (session('error'))
        <script>
            errorMessage(
                "{{ session('error') }}"
            );
        </script>
    @endif
    <script>
        const MODULE_REGISTRY = @json(\App\Support\Subscription\SubscriptionModuleRegistry::grouped());
    </script>
    @include('admin.partials.datatable', [
        'columns' => "
                            {data:'name',name:'name'},
                            {data:'price',name:'price'},
                            {data:'duration_days',name:'duration_days',sortable:false},
                            {data:'max_users',name:'max_users'},
                            {data:'max_branches',name:'max_branches'},
                            {data:'status',name:'status',sortable:false},
                            {data:'action',name:'action',sortable:false,searchable:false}
                        ",
        'route' => 'packages/data',
        'buttons' => false,
        'pageLength' => 10,
        'class' => 'package_table',
        'variable' => 'package_table',
    ])
    <script>
        @if (session('success'))

            successMessage(
                "{{ session('success') }}"
            );
        @endif
        deleteRecord({
            buttonClass: "#deletePackage",
            url: url_local + "/admin/packages",

            tableCallback: function() {
                initDataTablepackage_table();
            }
        });

        // view package

        $(document).on('click', '#viewPackage', async function() {
            try {
                const id = $(this).data('id');
                const response =
                    await ajaxRequest({
                        url: `${url_local}/admin/packages/${id}`,
                        method: 'GET'
                    });

                const packageData = response.Data ?? response.data;

                setPackageViewData(packageData);
                $('#viewModal').modal('show');
            } catch (error) {
                errorMessage(error.Message ?? 'Something went wrong');
            }
        });

        function setPackageViewData(item) {
            const fields = {
                '#v_name': item.name,
                '#v_description': item.description,
                '#v_price': item.price,
                '#v_discount': item.discount != null ? item.discount : 0,
                '#v_duration_type': item.duration_type,
                '#v_duration': item.duration_days,
                '#v_order': item.order,
            };
            Object.entries(fields).forEach(
                ([key, value]) => {
                    $(key).text(value ?? '-');
                }
            );
            $('#v_status').html(item.status ?
                `<span class="badge bg-success">
                    Active
                </span>` :
                `<span class="badge bg-danger">
                    Inactive
                </span>`
            );

            renderModuleSummary(item.modules ?? []);
        }

        function getStatus(value) {
            return value ? 'Enabled' : 'Disabled';
        }

        function renderModuleSummary(modules) {
            const byKey = {};
            modules.forEach(m => byKey[m.module_key] = m);

            let html = '';
            Object.entries(MODULE_REGISTRY).forEach(([category, categoryModules]) => {
                html += `<h6 class="mt-3">${category}</h6><div class="row">`;
                Object.entries(categoryModules).forEach(([key, meta]) => {
                    const row = byKey[key];
                    const enabled = row ? row.is_enabled : (meta.default_enabled ?? true);
                    let limitText = '';
                    if (meta.type === 'limited') {
                        limitText = row && row.is_unlimited
                            ? 'Unlimited'
                            : `Limit: ${row ? (row.limit_value ?? 0) : (meta.default_limit ?? 5)}`;
                    }
                    const badge = enabled
                        ? '<span class="badge bg-label-success">Enabled</span>'
                        : '<span class="badge bg-label-secondary">Disabled</span>';
                    html += `<div class="col-md-4 mb-1">${meta.label} ${badge} ${limitText ? `<span class="text-muted">(${limitText})</span>` : ''}</div>`;
                });
                html += '</div>';
            });

            $('#v_modules').html(html);
        }
    </script>
@endsection
