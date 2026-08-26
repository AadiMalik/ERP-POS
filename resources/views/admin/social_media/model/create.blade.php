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
            <form id="social_media_form" name="social_media_form">
                <div class="modal-body">
                    <input type="hidden" name="social_media_link_id" id="social_media_link_id">
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
                    <div class="mb-3">
                        <label class="form-label">Platform <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="platform" name="platform" placeholder="Facebook, Instagram, WhatsApp..." required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">URL <span class="text-danger">*</span></label>
                        <input type="url" class="form-control" id="url" name="url" placeholder="https://..." required>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Icon <small class="text-muted">(FA class)</small></label>
                            <input type="text" class="form-control" id="icon" name="icon" placeholder="fab fa-facebook">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Icon Color</label>
                            <input type="color" class="form-control form-control-color" id="icon_color" name="icon_color" value="#666666">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Display Color</label>
                            <input type="color" class="form-control form-control-color" id="display_color" name="display_color" value="#666666">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Sort Order</label>
                        <input type="number" class="form-control" id="sort_order" name="sort_order" value="0">
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
