<div class="modal fade" id="ajaxModel" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="modelHeading">{{ __('Upload Media') }}</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="intro_media_form" name="intro_media_form" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">File <span class="text-danger">*</span></label><input type="file" class="form-control" id="file" name="file" required></div>
                    <div class="mb-3"><label class="form-label">Collection</label><input type="text" class="form-control" id="collection" name="collection" value="general"></div>
                    <div class="mb-3"><label class="form-label">Alt Text</label><input type="text" class="form-control" id="alt_text" name="alt_text"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('common.close') }}</button>
                    <button type="submit" id="saveBtn" class="btn btn-primary">Upload</button>
                </div>
            </form>
        </div>
    </div>
</div>