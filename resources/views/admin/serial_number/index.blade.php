@php
    use App\Enums\RoleNames;
    use App\Enums\SerialStatus;
@endphp

@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">Serial Numbers</h4>

        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <div>
                    <button type="button" id="toggleFilter" class="btn btn-outline-primary">
                        <i class="fa fa-filter"></i> Filters
                    </button>
                </div>
                @canAccess('serial-number.create')
                    <button type="button" id="addFoundUnitBtn" class="btn rounded-pill btn-primary">
                        <i class="icon-base fa fa-plus mr-5"></i> Add Found Unit
                    </button>
                @endcanAccess
            </div>
            <div class="card-body">
                <div id="filterSection" class="card-body border-bottom" style="display:none;">
                    <div class="row g-3">
                        @if (RoleNames::SUPERADMIN == getRoleName())
                            <div class="col-md-3">
                                <label class="form-label">Business</label>
                                <select id="filter_business_id" class="form-select">
                                    <option value="">--All Businesses--</option>
                                    @foreach ($business as $item)
                                        <option value="{{ $item->business_id }}">{{ $item->code ?? '' }} {{ $item->name ?? '' }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div class="col-md-3">
                            <label class="form-label">Product</label>
                            <select id="filter_product_id" class="form-select">
                                <option value="">--All Products--</option>
                                @foreach ($products as $item)
                                    <option value="{{ $item->product_id }}">{{ $item->name ?? '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Variation</label>
                            <select id="filter_product_variation_id" class="form-select">
                                <option value="">--All Variations--</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Warehouse</label>
                            <select id="filter_warehouse_id" class="form-select">
                                <option value="">--All Warehouses--</option>
                                @foreach ($warehouses as $item)
                                    <option value="{{ $item->warehouse_id }}">{{ $item->name ?? '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select id="filter_status" class="form-select">
                                <option value="">--All Statuses--</option>
                                @foreach (SerialStatus::getOptions() as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end gap-2">
                            <button type="button" id="search_btn" class="btn btn-primary">Search</button>
                            <button type="button" id="reset_filter" class="btn btn-outline-secondary">Reset</button>
                        </div>
                    </div>
                </div>
                <div class="table-responsive text-nowrap p-4">
                    <table id="serial_number_table" class="table display datatables" style="width:100%">
                        <thead>
                            <tr>
                                <th>Serial No.</th>
                                <th>Product</th>
                                <th>Variation</th>
                                <th>Warehouse</th>
                                <th>Status</th>
                                <th>Customer</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addFoundUnitModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Found Unit</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted">Records a physically-found serialized unit that was never entered into the
                        system (e.g. discovered during a stock count) - this also increases stock for the selected
                        warehouse.</p>
                    <div class="mb-3">
                        <label class="form-label">Product</label>
                        <select id="fu_product_id" class="form-select"></select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Variation</label>
                        <select id="fu_product_variation_id" class="form-select"></select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Warehouse</label>
                        <select id="fu_warehouse_id" class="form-select">
                            <option value="">--Select Warehouse--</option>
                            @foreach ($warehouses as $item)
                                <option value="{{ $item->warehouse_id }}">{{ $item->name ?? '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Serial Number</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="fu_serial_no">
                            @include('admin.partials.barcode_scanner', ['targetInputId' => '#fu_serial_no'])
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Unit Cost (optional)</label>
                        <input type="number" step="0.01" min="0" class="form-control" id="fu_unit_cost">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" id="fu_notes" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="fuSaveBtn">Save</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    @include('admin.partials.datatable', [
        'columns' => "
        {data:'serial_no',name:'serial_no'},
        {data:'product',name:'product', sortable:false},
        {data:'variation',name:'variation', sortable:false},
        {data:'warehouse',name:'warehouse', sortable:false},
        {data:'status_badge',name:'status_badge', sortable:false, searchable:false},
        {data:'customer',name:'customer', sortable:false},
        {data:'action',name:'action', sortable:false, searchable:false}",
        'route' => 'serial-number/data',
        'buttons' => false,
        'pageLength' => 25,
        'class' => 'serial_number_table',
        'variable' => 'serial_number_table',
        'params' =>
            "business_id:$('#filter_business_id').val(),product_id:$('#filter_product_id').val(),product_variation_id:$('#filter_product_variation_id').val(),warehouse_id:$('#filter_warehouse_id').val(),status:$('#filter_status').val()",
    ])

    <script>
        $(document).ready(function() {
            $('#toggleFilter').on('click', function() {
                $('#filterSection').slideToggle();
            });
            $('#filter_business_id, #filter_product_id, #filter_product_variation_id, #filter_warehouse_id, #filter_status')
                .select2();
        });

        $('#search_btn').on('click', function() {
            initDataTableserial_number_table();
        });

        $('#reset_filter').on('click', function() {
            $('#filterSection select').val('').trigger('change');
            initDataTableserial_number_table();
        });

        $('#filter_product_id').on('change', function() {
            let productId = $(this).val();
            $('#filter_product_variation_id').html('<option value="">--All Variations--</option>');
            if (!productId) return;
            ajaxRequest({
                url: url_local + '/admin/product/variation-by-product/' + productId,
            }).then(function(response) {
                let options = '<option value="">--All Variations--</option>';
                (response.Data || []).forEach(function(v) {
                    options += `<option value="${v.product_variation_id}">${v.name}</option>`;
                });
                $('#filter_product_variation_id').html(options);
            });
        });

        // ======================================================
        // ADD FOUND UNIT
        // ======================================================
        @canAccess('serial-number.create')
        var addFoundUnitModal = null;

        $('#addFoundUnitBtn').on('click', function() {
            let productHtml = '<option value="">--Select Product--</option>';
            @foreach ($products as $item)
                productHtml += `<option value="{{ $item->product_id }}">{{ $item->name }}</option>`;
            @endforeach
            $('#fu_product_id').html(productHtml);
            $('#fu_product_variation_id').html('<option value="">--Select Variation--</option>');
            $('#fu_warehouse_id').val('');
            $('#fu_serial_no').val('');
            $('#fu_unit_cost').val('');
            $('#fu_notes').val('');

            addFoundUnitModal = addFoundUnitModal || new bootstrap.Modal(document.getElementById('addFoundUnitModal'));
            addFoundUnitModal.show();
        });

        $('#fu_product_id').on('change', function() {
            let productId = $(this).val();
            $('#fu_product_variation_id').html('<option value="">--Select Variation--</option>');
            if (!productId) return;
            ajaxRequest({
                url: url_local + '/admin/product/variation-by-product/' + productId,
            }).then(function(response) {
                let options = '<option value="">--Select Variation--</option>';
                (response.Data || []).forEach(function(v) {
                    if (v.track_serial_number) {
                        options += `<option value="${v.product_variation_id}">${v.name}</option>`;
                    }
                });
                $('#fu_product_variation_id').html(options);
                if ($('#fu_product_variation_id option').length <= 1) {
                    errorMessage('This product has no serial-tracked variations.');
                }
            });
        });

        $('#fuSaveBtn').on('click', function() {
            let payload = {
                product_id: $('#fu_product_id').val(),
                product_variation_id: $('#fu_product_variation_id').val(),
                warehouse_id: $('#fu_warehouse_id').val(),
                serial_no: $('#fu_serial_no').val().trim(),
                unit_cost: $('#fu_unit_cost').val() || null,
                notes: $('#fu_notes').val(),
            };

            if (!payload.product_id || !payload.product_variation_id || !payload.warehouse_id || !payload.serial_no) {
                errorMessage('Product, variation, warehouse, and serial number are required.');
                return;
            }

            ajaxRequest({
                url: url_local + '/admin/serial-number/add-found-unit',
                method: 'POST',
                data: payload,
            }).then(function(res) {
                successMessage(res.Message);
                addFoundUnitModal.hide();
                initDataTableserial_number_table();
            }).catch(function(err) {
                errorMessage(err.Message || 'Unable to add this serial number.');
            });
        });
        @endcanAccess
    </script>
@endsection
