<div class="modal fade" id="ajaxModel" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Message from <span id="view_name"></span></h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="contact_message_id">
                <div class="row mb-3">
                    <div class="col-md-4"><strong>Email:</strong> <span id="view_email"></span></div>
                    <div class="col-md-4"><strong>Phone:</strong> <span id="view_phone">-</span></div>
                    <div class="col-md-4"><strong>Received:</strong> <span id="view_date"></span></div>
                </div>
                <div class="mb-3">
                    <strong>Subject:</strong> <span id="view_subject"></span>
                </div>
                <div class="card mb-3">
                    <div class="card-body bg-light" id="view_message"></div>
                </div>

                <div id="existing_reply_wrap" style="display:none;">
                    <h6>Reply Sent</h6>
                    <div class="card mb-3">
                        <div class="card-body" id="view_reply"></div>
                    </div>
                </div>

                <div id="reply_form_wrap">
                    <h6>Reply</h6>
                    <textarea class="form-control" id="reply_message" rows="5" placeholder="Type your reply..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" id="sendReplyBtn" class="btn btn-primary">Send Reply</button>
            </div>
        </div>
    </div>
</div>
