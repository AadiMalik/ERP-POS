<div class="modal fade" id="viewJvModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Journal Voucher') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="viewJvLoading" class="text-center text-muted py-4">Loading...</div>
                <div id="viewJvContent" style="display:none;">
                    <div class="row g-2 mb-3">
                        <div class="col-md-3"><strong>Voucher No:</strong> <span id="jv_entry_no"></span></div>
                        <div class="col-md-3"><strong>Type:</strong> <span id="jv_journal_short"></span></div>
                        <div class="col-md-3"><strong>Date:</strong> <span id="jv_entry_date"></span></div>
                        <div class="col-md-3"><strong>Status:</strong> <span id="jv_status" class="text-capitalize"></span></div>
                        <div class="col-md-6"><strong>Reference No:</strong> <span id="jv_reference_no"></span></div>
                        <div class="col-md-6"><strong>Source:</strong> <span id="jv_source_type"></span></div>
                        <div class="col-md-12"><strong>Narration:</strong> <span id="jv_description"></span></div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead>
                                <tr>
                                    <th>Account</th>
                                    <th>Description</th>
                                    <th class="text-end">Debit</th>
                                    <th class="text-end">Credit</th>
                                </tr>
                            </thead>
                            <tbody id="jv_lines_body"></tbody>
                            <tfoot>
                                <tr class="fw-bold">
                                    <td colspan="2" class="text-end">Total</td>
                                    <td class="text-end" id="jv_total_debit"></td>
                                    <td class="text-end" id="jv_total_credit"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                <div id="viewJvError" class="alert alert-danger" style="display:none;"></div>
            </div>
        </div>
    </div>
</div>
