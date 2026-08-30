<div class="modal fade" id="ajaxModel" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="modelHeading">Inquiry</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="intro_contact_inquiry_id">
                <div class="mb-2"><strong>From:</strong> <span id="inq_name"></span> &lt;<span id="inq_email"></span>&gt;</div>
                <div class="mb-2"><strong>Phone:</strong> <span id="inq_phone"></span></div>
                <div class="mb-2"><strong>Subject:</strong> <span id="inq_subject"></span></div>
                <div class="mb-3 p-3 border rounded bg-light" id="inq_message" style="white-space:pre-wrap"></div>
                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select id="inq_status" class="form-select">
                        <option value="new">New</option>
                        <option value="read">Read</option>
                        <option value="replied">Replied</option>
                        <option value="closed">Closed</option>
                    </select>
                </div>
                <div id="inq_replies" class="mb-3"></div>
                <div class="mb-3">
                    <label class="form-label">Reply</label>
                    <textarea id="reply_message" class="form-control" rows="3"></textarea>
                </div>

                <hr>
                <h6 class="mb-3">Register Business</h6>
                <div id="inq_linked_business" class="alert alert-info d-none mb-3"></div>
                <div id="register_business_form">
                    <div class="row g-2 mb-2">
                        <div class="col-md-6">
                            <label class="form-label">Business Name</label>
                            <input type="text" id="reg_business_name" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Package</label>
                            <select id="reg_package_id" class="form-select">
                                <option value="">Select package</option>
                                @foreach ($packages ?? [] as $package)
                                    <option value="{{ $package->package_id }}">
                                        {{ $package->name }} ({{ ucfirst($package->duration_type) }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Payment Method</label>
                            <select id="reg_payment_method" class="form-select">
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="cash">Cash</option>
                                <option value="cheque">Cheque</option>
                                <option value="online">Online</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Payment Reference</label>
                            <input type="text" id="reg_payment_reference" class="form-control">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="reg_activate">
                                <label class="form-check-label" for="reg_activate">Activate now (confirm payment)</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="inq_payment_actions" class="d-none mb-2">
                    <button type="button" id="btnUpdatePayment" class="btn btn-outline-secondary btn-sm">Update Payment</button>
                    <button type="button" id="btnActivateBusiness" class="btn btn-success btn-sm">Confirm Payment &amp; Activate</button>
                    <a href="#" id="btnViewInvoice" class="btn btn-outline-primary btn-sm" target="_blank">Open Invoice</a>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" id="btnUpdateStatus" class="btn btn-outline-primary">Update Status</button>
                <button type="button" id="btnSendReply" class="btn btn-primary">Send Reply</button>
                <button type="button" id="btnRegisterBusiness" class="btn btn-success">Register Business</button>
            </div>
        </div>
    </div>
</div>
