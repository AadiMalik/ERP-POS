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
            <form id="category_form" name="category_form" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="category_id" id="category_id">
                    <div class="row">
                        @if (RoleNames::SUPERADMIN == getRoleName())
                        <div class="col-md-12">
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
                            <label class="form-label">Logo</label>
                            <input type="file" class="form-control" id="logo" name="logo" accept="image/*">
                            <div class="mt-2 text-center">
                                <img id="logo_preview" src="" style="max-height:120px;display:none;" class="img-thumbnail">
                            </div>
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