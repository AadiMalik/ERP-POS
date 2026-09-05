<div class="modal fade" id="ajaxModel" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="modelHeading"></h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="intro_testimonial_form" name="intro_testimonial_form" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="intro_testimonial_id" id="intro_testimonial_id">
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">Customer Name <span class="text-danger">*</span></label><input type="text" class="form-control" id="customer_name" name="customer_name" required></div>
                        <div class="col-md-6 mb-3"><label class="form-label">Designation</label><input type="text" class="form-control" id="designation" name="designation"></div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">Business Name</label><input type="text" class="form-control" id="business_name" name="business_name"></div>
                        <div class="col-md-6 mb-3"><label class="form-label">Business Type</label><input type="text" class="form-control" id="business_type" name="business_type" placeholder="Retail / Mart / Wholesale"></div>
                    </div>
                    <div class="mb-3"><label class="form-label">Review <span class="text-danger">*</span></label><textarea class="form-control" id="review_text" name="review_text" rows="4" required></textarea></div>
                    <div class="row">
                        <div class="col-md-4 mb-3"><label class="form-label">Rating</label><input type="number" min="1" max="5" class="form-control" id="rating" name="rating" value="5"></div>
                        <div class="col-md-4 mb-3"><label class="form-label">Display Order</label><input type="number" class="form-control" id="display_order" name="display_order" value="0"></div>
                        <div class="col-md-4 mb-3"><label class="form-label">Status</label><select class="form-select" id="status" name="status"><option value="active">Active</option><option value="inactive">Inactive</option></select></div>
                    </div>
                    <div class="mb-3"><label class="form-label">Image</label><input type="file" class="form-control" id="image" name="image" accept="image/*"><img id="image_preview" src="" style="max-height:80px;display:none;" class="img-thumbnail mt-2"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('common.close') }}</button>
                    <button type="submit" id="saveBtn" class="btn btn-primary">{{ __('common.save') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>