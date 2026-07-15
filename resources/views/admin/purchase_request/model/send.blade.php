@php
use App\Enums\RoleNames;
@endphp
<div class="modal fade" id="ajaxModel" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="modelHeading">Send Quotation</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="purchase_request_quotation_form" name="purchase_request_quotation_form" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="purchase_request_id" id="purchase_request_id">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">
                                Suppliers <span class="text-danger">**</span>
                            </label>
                            <select class="form-control" id="supplier_ids" name="supplier_ids[]" required multiple>
                                @foreach ($suppliers as $supplier)
                                    <option value="{{ $supplier->supplier_id }}">{{ $supplier->code }} - {{ $supplier->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <input type="checkbox" class="form-check-input" name="send_email" id="send_email" value="">
                            <label class="form-check-label" for="send_email">Send Email</label>
                        </div>
                        <div class="col-md-6 mb-3">
                            <input type="checkbox" class="form-check-input" name="send_whatsapp" id="send_whatsapp" value="">
                            <label class="form-check-label" for="send_whatsapp">Send Whatsapp</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        Close
                    </button>
                    <button type="submit" id="btnSendQuotation" class="btn btn-primary">
                        Send Quotation
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>