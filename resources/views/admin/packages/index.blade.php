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
                <table id="package_table" class="table display" style="width:100%">
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
                '#v_duration_type': item.duration_type,
                '#v_duration': item.duration_days,
                '#v_order': item.order,
                '#v_branches': item.max_branches,
                '#v_users': item.max_users,
                '#v_customers': item.max_customers,
                '#v_warehouses': item.max_warehouses,
                '#v_categories': item.max_categories,
                '#v_products': item.max_products,
                '#v_suppliers': item.max_suppliers,
                '#v_purchase_orders': item.max_purchase_orders,
                '#v_purchases': item.max_purchases,
                '#v_sales': item.max_sales,
                '#v_transfers': item.max_transfers,
                '#v_expenses': item.max_expenses,
                '#v_vouchers': item.max_vouchers,
                '#v_pos': getStatus(item.is_pos_enabled),
                '#v_inventory': getStatus(item.is_inventory_enabled),
                '#v_accounting': getStatus(item.is_accounting_enabled),
                '#v_hrm': getStatus(item.is_hrm_enabled),
                '#v_payroll': getStatus(item.is_payroll_enabled)
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
        }

        function getStatus(value) {
            return value ? 'Enabled' : 'Disabled';
        }
    </script>
@endsection
