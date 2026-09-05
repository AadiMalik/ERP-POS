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
            <form id="pos_register_form" name="pos_register_form">
                <div class="modal-body">
                    <input type="hidden" name="pos_register_id" id="pos_register_id">
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
                                {{ __('common.name') }} <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="name" name="name" placeholder="{{ __('common.enter_name') }}" required>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">
                                {{ __('common.code') }} <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="code" name="code" placeholder="{{ __('common.enter_code') }}" required>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">{{ __('common.branch') }} <span class="text-danger">*</span></label>
                            <select id="branch_id" name="branch_id" class="form-select" required>
                                <option value="">{{ __('common.select_branch') }}</option>
                                @foreach ($branches as $item)
                                <option value="{{ $item->branch_id }}">{{ $item->name ?? '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">{{ __('common.warehouse') }} <span class="text-danger">*</span></label>
                            <select id="warehouse_id" name="warehouse_id" class="form-select" required>
                                <option value="">{{ __('common.select_warehouse') }}</option>
                                @foreach ($warehouses as $item)
                                <option value="{{ $item->warehouse_id }}">{{ $item->name ?? '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">{{ __('common.mode') }} <span class="text-danger">*</span></label>
                            <select id="mode" name="mode" class="form-select" required>
                                <option value="manual">{{ __('common.manual') }}</option>
                                <option value="automatic">{{ __('common.automatic') }}</option>
                            </select>
                        </div>
                        <div class="col-md-12 mb-3" id="assignedUserWrapper">
                            <label class="form-label">{{ __('common.assigned_user') }}</label>
                            <select id="assigned_user_id" name="assigned_user_id" class="form-select">
                                <option value="">{{ __('common.select_user') }}</option>
                                @foreach ($users as $item)
                                <option value="{{ $item->id }}">{{ $item->name ?? '' }}</option>
                                @endforeach
                            </select>
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
