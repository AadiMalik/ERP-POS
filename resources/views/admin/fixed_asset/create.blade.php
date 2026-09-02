@php
    use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">Fixed Asset</h4>

    <div class="card">
        <div class="card-header bg-white border-bottom">
            <h5 class="mb-0">{{ isset($fixed_asset) ? 'Update' : 'New' }} Fixed Asset</h5>
        </div>

        <form action="{{ url('admin/fixed-asset/store') }}" method="POST">
            @csrf
            <div class="card-body">
                <input type="hidden" name="fixed_asset_id"
                    value="{{ isset($fixed_asset) ? $fixed_asset->fixed_asset_id : '' }}">
                <input type="hidden" name="depreciation_method" value="straight_line">

                <div class="row g-4">
                    @if (!empty($business) && RoleNames::SUPERADMIN == getRoleName())
                    <div class="col-md-4">
                        <label class="fw-semibold">Business <span class="text-danger">*</span></label>
                        <select class="form-select" name="business_id" id="business_id" required>
                            <option value="">-- Select Business --</option>
                            @foreach ($business as $item)
                            <option value="{{ $item->business_id }}"
                                {{ old('business_id', $fixed_asset->business_id ?? '') == $item->business_id ? 'selected' : '' }}>
                                {{ $item->code ?? '' }} {{ $item->name ?? '' }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    <div class="col-md-4">
                        <label class="fw-semibold">Branch</label>
                        <select class="form-select" name="branch_id" id="branch_id">
                            <option value="">-- Select Branch --</option>
                            @foreach ($branches as $item)
                            <option value="{{ $item->branch_id }}"
                                {{ old('branch_id', $fixed_asset->branch_id ?? '') == $item->branch_id ? 'selected' : '' }}>
                                {{ $item->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="fw-semibold">Category</label>
                        <select class="form-select" name="fixed_asset_category_id" id="fixed_asset_category_id">
                            <option value="">-- Select Category --</option>
                            @foreach ($categories as $item)
                            <option value="{{ $item->fixed_asset_category_id }}"
                                data-life="{{ $item->default_useful_life_years }}"
                                data-residual="{{ $item->default_residual_percent }}"
                                {{ old('fixed_asset_category_id', $fixed_asset->fixed_asset_category_id ?? '') == $item->fixed_asset_category_id ? 'selected' : '' }}>
                                {{ $item->code ? $item->code . ' - ' : '' }}{{ $item->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="fw-semibold">Asset Code</label>
                        <input type="text" class="form-control" name="asset_code"
                            value="{{ old('asset_code', $fixed_asset->asset_code ?? '') }}"
                            placeholder="Leave blank to auto-generate">
                    </div>

                    <div class="col-md-4">
                        <label class="fw-semibold">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name"
                            value="{{ old('name', $fixed_asset->name ?? '') }}" required>
                    </div>

                    <div class="col-md-4">
                        <label class="fw-semibold">Serial Number</label>
                        <input type="text" class="form-control" name="serial_number"
                            value="{{ old('serial_number', $fixed_asset->serial_number ?? '') }}">
                    </div>

                    <div class="col-md-4">
                        <label class="fw-semibold">Location</label>
                        <input type="text" class="form-control" name="location"
                            value="{{ old('location', $fixed_asset->location ?? '') }}">
                    </div>

                    <div class="col-md-8">
                        <label class="fw-semibold">Description</label>
                        <textarea class="form-control" name="description" rows="2">{{ old('description', $fixed_asset->description ?? '') }}</textarea>
                    </div>

                    <div class="col-md-4">
                        <label class="fw-semibold">Purchase Date <span class="text-danger">*</span></label>
                        <input type="text" class="form-control datepicker" name="purchase_date"
                            value="{{ old('purchase_date', isset($fixed_asset) ? localDate($fixed_asset->purchase_date) : localDate(date('Y-m-d'))) }}" required>
                    </div>

                    <div class="col-md-4">
                        <label class="fw-semibold">Purchase Cost <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="purchase_cost" min="0" step="0.01"
                            value="{{ old('purchase_cost', $fixed_asset->purchase_cost ?? '') }}" required>
                    </div>

                    <div class="col-md-4">
                        <label class="fw-semibold">Residual Value</label>
                        <input type="number" class="form-control" name="residual_value" min="0" step="0.01"
                            value="{{ old('residual_value', $fixed_asset->residual_value ?? '') }}">
                    </div>

                    <div class="col-md-4">
                        <label class="fw-semibold">Residual %</label>
                        <input type="number" class="form-control" name="residual_percent" min="0" max="100" step="0.01"
                            value="{{ old('residual_percent', $fixed_asset->residual_percent ?? '') }}">
                    </div>

                    <div class="col-md-4">
                        <label class="fw-semibold">Min Book Value %</label>
                        <input type="number" class="form-control" name="min_book_value_percent" min="0" max="100" step="0.01"
                            value="{{ old('min_book_value_percent', $fixed_asset->min_book_value_percent ?? '') }}">
                    </div>

                    <div class="col-md-4">
                        <label class="fw-semibold">Useful Life (Years) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="useful_life_years" id="useful_life_years" min="1" max="100"
                            value="{{ old('useful_life_years', $fixed_asset->useful_life_years ?? 5) }}" required>
                    </div>

                    <div class="col-md-4">
                        <label class="fw-semibold">Depreciation Method</label>
                        <input type="text" class="form-control" value="Straight Line" readonly>
                    </div>

                    <div class="col-md-4">
                        <label class="fw-semibold">Depreciation Frequency <span class="text-danger">*</span></label>
                        <select class="form-select" name="depreciation_frequency" required>
                            @foreach ($frequencies as $value => $label)
                            <option value="{{ $value }}"
                                {{ old('depreciation_frequency', $fixed_asset->depreciation_frequency ?? 'monthly') == $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="fw-semibold">Adjustment Mode</label>
                        <select class="form-select" name="depreciation_adjustment_mode">
                            @foreach ($adjustment_modes as $value => $label)
                            <option value="{{ $value }}"
                                {{ old('depreciation_adjustment_mode', $fixed_asset->depreciation_adjustment_mode ?? 'none') == $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="fw-semibold">Adjustment Rate %</label>
                        <input type="number" class="form-control" name="depreciation_adjustment_rate" min="0" max="100" step="0.01"
                            value="{{ old('depreciation_adjustment_rate', $fixed_asset->depreciation_adjustment_rate ?? '') }}">
                    </div>

                    <div class="col-md-4">
                        <label class="fw-semibold">Supplier</label>
                        <select class="form-select" name="supplier_id" id="supplier_id">
                            <option value="">-- Select Supplier --</option>
                            @foreach ($suppliers as $item)
                            <option value="{{ $item->supplier_id }}"
                                {{ old('supplier_id', $fixed_asset->supplier_id ?? '') == $item->supplier_id ? 'selected' : '' }}>
                                {{ $item->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="fw-semibold">Purchase ID <small class="text-muted">(optional UUID)</small></label>
                        <input type="text" class="form-control" name="purchase_id"
                            value="{{ old('purchase_id', $fixed_asset->purchase_id ?? '') }}"
                            placeholder="Purchase module reference">
                    </div>

                    <div class="col-md-4">
                        <label class="fw-semibold">Payment Account</label>
                        <select class="form-select" name="payment_account_id" id="payment_account_id">
                            <option value="">-- Select Account --</option>
                            @foreach ($accounts as $item)
                            <option value="{{ $item->account_id }}"
                                {{ old('payment_account_id', $fixed_asset->payment_account_id ?? '') == $item->account_id ? 'selected' : '' }}>
                                {{ $item->code ?? '' }} {{ $item->name ?? '' }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="accounting_from_purchase" id="accounting_from_purchase" value="1"
                                {{ old('accounting_from_purchase', $fixed_asset->accounting_from_purchase ?? false) ? 'checked' : '' }}>
                            <label class="form-check-label" for="accounting_from_purchase">
                                Purchase already posted in Purchase module — do not create acquisition JV
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer border-top">
                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-outline-secondary"
                        onclick="window.history.back()">Cancel</button>
                    <button class="btn btn-primary px-4">Save Fixed Asset</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@section('js')
@if ($errors->any())
<script>
    errorMessage("{{ $errors->first() }}");
</script>
@endif
@if(session('error'))
<script>
    errorMessage("{{ session('error') }}");
</script>
@endif
<script>
    $(document).ready(function() {
        $('#business_id, #branch_id, #fixed_asset_category_id, #supplier_id, #payment_account_id').select2();

        $('#fixed_asset_category_id').on('change', function() {
            let opt = $(this).find(':selected');
            if (opt.data('life')) {
                $('#useful_life_years').val(opt.data('life'));
            }
            if (opt.data('residual') !== undefined && opt.data('residual') !== '') {
                $('[name="residual_percent"]').val(opt.data('residual'));
            }
        });

        $('#business_id').change(function() {
            let business_id = $(this).val();
            if (!business_id) {
                return;
            }
            ajaxRequest({
                    url: url_local + '/admin/branch/by-business/' + business_id,
                    data: {}
                })
                .then((response) => {
                    let data = response.Data;
                    let options = '<option value="">-- Select Branch --</option>';
                    $.each(data, function(index, item) {
                        options += `<option value="${item.branch_id}">${item.name}</option>`;
                    });
                    $('#branch_id').html(options).trigger('change');
                })
                .catch((err) => {
                    errorMessage(err.Message);
                });
        });
    });
</script>
@endsection
