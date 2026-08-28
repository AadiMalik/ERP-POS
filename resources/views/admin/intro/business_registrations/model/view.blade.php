<div class="modal fade" id="ajaxModel" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Business Registration</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="intro_business_registration_id">
                <div class="row mb-2"><div class="col-4 text-muted">Business</div><div class="col-8" id="reg_business_name"></div></div>
                <div class="row mb-2"><div class="col-4 text-muted">Owner</div><div class="col-8"><span id="reg_owner_name"></span> · <span id="reg_owner_email"></span> · <span id="reg_owner_phone"></span></div></div>
                <div class="row mb-2"><div class="col-4 text-muted">Type / City</div><div class="col-8"><span id="reg_business_type"></span> · <span id="reg_city"></span></div></div>
                <div class="row mb-2"><div class="col-4 text-muted">Package</div><div class="col-8"><span id="reg_package"></span> (<span id="reg_cycle"></span>)</div></div>
                <div class="row mb-2"><div class="col-4 text-muted">Subscription</div><div class="col-8" id="reg_sub_status"></div></div>
                <div class="row mb-3"><div class="col-4 text-muted">Notes</div><div class="col-8" id="reg_notes" style="white-space:pre-wrap"></div></div>
                <div class="mb-3">
                    <label class="form-label">Registration Status</label>
                    <select id="reg_status" class="form-select">
                        <option value="pending">Pending</option>
                        <option value="under_review">Under Review</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                        <option value="activated">Activated</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" id="btnRegStatus" class="btn btn-primary">Update Status</button>
            </div>
        </div>
    </div>
</div>