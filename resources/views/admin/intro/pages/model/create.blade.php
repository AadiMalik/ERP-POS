<div class="modal fade" id="ajaxModel" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="modelHeading"></h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="intro_page_form" name="intro_page_form" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="intro_page_id" id="intro_page_id">
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">Title <span class="text-danger">*</span></label><input type="text" class="form-control" id="title" name="title" required></div>
                        <div class="col-md-6 mb-3"><label class="form-label">Slug</label><input type="text" class="form-control" id="slug" name="slug"></div>
                    </div>
                    <div class="mb-3"><label class="form-label">Content</label><textarea class="form-control" id="content" name="content" rows="6"></textarea></div>
                    <div class="mb-3"><label class="form-label">Status</label><select class="form-select" id="status" name="status"><option value="published">Published</option><option value="draft">Draft</option></select></div>
                    <hr>
                    <div class="mb-3"><label class="form-label">SEO Title</label><input type="text" class="form-control" id="seo_title" name="seo_title"></div>
                    <div class="mb-3"><label class="form-label">Meta Description</label><textarea class="form-control" id="meta_description" name="meta_description" rows="2"></textarea></div>
                    <div class="mb-3"><label class="form-label">Meta Keywords</label><input type="text" class="form-control" id="meta_keywords" name="meta_keywords"></div>
                    <div class="mb-3"><label class="form-label">Canonical URL</label><input type="text" class="form-control" id="canonical_url" name="canonical_url"></div>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">OG Title</label><input type="text" class="form-control" id="og_title" name="og_title"></div>
                        <div class="col-md-6 mb-3"><label class="form-label">OG Image</label><input type="file" class="form-control" id="og_image" name="og_image" accept="image/*"><img id="og_image_preview" src="" style="max-height:60px;display:none;" class="img-thumbnail mt-2"></div>
                    </div>
                    <div class="mb-3"><label class="form-label">OG Description</label><textarea class="form-control" id="og_description" name="og_description" rows="2"></textarea></div>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">Robots Index</label><select class="form-select" id="robots_index" name="robots_index"><option value="1">Yes</option><option value="0">No</option></select></div>
                        <div class="col-md-6 mb-3"><label class="form-label">Robots Follow</label><select class="form-select" id="robots_follow" name="robots_follow"><option value="1">Yes</option><option value="0">No</option></select></div>
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