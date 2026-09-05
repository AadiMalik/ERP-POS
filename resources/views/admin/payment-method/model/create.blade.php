@php
use App\Enums\RoleNames;
@endphp
<div class="modal fade" id="ajaxModel" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="modelHeading"></h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="pos_payment_method_form" name="pos_payment_method_form">
                <div class="modal-body">
                    <input type="hidden" name="payment_method_id" id="payment_method_id">
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
                        <div class="col-md-12 mb-3">
                            <label class="form-label">
                                Name <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="name" name="name" placeholder="{{ __('common.enter_name') }}" required>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">
                                Code <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="code" name="code" placeholder="{{ __('common.enter_code') }}" required>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">
                                Type <span class="text-danger">*</span>
                            </label>
                            <select id="type" name="type" class="form-select" required>
                                <option value="cash">{{ __('payment_methods.cash') }}</option>
                                <option value="card">{{ __('payment_methods.card') }}</option>
                                <option value="bank">{{ __('payment_methods.bank') }}</option>
                                <option value="credit">{{ __('payment_methods.credit') }}</option>
                                <option value="store_credit">{{ __('payment_methods.store_credit') }}</option>
                                <option value="wallet">{{ __('payment_methods.wallet') }}</option>
                                <option value="other">{{ __('payment_methods.other') }}</option>
                            </select>
                        </div>
                        <div class="col-md-12 mb-3" id="account_id_wrapper">
                            <label class="form-label">
                                Account <span class="text-danger">*</span>
                            </label>
                            <select id="account_id" name="account_id" class="form-select">
                                <option value="">{{ __('payment_methods.select_account') }}</option>
                                @foreach ($accounts as $item)
                                <option value="{{ $item->account_id }}">{{ isset($item->code) ? $item->code : '' }}
                                    {{ $item->name ?? '' }}
                                </option>
                                @endforeach
                            </select>
                            <small class="text-muted">{{ __('payment_methods.account_hint') }}</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('common.sort_order') }}</label>
                            <input type="number" class="form-control" id="sort_order" name="sort_order" value="0" min="0">
                        </div>
                        <div class="col-md-6 mb-3 d-flex align-items-end">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_default" name="is_default" value="1">
                                <label class="form-check-label" for="is_default">
                                    {{ __('payment_methods.set_as_default') }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">{{ __('common.status') }}</label>
                            <select id="status" name="status" class="form-select">
                                <option value="active">{{ __('common.active') }}</option>
                                <option value="inactive">{{ __('common.inactive') }}</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        {{ __('common.close') }}
                    </button>
                    <button type="submit" id="saveBtn" class="btn btn-primary">
                        {{ __('common.save') }}
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>
