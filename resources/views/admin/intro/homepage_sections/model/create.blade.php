<div class="modal fade" id="ajaxModel" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="modelHeading"></h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="intro_section_form" name="intro_section_form" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="intro_homepage_section_id" id="intro_homepage_section_id">
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">Section Key <span class="text-danger">*</span></label><input type="text" class="form-control" id="section_key" name="section_key" required placeholder="hero / ticker / faq"></div>
                        <div class="col-md-6 mb-3"><label class="form-label">Title</label><input type="text" class="form-control" id="title" name="title"></div>
                    </div>
                    <div class="mb-3"><label class="form-label">Subtitle</label><input type="text" class="form-control" id="subtitle" name="subtitle"></div>
                    <div class="mb-3"><label class="form-label">Content</label><textarea class="form-control" id="content" name="content" rows="3"></textarea></div>
                    <div class="mb-3"><label class="form-label">Content JSON</label><textarea class="form-control font-monospace" id="content_json" name="content_json" rows="5" placeholder='{"items":[]}'></textarea></div>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">Button Text</label><input type="text" class="form-control" id="button_text" name="button_text"></div>
                        <div class="col-md-6 mb-3"><label class="form-label">Button Link</label><input type="text" class="form-control" id="button_link" name="button_link"></div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3"><label class="form-label">Display Order</label><input type="number" class="form-control" id="display_order" name="display_order" value="0"></div>
                        <div class="col-md-4 mb-3"><label class="form-label">Enabled</label><select class="form-select" id="is_enabled" name="is_enabled"><option value="1">Yes</option><option value="0">No</option></select></div>
                        <div class="col-md-4 mb-3"><label class="form-label">Status</label><select class="form-select" id="status" name="status"><option value="active">Active</option><option value="inactive">Inactive</option></select></div>
                    </div>
                    <div class="mb-3"><label class="form-label">Image</label><input type="file" class="form-control" id="image" name="image" accept="image/*"><img id="image_preview" src="" style="max-height:80px;display:none;" class="img-thumbnail mt-2"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" id="saveBtn" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>