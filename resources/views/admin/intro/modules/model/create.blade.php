<div class="modal fade" id="ajaxModel" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="modelHeading"></h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="intro_module_form" name="intro_module_form" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="intro_module_id" id="intro_module_id">
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">Name <span class="text-danger">*</span></label><input type="text" class="form-control" id="name" name="name" required></div>
                        <div class="col-md-6 mb-3"><label class="form-label">Slug</label><input type="text" class="form-control" id="slug" name="slug"></div>
                    </div>
                    <div class="mb-3"><label class="form-label">Description</label><textarea class="form-control" id="description" name="description" rows="3"></textarea></div>
                    <div class="row">
                        <div class="col-md-4 mb-3"><label class="form-label">Category</label><input type="text" class="form-control" id="category" name="category" placeholder="rail / sales / inventory"></div>
                        <div class="col-md-4 mb-3"><label class="form-label">Display Order</label><input type="number" class="form-control" id="display_order" name="display_order" value="0"></div>
                        <div class="col-md-4 mb-3"><label class="form-label">Featured</label><select class="form-select" id="is_featured" name="is_featured"><option value="0">No</option><option value="1">Yes</option></select></div>
                    </div>
                    <div class="mb-3"><label class="form-label">Status</label><select class="form-select" id="status" name="status"><option value="active">Active</option><option value="inactive">Inactive</option></select></div>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">Icon</label><input type="file" class="form-control" id="icon" name="icon" accept="image/*"><img id="icon_preview" src="" style="max-height:60px;display:none;" class="img-thumbnail mt-2"></div>
                        <div class="col-md-6 mb-3"><label class="form-label">Image</label><input type="file" class="form-control" id="image" name="image" accept="image/*"><img id="image_preview" src="" style="max-height:80px;display:none;" class="img-thumbnail mt-2"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('common.close') }}</button>
                    <button type="submit" id="saveBtn" class="btn btn-primary">{{ __('common.save') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>