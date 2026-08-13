@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Enter POS</h5>
                        <small class="text-muted">Select the Business/Branch/Warehouse you want to sell from.</small>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('pos-screen.context') }}" method="POST">
                            @csrf

                            @if ($is_superadmin)
                                <div class="mb-3">
                                    <label class="form-label">Business <span class="text-danger">*</span></label>
                                    <select class="form-select select2" name="business_id" id="business_id" required>
                                        <option value="">--Select Business--</option>
                                        @foreach ($businesses as $item)
                                            <option value="{{ $item->business_id }}">{{ $item->name ?? '' }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif

                            <div class="mb-3">
                                <label class="form-label">Branch <span class="text-danger">*</span></label>
                                <select class="form-select select2" name="branch_id" id="branch_id" required>
                                    <option value="">--Select Branch--</option>
                                    @foreach ($branches as $item)
                                        <option value="{{ $item->branch_id }}">{{ $item->name ?? '' }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Warehouse</label>
                                <select class="form-select select2" name="warehouse_id" id="warehouse_id">
                                    <option value="">--Select Warehouse--</option>
                                    @foreach ($warehouses as $item)
                                        <option value="{{ $item->warehouse_id }}">{{ $item->name ?? '' }}</option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Manual mode registers already fix a warehouse - this is only
                                    used where the register/session doesn't determine one.</small>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Continue to POS</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('js')
    <script>
        $(document).ready(function() {
            $('.select2').select2();
        });

        $('#business_id').on('change', function() {
            let business_id = $(this).val();

            $('#branch_id').html('<option value="">--Select Branch--</option>');
            $('#warehouse_id').html('<option value="">--Select Warehouse--</option>');

            if (!business_id) {
                return;
            }

            ajaxRequest({
                url: url_local + '/admin/pos-screen/context-options/' + business_id,
                data: {}
            }).then(function(response) {
                let data = response.Data;

                let branchOptions = '<option value="">--Select Branch--</option>';
                $.each(data.branches, function(_, item) {
                    branchOptions += `<option value="${item.branch_id}">${item.name}</option>`;
                });
                $('#branch_id').html(branchOptions);

                let warehouseOptions = '<option value="">--Select Warehouse--</option>';
                $.each(data.warehouses, function(_, item) {
                    warehouseOptions += `<option value="${item.warehouse_id}">${item.name}</option>`;
                });
                $('#warehouse_id').html(warehouseOptions);
            }).catch(function(err) {
                errorMessage(err.Message ?? 'Something went wrong.');
            });
        });
    </script>
@endsection
