@php
    use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">{{ isset($cost_price_adjustment) ? __('cost_price_adjustment.update_heading') : __('cost_price_adjustment.new_heading') }}</h4>
        <div class="card">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0">{{ isset($cost_price_adjustment) ? __('cost_price_adjustment.update_heading') : __('cost_price_adjustment.create_heading') }}</h5>
            </div>
            <div class="card-body">
                <form action="{{ url('admin/cost-price-adjustment') }}" method="POST" id="cpaForm">
                    @csrf
                    <input type="hidden" name="cost_price_adjustment_id" value="{{ $cost_price_adjustment->cost_price_adjustment_id ?? '' }}">
                    <div class="row">
                        @if (!empty($business) && RoleNames::SUPERADMIN == getRoleName())
                            <div class="col-md-3 mb-3">
                                <label>{{ __('common.business') }} <span class="text-danger">*</span></label>
                                <select class="form-control select2" name="business_id" id="business_id">
                                    <option value="">{{ __('common.select_business') }}</option>
                                    @foreach ($business as $item)
                                        <option value="{{ $item->business_id }}"
                                            {{ old('business_id', $cost_price_adjustment->business_id ?? '') == $item->business_id ? 'selected' : '' }}>
                                            {{ $item->code }} - {{ $item->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div class="col-md-3 mb-3">
                            <label>{{ __('common.warehouse') }} <span class="text-danger">*</span></label>
                            <select class="form-control select2" name="warehouse_id" id="warehouse_id"
                                {{ isset($cost_price_adjustment) ? 'disabled' : '' }}>
                                <option value="">{{ __('common.select_warehouse') }}</option>
                                @foreach ($warehouses as $item)
                                    <option value="{{ $item->warehouse_id }}"
                                        {{ old('warehouse_id', $cost_price_adjustment->warehouse_id ?? '') == $item->warehouse_id ? 'selected' : '' }}>
                                        {{ $item->name }}
                                    </option>
                                @endforeach
                            </select>
                            @if (isset($cost_price_adjustment))
                                <input type="hidden" name="warehouse_id" value="{{ $cost_price_adjustment->warehouse_id }}">
                            @endif
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>{{ __('common.product') }} <span class="text-danger">*</span></label>
                            <select class="form-control select2" name="product_id" id="product_id"
                                {{ isset($cost_price_adjustment) ? 'disabled' : '' }}>
                                <option value="">{{ __('common.select_product') }}</option>
                                @foreach ($products as $item)
                                    <option value="{{ $item->product_id }}"
                                        {{ old('product_id', $cost_price_adjustment->product_id ?? '') == $item->product_id ? 'selected' : '' }}>
                                        {{ $item->name }}
                                    </option>
                                @endforeach
                            </select>
                            @if (isset($cost_price_adjustment))
                                <input type="hidden" name="product_id" value="{{ $cost_price_adjustment->product_id }}">
                            @endif
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>{{ __('common.variation') }} <span class="text-danger">*</span></label>
                            <select class="form-control select2" name="product_variation_id" id="product_variation_id"
                                {{ isset($cost_price_adjustment) ? 'disabled' : '' }}>
                                <option value="">{{ __('common.select_variation') }}</option>
                            </select>
                            @if (isset($cost_price_adjustment))
                                <input type="hidden" name="product_variation_id" value="{{ $cost_price_adjustment->product_variation_id }}">
                            @endif
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>{{ __('cost_price_adjustment.reference_no') }}</label>
                            <input type="text" class="form-control" name="reference_no" readonly
                                value="{{ $cost_price_adjustment->reference_no ?? ($reference_no ?? __('common.auto_generated')) }}">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>{{ __('cost_price_adjustment.adjustment_date') }} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control datepicker" name="adjustment_date"
                                value="{{ old('adjustment_date', isset($cost_price_adjustment) ? businessDate($cost_price_adjustment->adjustment_date) : businessDate(businessToday())) }}">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>{{ __('cost_price_adjustment.previous_cost_price') }}</label>
                            <input type="text" class="form-control" id="previous_cost_price_display" readonly
                                value="{{ isset($cost_price_adjustment) ? currency($cost_price_adjustment->previous_cost_price) : '-' }}">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>{{ __('cost_price_adjustment.new_cost_price') }} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="new_cost_price" id="new_cost_price"
                                value="{{ old('new_cost_price', $cost_price_adjustment->new_cost_price ?? '') }}">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>{{ __('cost_price_adjustment.quantity_on_hand') }}</label>
                            <input type="text" class="form-control" id="quantity_on_hand_display" readonly
                                value="{{ isset($cost_price_adjustment) ? decimal($cost_price_adjustment->quantity_on_hand) : '-' }}">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>{{ __('cost_price_adjustment.difference_per_unit') }}</label>
                            <input type="text" class="form-control" id="difference_per_unit_display" readonly
                                value="{{ isset($cost_price_adjustment) ? currency($cost_price_adjustment->difference_per_unit) : '-' }}">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>{{ __('cost_price_adjustment.total_adjustment_amount') }}</label>
                            <input type="text" class="form-control fw-bold" id="total_adjustment_amount_display" readonly
                                value="{{ isset($cost_price_adjustment) ? currency($cost_price_adjustment->total_adjustment_amount) : '-' }}">
                        </div>
                        <div id="noStockWarning" class="col-md-12 mb-3" style="display:none;">
                            <div class="alert alert-warning mb-0">{{ __('cost_price_adjustment.no_stock_warning') }}</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>{{ __('cost_price_adjustment.reason') }} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="reason" placeholder="{{ __('cost_price_adjustment.reason_placeholder') }}"
                                value="{{ old('reason', $cost_price_adjustment->reason ?? '') }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>{{ __('cost_price_adjustment.reference_optional') }}</label>
                            <input type="text" class="form-control" name="reference" placeholder="{{ __('cost_price_adjustment.reference_placeholder') }}"
                                value="{{ old('reference', $cost_price_adjustment->reference ?? '') }}">
                        </div>
                        <div class="col-md-12 mb-3">
                            <label>{{ __('common.notes') }}</label>
                            <textarea class="form-control" rows="2" name="notes">{{ old('notes', $cost_price_adjustment->notes ?? '') }}</textarea>
                        </div>
                    </div>

                    <div class="card" id="batchBreakdownCard" style="display:none;">
                        <div class="card-header">
                            <h6 class="mb-0">{{ __('cost_price_adjustment.batch_breakdown') }}</h6>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th>{{ __('common.batch_no') }}</th>
                                        <th>{{ __('common.quantity') }}</th>
                                        <th>{{ __('cost_price_adjustment.previous_cost_price') }}</th>
                                        <th>{{ __('cost_price_adjustment.new_cost_price') }}</th>
                                    </tr>
                                </thead>
                                <tbody id="batchBreakdownRows"></tbody>
                            </table>
                        </div>
                    </div>

                    <br>
                    <div class="row">
                        <div class="col-md-12">
                            <button type="submit" class="text-end btn btn-primary" id="submitBtn">
                                {{ isset($cost_price_adjustment) ? __('common.update') : __('common.save') }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('js')
    @if ($errors->any())
        <script>errorMessage("{{ $errors->first() }}");</script>
    @endif
    @if (session('error'))
        <script>errorMessage("{{ session('error') }}");</script>
    @endif
    <script>
        var isEditMode = {{ isset($cost_price_adjustment) ? 'true' : 'false' }};
        window.i18n_cost_price_adjustment = @json([
            'unable_to_load_variations' => __('cost_price_adjustment.unable_to_load_variations'),
            'new_cost_must_be_gt_zero' => __('cost_price_adjustment.new_cost_must_be_gt_zero'),
        ]);

        $(function() {
            if ($.fn.select2) {
                $('.select2').select2({ width: '100%' });
            }
            if (isEditMode) {
                loadVariationsForEdit();
            }
        });

        function currentWarehouseId() {
            return $('#warehouse_id').val();
        }

        $(document).on('change', '#product_id', function() {
            let productId = $(this).val();
            $('#product_variation_id').html('<option value="">{{ __('common.select_variation') }}</option>');
            resetCostDisplays();
            if (!productId) return;
            loadVariations(productId, null);
        });

        function loadVariations(productId, selectedVariationId) {
            $.ajax({
                url: url_local + '/admin/product/variation-by-product/' + productId,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    let html = '<option value="">{{ __('common.select_variation') }}</option>';
                    if (response.Success && response.Data.length) {
                        $.each(response.Data, function(_, variation) {
                            let selected = selectedVariationId && selectedVariationId == variation.product_variation_id ? 'selected' : '';
                            html += `<option value="${variation.product_variation_id}" ${selected}>${variation.name}</option>`;
                        });
                    }
                    $('#product_variation_id').html(html);
                    if (selectedVariationId) {
                        $('#product_variation_id').val(selectedVariationId);
                        loadStockAndBatches();
                    }
                },
                error: function() {
                    errorMessage(window.i18n_cost_price_adjustment.unable_to_load_variations);
                }
            });
        }

        function loadVariationsForEdit() {
            let productId = $('#product_id').val();
            let variationId = "{{ $cost_price_adjustment->product_variation_id ?? '' }}";
            if (productId && variationId) {
                loadVariations(productId, variationId);
            }
        }

        $(document).on('change', '#product_variation_id, #warehouse_id', function() {
            loadStockAndBatches();
        });

        function resetCostDisplays() {
            $('#previous_cost_price_display').val('-');
            $('#quantity_on_hand_display').val('-');
            $('#difference_per_unit_display').val('-');
            $('#total_adjustment_amount_display').val('-');
            $('#noStockWarning').hide();
            $('#batchBreakdownCard').hide();
            $('#batchBreakdownRows').html('');
        }

        function loadStockAndBatches() {
            let warehouseId = currentWarehouseId();
            let variationId = $('#product_variation_id').val();

            resetCostDisplays();

            if (!warehouseId || !variationId) {
                return;
            }

            $.ajax({
                url: url_local + '/admin/cost-price-adjustment/stock/' + warehouseId + '/' + variationId,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (!response.Success) return;
                    $('#previous_cost_price_display').val(currency(response.Data.avg_price));
                    $('#quantity_on_hand_display').val(decimal(response.Data.quantity));
                    $('#previous_cost_price_display').data('value', decimal(response.Data.avg_price));
                    $('#quantity_on_hand_display').data('value', decimal(response.Data.quantity));

                    if (decimal(response.Data.quantity) <= 0) {
                        $('#noStockWarning').show();
                    }

                    recalculate();
                }
            });

            $.ajax({
                url: url_local + '/admin/cost-price-adjustment/batches/' + warehouseId + '/' + variationId,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (!response.Success || !response.Data.length) {
                        $('#batchBreakdownCard').hide();
                        return;
                    }
                    let rows = '';
                    $.each(response.Data, function(_, batch) {
                        rows += `<tr>
                            <td>${batch.batch_no}</td>
                            <td>${decimal(batch.quantity)}</td>
                            <td>${currency(batch.avg_price)}</td>
                            <td class="fw-bold new-batch-cost">-</td>
                        </tr>`;
                    });
                    $('#batchBreakdownRows').html(rows);
                    $('#batchBreakdownCard').show();
                    recalculate();
                }
            });
        }

        $(document).on('keyup change', '#new_cost_price', function() {
            recalculate();
        });

        function recalculate() {
            let newCost = decimal($('#new_cost_price').val()) || 0;
            let prevCost = decimal($('#previous_cost_price_display').data('value')) || 0;
            let qty = decimal($('#quantity_on_hand_display').data('value')) || 0;
            let difference = newCost - prevCost;
            let total = qty * difference;

            $('#difference_per_unit_display').val(currency(difference));
            $('#total_adjustment_amount_display').val(currency(total));

            $('#batchBreakdownRows .new-batch-cost').text(newCost > 0 ? currency(newCost) : '-');
        }

        $('#cpaForm').on('submit', function(e) {
            if (!$('#new_cost_price').val() || decimal($('#new_cost_price').val()) <= 0) {
                e.preventDefault();
                errorMessage(window.i18n_cost_price_adjustment.new_cost_must_be_gt_zero);
                return false;
            }
        });
    </script>
@endsection
