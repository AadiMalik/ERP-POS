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
                    <textarea id="reply_message" class="form-control" rows="4"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" id="btnUpdateStatus" class="btn btn-outline-primary">Update Status</button>
                <button type="button" id="btnSendReply" class="btn btn-primary">Send Reply</button>
            </div>
        </div>
    </div>
</div>