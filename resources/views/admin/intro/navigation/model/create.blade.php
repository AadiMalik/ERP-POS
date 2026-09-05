<div class="modal fade" id="ajaxModel" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="modelHeading"></h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="intro_navigation_form" name="intro_navigation_form">
                <div class="modal-body">
                    <input type="hidden" name="intro_navigation_item_id" id="intro_navigation_item_id">
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">Label <span class="text-danger">*</span></label><input type="text" class="form-control" id="label" name="label" required></div>
                        <div class="col-md-6 mb-3"><label class="form-label">URL</label><input type="text" class="form-control" id="url" name="url" placeholder="/pricing"></div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3"><label class="form-label">Location</label><select class="form-select" id="location" name="location"><option value="header">Header</option><option value="footer">Footer</option><option value="deck">Deck</option></select></div>
                        <div class="col-md-4 mb-3"><label class="form-label">Section Key</label><input type="text" class="form-control" id="section_key" name="section_key"></div>
                        <div class="col-md-4 mb-3"><label class="form-label">Match Key</label><input type="text" class="form-control" id="match_key" name="match_key"></div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3"><label class="form-label">Parent ID</label><input type="text" class="form-control" id="parent_id" name="parent_id"></div>
                        <div class="col-md-4 mb-3"><label class="form-label">Display Order</label><input type="number" class="form-control" id="display_order" name="display_order" value="0"></div>
                        <div class="col-md-4 mb-3"><label class="form-label">Status</label><select class="form-select" id="status" name="status"><option value="active">Active</option><option value="inactive">Inactive</option></select></div>
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