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
                        <div class="col-md-12 mb-3">
                            <label class="form-label">
                                Code <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="code" name="code" placeholder="Enter Code" required>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Branch <span class="text-danger">*</span></label>
                            <select id="branch_id" name="branch_id" class="form-select" required>
                                <option value="">--Select Branch--</option>
                                @foreach ($branches as $item)
                                <option value="{{ $item->branch_id }}">{{ $item->name ?? '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Warehouse <span class="text-danger">*</span></label>
                            <select id="warehouse_id" name="warehouse_id" class="form-select" required>
                                <option value="">--Select Warehouse--</option>
                                @foreach ($warehouses as $item)
                                <option value="{{ $item->warehouse_id }}">{{ $item->name ?? '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Mode <span class="text-danger">*</span></label>
                            <select id="mode" name="mode" class="form-select" required>
                                <option value="manual">Manual</option>
                                <option value="automatic">Automatic</option>
                            </select>
                        </div>
                        <div class="col-md-12 mb-3" id="assignedUserWrapper">
                            <label class="form-label">Assigned User</label>
                            <select id="assigned_user_id" name="assigned_user_id" class="form-select">
                                <option value="">--Select User--</option>
                                @foreach ($users as $item)
                                <option value="{{ $item->id }}">{{ $item->name ?? '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-12 mb-3">
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
