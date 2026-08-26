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
            <form id="testimonial_form" name="testimonial_form" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="testimonial_id" id="testimonial_id">
                    @if (RoleNames::SUPERADMIN == getRoleName())
                    <div class="mb-3">
                        <label class="form-label">Business <span class="text-danger">*</span></label>
                        <select id="business_id" name="business_id" class="form-select" required>
                            <option value="">--Select Business--</option>
                            @foreach ($business as $item)
                            <option value="{{ $item->business_id }}">{{ $item->name ?? '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    <div class="row">
                        <div class="col-md-7 mb-3">
                            <label class="form-label">Author Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="author_name" name="author_name" required>
                        </div>
                        <div class="col-md-5 mb-3">
                            <label class="form-label">Author Title <small class="text-muted">(role/location)</small></label>
                            <input type="text" class="form-control" id="author_title" name="author_title">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Quote <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="quote" name="quote" rows="3" required></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Avatar</label>
                            <input type="file" class="form-control" id="avatar" name="avatar" accept="image/*">
                            <img id="avatar_preview" src="" style="max-height:80px;display:none;" class="img-thumbnail mt-2">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Rating <small class="text-muted">(1-5)</small></label>
                            <input type="number" min="1" max="5" class="form-control" id="rating" name="rating">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Sort Order</label>
                            <input type="number" class="form-control" id="sort_order" name="sort_order" value="0">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" id="saveBtn" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
