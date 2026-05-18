<div class="modal fade" id="ajaxModel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="modelHeading"></h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="permission_form" name="permission_form" class="form-horizontal" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="id" id="id">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="input-style-1">
                                <label for="name">Permission Name:<span style="color:red;">*</span></label>
                                <input type="text" class="form-control" id="name" name="name"
                                    placeholder="Enter Permission Name" value="" maxlength="40" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch toggle-switch">
                                <input class="form-check-input" type="checkbox" id="toggleSwitch2">
                                <label class="form-check-label" for="toggleSwitch2">Is System Only</label>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer mt-2">
                        <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Close</button>
                        <button type="submit" id="saveBtn" class="btn btn-primary" value="create">Save
                        </button>
                    </div>
            </form>
        </div>
    </div>
</div>
