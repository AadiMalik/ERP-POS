{{-- Generic Import modal + preview UI, shared by every CRUD module. Include
     once per index page alongside admin.partials.import-export-buttons. --}}
<div class="modal fade" id="importExportModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="importExportModalLabel">Import</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">

                {{-- Step 1: Upload --}}
                <div id="importStepUpload">
                    <div class="alert alert-info">
                        <strong>How it works:</strong> download the sample file below, fill it in following the
                        instructions on its second sheet, then upload it here. Nothing is saved until you review the
                        preview and click <strong>Confirm Import</strong>.
                    </div>
                    <div class="mb-3">
                        <a href="javascript:void(0)" id="importDownloadSample" class="btn btn-outline-secondary">
                            <i class="fa fa-file-excel mr-5"></i>Download Sample Excel File
                        </a>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Upload Excel File (.xlsx, .xls)</label>
                        <input type="file" id="importFileInput" class="form-control" accept=".xlsx,.xls">
                    </div>
                    <div id="importUploadError" class="alert alert-danger" style="display:none;"></div>
                </div>

                {{-- Step 2: Preview --}}
                <div id="importStepPreview" style="display:none;">
                    <div class="row text-center mb-3" id="importSummaryBadges"></div>

                    <ul class="nav nav-tabs" id="importPreviewTabs">
                        <li class="nav-item">
                            <a class="nav-link active" data-bs-toggle="tab" href="#importValidRowsTab">Valid Rows</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#importInvalidRowsTab">Invalid Rows</a>
                        </li>
                    </ul>
                    <div class="tab-content border border-top-0 p-2">
                        <div class="tab-pane fade show active" id="importValidRowsTab">
                            <div class="table-responsive">
                                <table class="table table-sm table-striped">
                                    <thead>
                                        <tr>
                                            <th style="width:40px;"></th>
                                            <th>Row</th>
                                            <th>Action</th>
                                            <th>Summary</th>
                                        </tr>
                                    </thead>
                                    <tbody id="importValidRowsBody"></tbody>
                                </table>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="importInvalidRowsTab">
                            <div class="table-responsive">
                                <table class="table table-sm table-striped">
                                    <thead>
                                        <tr>
                                            <th>Row</th>
                                            <th>Column</th>
                                            <th>Value</th>
                                            <th>Reason</th>
                                        </tr>
                                    </thead>
                                    <tbody id="importInvalidRowsBody"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="importConfirmResult" style="display:none;" class="alert alert-success mt-3"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="importUploadBtn" class="btn btn-primary">Upload &amp; Preview</button>
                <button type="button" id="importConfirmBtn" class="btn btn-success" style="display:none;">Confirm
                    Import</button>
                <button type="button" id="importAnotherBtn" class="btn btn-outline-primary"
                    style="display:none;">Import Another File</button>
            </div>
        </div>
    </div>
</div>
{{-- import-export.js is loaded globally in layouts/js.blade.php (after jQuery),
     not here - this partial is rendered inside @yield('content'), which runs
     before jQuery loads, so an inline <script> tag here would fail. --}}
