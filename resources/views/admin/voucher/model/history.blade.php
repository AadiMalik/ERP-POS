<div class="modal fade" id="voucherHistoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Voucher Usage History</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="voucherHistoryLoading" class="text-center py-4" style="display:none;">
                    <i class="fa fa-spinner fa-spin"></i> Loading…
                </div>
                <div id="voucherHistoryContent" style="display:none;">
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <div class="alert alert-info mb-0">
                                <strong id="vh_code">-</strong><br>
                                <span id="vh_name" class="text-muted"></span><br>
                                <small id="vh_rule"></small>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="alert alert-secondary mb-0"><strong>Total Uses</strong><br><span id="vh_total_uses">0</span></div>
                        </div>
                        <div class="col-md-3">
                            <div class="alert alert-secondary mb-0"><strong>Total Discount Given</strong><br><span id="vh_total_discount">0</span></div>
                        </div>
                        <div class="col-md-3">
                            <div class="alert alert-secondary mb-0"><strong>Unique Customers</strong><br><span id="vh_unique_customers">0</span></div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-striped" id="voucherHistoryTable">
                            <thead>
                                <tr>
                                    <th>Used At</th>
                                    <th>Customer</th>
                                    <th>Email</th>
                                    <th>Order #</th>
                                    <th>Status</th>
                                    <th>Discount</th>
                                </tr>
                            </thead>
                            <tbody id="voucherHistoryBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
