@php
    use App\Enums\RoleNames;
@endphp
<div class="modal fade" id="parentAccountModal" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="modelHeading"></h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="parent_account_form" name="parent_account_form" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="parent_account_id" id="parent_account_id">
                    <div class="row">
                        @if (RoleNames::SUPERADMIN == getRoleName())
                            <div class="col-md-6">
                                <label class="form-label">Business <span class="text-danger">*</span></label>
                                <select id="parent_business_id" name="parent_business_id" class="form-select" required>
                                    <option value="">--Select Business--</option>
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
                            <label class="form-label">Account Type <span class="text-danger">*</span></label>
                            <select id="parent_account_type_id" name="parent_account_type_id" class="form-select" required>
                                <option value="">--Select Account Type--</option>
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
                            <label class="form-label">Account Sub Type <span class="text-danger">*</span></label>
                            <select id="parent_account_sub_type_id" name="parent_account_sub_type_id" class="form-select" required>
                                <option value="">--Select Account Sub Type--</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                Code <span class="text-danger">**</span>
                            </label>
                            <input type="text" class="form-control" id="parent_code" name="parent_code"
                                placeholder="Enter Code" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                Name <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="parent_name" name="parent_name"
                                placeholder="Enter Name" required>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control"  name="parent_description"  id="parent_description"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        Close
                    </button>
                    <button type="submit" id="parentSaveBtn" class="btn btn-primary">
                        Save
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
