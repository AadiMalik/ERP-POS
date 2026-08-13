@php
use App\Enums\RoleNames;
@endphp
<div class="modal fade" id="ajaxModel" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="modelHeading"></h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="pos_voucher_form" name="pos_voucher_form">
                <div class="modal-body">
                    <input type="hidden" name="voucher_id" id="voucher_id">
                    <div class="row">
                        @if (RoleNames::SUPERADMIN == getRoleName())
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Business <span class="text-danger">*</span></label>
                            <select id="business_id" name="business_id" class="form-select" required>
                                <option value="">--Select Business--</option>
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
                                Name <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="name" name="name" placeholder="Enter Name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                Code <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="code" name="code" placeholder="Enter Code" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">
                                Type <span class="text-danger">*</span>
                            </label>
                            <select id="type" name="type" class="form-select" required>
                                <option value="percent">Percent</option>
                                <option value="fixed">Fixed</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">
                                Value <span class="text-danger">*</span>
                            </label>
                            <input type="number" step="0.001" min="0" class="form-control" id="value" name="value" placeholder="Enter Value" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Status</label>
                            <select id="status" name="status" class="form-select">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Valid From</label>
                            <input type="date" class="form-control" id="valid_from" name="valid_from">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Valid To</label>
                            <input type="date" class="form-control" id="valid_to" name="valid_to">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Total Usage Limit</label>
                            <input type="number" min="0" class="form-control" id="usage_limit_total" name="usage_limit_total" placeholder="Unlimited">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Per Customer Usage Limit</label>
                            <input type="number" min="0" class="form-control" id="usage_limit_per_customer" name="usage_limit_per_customer" placeholder="Unlimited">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Min Order Amount</label>
                            <input type="number" step="0.001" min="0" class="form-control" id="min_order_amount" name="min_order_amount" placeholder="No minimum">
                        </div>

                        <div class="col-md-12"><hr><small class="text-muted">Leave a scope below empty to apply this voucher to all of that dimension.</small></div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Products</label>
                            <input type="hidden" name="product_ids[]" value="">
                            <select id="product_ids" name="product_ids[]" class="form-select select2-multiple" multiple>
                                @foreach ($products as $item)
                                <option value="{{ $item->product_id }}">{{ $item->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Categories</label>
                            <input type="hidden" name="category_ids[]" value="">
                            <select id="category_ids" name="category_ids[]" class="form-select select2-multiple" multiple>
                                @foreach ($categories as $item)
                                <option value="{{ $item->category_id }}">{{ $item->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Customers</label>
                            <input type="hidden" name="customer_ids[]" value="">
                            <select id="customer_ids" name="customer_ids[]" class="form-select select2-multiple" multiple>
                                @foreach ($customers as $item)
                                <option value="{{ $item->user_id }}">{{ $item->user->name ?? '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Order Types</label>
                            <input type="hidden" name="order_type_ids[]" value="">
                            <select id="order_type_ids" name="order_type_ids[]" class="form-select select2-multiple" multiple>
                                @foreach ($order_types as $item)
                                <option value="{{ $item->order_type_id }}">{{ $item->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Branches</label>
                            <input type="hidden" name="branch_ids[]" value="">
                            <select id="branch_ids" name="branch_ids[]" class="form-select select2-multiple" multiple>
                                @foreach ($branches as $item)
                                <option value="{{ $item->branch_id }}">{{ $item->name }}</option>
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
