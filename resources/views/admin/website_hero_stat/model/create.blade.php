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
            <form id="hero_stat_form" name="hero_stat_form">
                <div class="modal-body">
                    <input type="hidden" name="hero_stat_id" id="hero_stat_id">
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
                        <label class="form-label">Value <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="value" name="value" placeholder="12k+, 500+, 2 hrs..." required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Label <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="label" name="label" placeholder="Happy Customers" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Icon <small class="text-muted">(FA class, optional)</small></label>
                            <input type="text" class="form-control" id="icon" name="icon" placeholder="fa-solid fa-users">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Icon Color</label>
                            <input type="color" class="form-control form-control-color" id="icon_color" name="icon_color" value="#666666">
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
