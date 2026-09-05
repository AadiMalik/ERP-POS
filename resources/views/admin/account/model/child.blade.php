@php
    use App\Enums\RoleNames;
@endphp
<div class="modal fade" id="childAccountModal" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="modelHeading"></h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="child_account_form" name="child_account_form" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="child_account_id" id="child_account_id">
                    <div class="row">
                        @if (RoleNames::SUPERADMIN == getRoleName())
                            <div class="col-md-6">
                                <label class="form-label">{{ __('common.business') }}</label>
                                <select id="child_business_id" name="child_business_id" class="form-select">
                                    <option value="">{{ __('accounts.system_template_global') }}</option>
                                    @foreach ($business as $item)
                                        <option value="{{ $item->business_id }}">
                                            {{ isset($item->code) ? $item->code : '' }}
                                            {{ $item->name ?? '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div class="col-md-6">
                            <label class="form-label">{{ __('accounts.account_type') }} <span class="text-danger">*</span></label>
                            <select id="child_account_type_id" name="child_account_type_id" class="form-select" required>
                                <option value="">{{ __('accounts.select_account_type') }}</option>
                                @if (RoleNames::SUPERADMIN != getRoleName())
                                    @foreach ($account_types as $item)
                                        <option value="{{ $item->account_type_id }}">
                                            {{ isset($item->code) ? $item->code : '' }}
                                            {{ $item->name ?? '' }}
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('accounts.account_sub_type') }} <span class="text-danger">*</span></label>
                            <select id="child_account_sub_type_id" name="child_account_sub_type_id" class="form-select" required>
                                <option value="">{{ __('accounts.select_account_sub_type') }}</option>
                                
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('accounts.parent_account') }} <span class="text-danger">*</span></label>
                            <select id="child_parent_account_id" name="child_parent_account_id" class="form-select" required>
                                <option value="">{{ __('accounts.select_parent_account') }}</option>
                                
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                {{ __('common.code') }} <span class="text-danger">**</span>
                            </label>
                            <input type="text" class="form-control" id="child_code" name="child_code"
                                placeholder="Auto-generated from parent account" readonly required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                {{ __('common.name') }} <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="child_name" name="child_name"
                                placeholder="{{ __('common.enter_name') }}" required>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">{{ __('common.description') }}</label>
                            <textarea class="form-control"  name="child_description"  id="child_description"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        {{ __('common.close') }}
                    </button>
                    <button type="submit" id="childSaveBtn" class="btn btn-primary">
                        {{ __('common.save') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
