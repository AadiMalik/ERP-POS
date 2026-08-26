<div class="modal fade" id="ajaxModel" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="modelHeading"></h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="website_page_form" name="website_page_form" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="page_id" id="page_id">
                    <div class="mb-3">
                        <label class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="title" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Content</label>
                        <textarea class="form-control" id="content" name="content" rows="10"></textarea>
                    </div>
                    <hr>
                    <h6>Page SEO</h6>
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">SEO Title</label>
                            <input type="text" class="form-control" id="seo_title" name="seo_title">
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">SEO Description</label>
                            <textarea class="form-control" id="seo_description" name="seo_description" rows="2"></textarea>
                        </div>
                        <div class="col-md-8 mb-3">
                            <label class="form-label">SEO Keywords</label>
                            <input type="text" class="form-control" id="seo_keywords" name="seo_keywords">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">OG Image</label>
                            <input type="file" class="form-control" id="og_image" name="og_image" accept="image/*">
                            <img id="og_image_preview" src="" style="max-height:80px;display:none;" class="img-thumbnail mt-2">
                        </div>
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
