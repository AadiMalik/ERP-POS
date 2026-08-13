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
                        <div class="col-md-12 mb-3">
                            <label class="form-label">
                                Name <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="name" name="name" placeholder="Enter Name" required>
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
