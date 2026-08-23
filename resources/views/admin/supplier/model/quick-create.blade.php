@php
use App\Enums\RoleNames;
@endphp
{{-- Quick-add Supplier modal for use on foreign forms (e.g. Purchase create).
     Posts to the existing supplier.store route, which now returns JSON on
     AJAX requests while the full supplier/create.blade.php page keeps its
     normal redirect flow. Non-superadmin business_id/code fall back
     server-side (see SupplierController::store()), so no hidden field is
     needed here. --}}
<div class="modal fade" id="quickAddSupplierModal" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Supplier</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="quickAddSupplierForm" name="quickAddSupplierForm">
                <div class="modal-body">
                    <div class="row">
                        @if (RoleNames::SUPERADMIN == getRoleName())
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Business <span class="text-danger">*</span></label>
                            <select id="qa_supplier_business_id" name="business_id" class="form-select" required>
                                <option value="">--Select Business--</option>
                                @foreach ($business ?? [] as $item)
                                <option value="{{ $item->business_id }}">{{ $item->code ?? '' }} {{ $item->name ?? '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endif
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="qa_supplier_name" name="name" placeholder="Enter Name" required>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Company Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="qa_supplier_company_name" name="company_name" placeholder="Enter Company Name" required>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" class="form-control" id="qa_supplier_phone" name="phone" placeholder="Enter Phone">
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" id="qa_supplier_email" name="email" placeholder="Enter Email">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
