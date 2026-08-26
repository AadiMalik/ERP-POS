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
            <form id="website_faq_form" name="website_faq_form">
                <div class="modal-body">
                    <input type="hidden" name="faq_id" id="faq_id">
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
                        <label class="form-label">Question <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="question" name="question" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Answer</label>
                        <textarea class="form-control" id="answer" name="answer" rows="4"></textarea>
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
