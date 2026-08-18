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
            <form id="expense_category_form" name="expense_category_form" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="expense_category_id" id="expense_category_id">
                    <div class="row">
                        @if (RoleNames::SUPERADMIN == getRoleName())
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Business <span class="text-danger">*</span></label>
                                <select id="business_id" name="business_id" class="form-select" required>
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
                        <div class="col-md-12 mb-3">
                            <label class="form-label">
                                Name <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="name" name="name"
                                placeholder="Enter Name" required>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">
                                Code
                            </label>
                            <input type="text" class="form-control" id="code" name="code"
                                placeholder="Enter Code">
                        </div>
                        <div class="col-md-12 mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="use_default_account"
                                    name="use_default_account" value="1" checked>
                                <label class="form-check-label" for="use_default_account">
                                    Use Business Default Expense Account (from Accounting Settings)
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12 mb-3 d-none" id="account_id_wrap">
                            <label class="form-label">
                                Expense Account (Chart of Accounts) <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="account_id" name="account_id">
                                <option value="">--Select Account--</option>
                                @foreach ($accounts as $item)
                                    <option value="{{ $item->account_id }}">{{ $item->code }} - {{ $item->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">
                                Status
                            </label>
                            <select class="form-select" id="status" name="status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">
                                Description
                            </label>
                            <textarea class="form-control" id="description" name="description" placeholder="Enter Description"></textarea>
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
