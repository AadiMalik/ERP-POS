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
            <form id="benefit_form" name="benefit_form">
                <div class="modal-body">
                    <input type="hidden" name="benefit_id" id="benefit_id">
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
                        <label class="form-label">Group <span class="text-danger">*</span></label>
                        <select id="group" name="group" class="form-select" required></select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="title" name="title" placeholder="Fast Delivery" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="2" placeholder="Same-day and 2-hour express slots across the city."></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Value <small class="text-muted">(price/duration display, if any)</small></label>
                            <input type="text" class="form-control" id="value" name="value" placeholder="$4.99 or Free">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Code <small class="text-muted">(matches an app option, if any)</small></label>
                            <input type="text" class="form-control" id="code" name="code" placeholder="standard / card / paypal...">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Icon <small class="text-muted">(FA class)</small></label>
                            <input type="text" class="form-control" id="icon" name="icon" placeholder="fa-solid fa-truck-fast">
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
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('common.close') }}</button>
                    <button type="submit" id="saveBtn" class="btn btn-primary">{{ __('common.save') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
