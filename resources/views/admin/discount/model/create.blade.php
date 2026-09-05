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
            <form id="pos_discount_form" name="pos_discount_form">
                <div class="modal-body">
                    <input type="hidden" name="discount_id" id="discount_id">
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
                        <div class="col-md-4 mb-3">
                            <label class="form-label">
                                Type <span class="text-danger">*</span>
                            </label>
                            <select id="type" name="type" class="form-select" required>
                                <option value="percent">{{ __('common.percent') }}</option>
                                <option value="fixed">{{ __('common.fixed') }}</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">
                                Value <span class="text-danger">*</span>
                            </label>
                            <input type="number" step="0.001" min="0" class="form-control" id="value" name="value" placeholder="{{ __('discounts.enter_value') }}" required>
                        </div>
                        <div class="col-md-4 mb-3">
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
