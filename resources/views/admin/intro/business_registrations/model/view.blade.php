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

                <hr>
                <h6 class="mb-2">Payment Details</h6>
                <div id="reg_payment_empty" class="text-muted mb-3 d-none">No payment record found.</div>
                <div id="reg_payment_box" class="border rounded p-3 mb-3">
                    <div class="row mb-1"><div class="col-4 text-muted">Invoice</div><div class="col-8" id="reg_invoice_no"></div></div>
                    <div class="row mb-1"><div class="col-4 text-muted">Amount</div><div class="col-8" id="reg_payment_amount"></div></div>
                    <div class="row mb-1"><div class="col-4 text-muted">Method</div><div class="col-8" id="reg_payment_method"></div></div>
                    <div class="row mb-1"><div class="col-4 text-muted" id="reg_payment_reference_label">Bank Reference No</div><div class="col-8 fw-semibold" id="reg_payment_reference"></div></div>
                    <div class="row mb-1"><div class="col-4 text-muted">Status</div><div class="col-8" id="reg_payment_status"></div></div>
                    <div class="row mb-2" id="reg_payment_proof_row">
                        <div class="col-4 text-muted" id="reg_payment_proof_label">Bank Receipt</div>
                        <div class="col-8">
                            <span id="reg_payment_proof_missing" class="text-muted">No receipt uploaded</span>
                            <a href="#" target="_blank" id="reg_payment_proof" class="btn btn-sm btn-outline-primary d-none"><i class="fa fa-file"></i> View Receipt</a>
                            <div id="reg_payment_proof_preview" class="mt-2 d-none"></div>
                        </div>
                    </div>
                    <div class="mt-3" id="reg_payment_actions">
                        <button type="button" id="btnApprovePayment" class="btn btn-success btn-sm">Confirm Payment</button>
                        <button type="button" id="btnRejectPayment" class="btn btn-outline-danger btn-sm">Reject Payment</button>
                        <a href="#" id="reg_open_invoice" class="btn btn-outline-primary btn-sm" target="_blank">Open Invoice</a>
                    </div>
                    <p class="small text-muted mt-2 mb-0 d-none" id="reg_payment_locked"></p>
                </div>

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
