@php
use App\Enums\RoleNames;
use Illuminate\Support\Facades\Auth;
@endphp
{{-- Quick-add Customer modal for use on foreign forms (e.g. Service Sale
     create). Posts to the existing customer.store route, which now returns
     JSON on AJAX requests while the full customer/create.blade.php page
     keeps its normal redirect flow. --}}
<div class="modal fade" id="quickAddCustomerModal" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Customer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="quickAddCustomerForm" name="quickAddCustomerForm">
                <div class="modal-body">
                    <div class="row">
                        @if (RoleNames::SUPERADMIN == getRoleName())
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Business <span class="text-danger">*</span></label>
                            <select id="qa_customer_business_id" name="business_id" class="form-select" required>
                                <option value="">--Select Business--</option>
                                @foreach ($business ?? [] as $item)
                                <option value="{{ $item->business_id }}">{{ $item->code ?? '' }} {{ $item->name ?? '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        @else
                        <input type="hidden" name="business_id" value="{{ Auth::user()->business_id }}">
                        @endif
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="qa_customer_name" name="name" placeholder="Enter Name" required>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="qa_customer_email" name="email" placeholder="Enter Email" required>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" class="form-control" id="qa_customer_phone" name="phone" placeholder="Enter Phone">
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
