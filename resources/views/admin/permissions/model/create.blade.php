<div class="modal fade" id="ajaxModel" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="modelHeading"></h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="permission_form" name="permission_form" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="id" id="id">
                    <div class="row">
                        <div class="col-md-12">
                            <label for="name" class="form-label">Permission Name<span style="color:red;">*</span>:</label>
                            <input type="text" id="name" name="name" class="form-control"
                                placeholder="Enter Permission Name" value="" maxlength="40" required>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" id="toggleSwitch2"
                                    name="is_system_only">
                                <label class="form-check-label" for="toggleSwitch2">Is System Only</label>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Close</button>
                        <button type="submit" id="saveBtn" class="btn btn-primary" value="create">Save
                        </button>
                    </div>
            </form>
        </div>
    </div>
</div>
