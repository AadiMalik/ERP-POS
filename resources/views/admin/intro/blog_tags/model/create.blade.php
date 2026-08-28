<div class="modal fade" id="ajaxModel" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="modelHeading"></h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="intro_blog_tag_form" name="intro_blog_tag_form">
                <div class="modal-body">
                    <input type="hidden" name="intro_blog_tag_id" id="intro_blog_tag_id">
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">Name <span class="text-danger">*</span></label><input type="text" class="form-control" id="name" name="name" required></div>
                        <div class="col-md-6 mb-3"><label class="form-label">Slug</label><input type="text" class="form-control" id="slug" name="slug"></div>
                    </div>
                    <div class="mb-3"><label class="form-label">Status</label><select class="form-select" id="status" name="status"><option value="active">Active</option><option value="inactive">Inactive</option></select></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" id="saveBtn" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>