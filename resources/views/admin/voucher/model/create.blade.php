@php
use App\Enums\RoleNames;
@endphp
<div class="modal fade" id="ajaxModel" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="modelHeading"></h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="pos_voucher_form" name="pos_voucher_form" class="d-flex flex-column overflow-hidden">
                <div class="modal-body">
                    <input type="hidden" name="voucher_id" id="voucher_id">
                    <div class="row">
                        @if (RoleNames::SUPERADMIN == getRoleName())
                        <div class="col-md-12 mb-3">
                            <label class="form-label">{{ __('common.business') }} <span class="text-danger">*</span></label>
                            <select id="business_id" name="business_id" class="form-select" required>
                                <option value="">{{ __('common.select_business') }}</option>
                                @foreach ($business as $item)
                                <option value="{{ $item->business_id }}">{{ isset($item->code) ? $item->code : '' }}
                                    {{ $item->name ?? '' }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        @endif
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                {{ __('common.name') }} <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="name" name="name" placeholder="{{ __('common.enter_name') }}" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                {{ __('common.code') }} <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="code" name="code" placeholder="{{ __('common.enter_code') }}" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">
                                {{ __('vouchers.promo_type') }} <span class="text-danger">*</span>
                            </label>
                            <select id="promo_type" name="promo_type" class="form-select">
                                <option value="discount">{{ __('vouchers.promo_discount') }}</option>
                                <option value="bogo">{{ __('vouchers.promo_bogo') }}</option>
                                <option value="buy_x_get_y">{{ __('vouchers.promo_buy_x_get_y') }}</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3 discount-only-field">
                            <label class="form-label">
                                {{ __('common.type') }} <span class="text-danger">*</span>
                            </label>
                            <select id="type" name="type" class="form-select" required>
                                <option value="percent">{{ __('common.percent') }}</option>
                                <option value="fixed">{{ __('common.fixed') }}</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3 discount-only-field">
                            <label class="form-label">
                                {{ __('common.value') }} <span class="text-danger">*</span>
                            </label>
                            <input type="number" step="0.001" min="0" class="form-control" id="value" name="value" placeholder="{{ __('common.please_enter_value') }}">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('common.status') }}</label>
                            <select id="status" name="status" class="form-select">
                                <option value="active">{{ __('common.active') }}</option>
                                <option value="inactive">{{ __('common.inactive') }}</option>
                            </select>
                        </div>

                        <div class="col-md-4 mb-3 bogo-only-field" style="display:none;">
                            <label class="form-label">{{ __('vouchers.buy_quantity') }} <span class="text-danger">*</span></label>
                            <input type="number" min="1" class="form-control" id="buy_quantity" name="buy_quantity" placeholder="e.g. 2">
                        </div>
                        <div class="col-md-4 mb-3 bogo-only-field" style="display:none;">
                            <label class="form-label">{{ __('vouchers.get_quantity') }} <span class="text-danger">*</span></label>
                            <input type="number" min="1" class="form-control" id="get_quantity" name="get_quantity" placeholder="e.g. 1">
                        </div>
                        <div class="col-md-4 mb-3 bogo-only-field" style="display:none;">
                            <label class="form-label">{{ __('vouchers.get_discount_percent') }}</label>
                            <input type="number" step="0.01" min="0" max="100" class="form-control" id="get_discount_percent" name="get_discount_percent" value="100" placeholder="{{ __('vouchers.fully_free_hint') }}">
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('vouchers.max_discount_amount') }}</label>
                            <input type="number" step="0.001" min="0" class="form-control" id="max_discount_amount" name="max_discount_amount" placeholder="{{ __('vouchers.no_cap') }}">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label d-block">{{ __('vouchers.exclusive_voucher') }}</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="is_exclusive" name="is_exclusive" value="1">
                                <label class="form-check-label" for="is_exclusive">{{ __('vouchers.cannot_combine_discount') }}</label>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('vouchers.valid_from') }}</label>
                            <input type="date" class="form-control" id="valid_from" name="valid_from">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('vouchers.valid_to') }}</label>
                            <input type="date" class="form-control" id="valid_to" name="valid_to">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('vouchers.time_start') }}</label>
                            <input type="time" class="form-control" id="time_start" name="time_start">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('vouchers.time_end') }}</label>
                            <input type="time" class="form-control" id="time_end" name="time_end">
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label d-block">{{ __('vouchers.days_of_week') }}</label>
                            @foreach (['0' => __('common.days_sun'), '1' => __('common.days_mon'), '2' => __('common.days_tue'), '3' => __('common.days_wed'), '4' => __('common.days_thu'), '5' => __('common.days_fri'), '6' => __('common.days_sat')] as $value => $label)
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input days-of-week" type="checkbox" name="days_of_week[]" id="dow_{{ $value }}" value="{{ $value }}">
                                    <label class="form-check-label" for="dow_{{ $value }}">{{ $label }}</label>
                                </div>
                            @endforeach
                            <small class="text-muted d-block">{{ __('vouchers.days_unchecked_hint') }}</small>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('vouchers.total_usage_limit') }}</label>
                            <input type="number" min="0" class="form-control" id="usage_limit_total" name="usage_limit_total" placeholder="{{ __('common.unlimited') }}">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('vouchers.per_customer_usage_limit') }}</label>
                            <input type="number" min="0" class="form-control" id="usage_limit_per_customer" name="usage_limit_per_customer" placeholder="{{ __('common.unlimited') }}">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('vouchers.min_order_amount') }}</label>
                            <input type="number" step="0.001" min="0" class="form-control" id="min_order_amount" name="min_order_amount" placeholder="{{ __('vouchers.no_minimum') }}">
                        </div>

                        <div class="col-md-12"><hr><small class="text-muted">{{ __('vouchers.scope_hint') }}</small></div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('vouchers.products') }}</label>
                            <input type="hidden" name="product_ids[]" value="">
                            <select id="product_ids" name="product_ids[]" class="form-select select2-multiple" multiple>
                                @foreach ($products as $item)
                                <option value="{{ $item->product_id }}">{{ $item->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('vouchers.categories') }}</label>
                            <input type="hidden" name="category_ids[]" value="">
                            <select id="category_ids" name="category_ids[]" class="form-select select2-multiple" multiple>
                                @foreach ($categories as $item)
                                <option value="{{ $item->category_id }}">{{ $item->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('vouchers.brands') }}</label>
                            <input type="hidden" name="brand_ids[]" value="">
                            <select id="brand_ids" name="brand_ids[]" class="form-select select2-multiple" multiple>
                                @foreach ($brands as $item)
                                <option value="{{ $item->brand_id }}">{{ $item->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('vouchers.variations') }}</label>
                            <input type="hidden" name="variation_ids[]" value="">
                            <select id="variation_ids" name="variation_ids[]" class="form-select select2-multiple" multiple>
                                @foreach ($variations as $item)
                                <option value="{{ $item->product_variation_id }}">{{ $item->product->name ?? '' }} - {{ $item->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('vouchers.customers') }}</label>
                            <input type="hidden" name="customer_ids[]" value="">
                            <select id="customer_ids" name="customer_ids[]" class="form-select select2-multiple" multiple>
                                @foreach ($customers as $item)
                                <option value="{{ $item->user_id }}">{{ $item->user->name ?? '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('vouchers.order_types') }}</label>
                            <input type="hidden" name="order_type_ids[]" value="">
                            <select id="order_type_ids" name="order_type_ids[]" class="form-select select2-multiple" multiple>
                                @foreach ($order_types as $item)
                                <option value="{{ $item->order_type_id }}">{{ $item->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('vouchers.branches') }}</label>
                            <input type="hidden" name="branch_ids[]" value="">
                            <select id="branch_ids" name="branch_ids[]" class="form-select select2-multiple" multiple>
                                @foreach ($branches as $item)
                                <option value="{{ $item->branch_id }}">{{ $item->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('vouchers.sale_types') }}</label>
                            <input type="hidden" name="sale_type_ids[]" value="">
                            <select id="sale_type_ids" name="sale_type_ids[]" class="form-select select2-multiple" multiple>
                                @foreach ($sale_types as $item)
                                <option value="{{ $item->sale_type_id }}">{{ $item->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('vouchers.order_sources') }}</label>
                            <input type="hidden" name="order_source_ids[]" value="">
                            <select id="order_source_ids" name="order_source_ids[]" class="form-select select2-multiple" multiple>
                                @foreach ($order_sources as $item)
                                <option value="{{ $item->order_source_id }}">{{ $item->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('vouchers.payment_methods') }}</label>
                            <input type="hidden" name="payment_method_ids[]" value="">
                            <select id="payment_method_ids" name="payment_method_ids[]" class="form-select select2-multiple" multiple>
                                @foreach ($payment_methods as $item)
                                <option value="{{ $item->payment_method_id }}">{{ $item->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-12 bogo-only-field" style="display:none;">
                            <hr><small class="text-muted">"Get" scope for Buy X Get Y - leave empty to give the free/discounted item from the same Products/Categories selected above.</small>
                        </div>
                        <div class="col-md-6 mb-3 bogo-only-field" style="display:none;">
                            <label class="form-label">{{ __('vouchers.get_products') }}</label>
                            <input type="hidden" name="get_product_ids[]" value="">
                            <select id="get_product_ids" name="get_product_ids[]" class="form-select select2-multiple" multiple>
                                @foreach ($products as $item)
                                <option value="{{ $item->product_id }}">{{ $item->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3 bogo-only-field" style="display:none;">
                            <label class="form-label">{{ __('vouchers.get_categories') }}</label>
                            <input type="hidden" name="get_category_ids[]" value="">
                            <select id="get_category_ids" name="get_category_ids[]" class="form-select select2-multiple" multiple>
                                @foreach ($categories as $item)
                                <option value="{{ $item->category_id }}">{{ $item->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        Close
                    </button>
                    <button type="submit" id="saveBtn" class="btn btn-primary">
                        Save
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>
