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
            <form id="account_type_form" name="account_type_form" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="account_type_id" id="account_type_id">
                    <div class="row">
                        @if (RoleNames::SUPERADMIN == getRoleName())
                            <div class="col-md-12">
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
                                Code <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="code" name="code"
                                placeholder="Enter Code" Required>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">
                                Name <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="name" name="name"
                                placeholder="Enter Name" disabled>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">
                                Description <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control" id="description" name="description" placeholder="Enter Description" disabled></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        Close
                    </button>
                    <button type="submit" id="saveBtn" class="btn btn-primary">
                        Update
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>
