<div class="modal fade" id="stockConsumptionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Stock Consumption Details') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="stockConsumptionLoading" class="text-center text-muted py-4">Loading...</div>
                <div class="table-responsive" id="stockConsumptionTableWrap" style="display:none;">
                    <table class="table table-sm table-bordered">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Warehouse</th>
                                <th>Product</th>
                                <th>Variation</th>
                                <th>Batch</th>
                                <th>Qty</th>
                                <th>Unit</th>
                                <th>Conversion Factor</th>
                                <th>Base Qty</th>
                                <th>Unit Price</th>
                                <th>Total</th>
                                <th>Stock Txn Ref</th>
                            </tr>
                        </thead>
                        <tbody id="stockConsumptionBody"></tbody>
                    </table>
                </div>
                <div id="stockConsumptionError" class="alert alert-danger" style="display:none;"></div>
            </div>
        </div>
    </div>
</div>
