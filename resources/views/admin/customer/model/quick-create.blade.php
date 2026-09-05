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
                <h5 class="modal-title">{{ __('customers.add_new_customer') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="quickAddCustomerForm" name="quickAddCustomerForm">
                <div class="modal-body">
                    <div class="row">
                        @if (RoleNames::SUPERADMIN == getRoleName())
                        <div class="col-md-12 mb-3">
                            <label class="form-label">{{ __('common.business') }} <span class="text-danger">*</span></label>
                            <select id="qa_customer_business_id" name="business_id" class="form-select" required>
                                <option value="">{{ __('common.select_business') }}</option>
                                @foreach ($business ?? [] as $item)
                                <option value="{{ $item->business_id }}">{{ $item->code ?? '' }} {{ $item->name ?? '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        @else
                        <input type="hidden" name="business_id" value="{{ Auth::user()->business_id }}">
                        @endif
                        <div class="col-md-12 mb-3">
                            <label class="form-label">{{ __('common.name') }} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="qa_customer_name" name="name" placeholder="{{ __('common.enter_name') }}" required>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">{{ __('common.email') }} <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="qa_customer_email" name="email" placeholder="{{ __('customers.enter_email') }}" required>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">{{ __('common.phone') }}</label>
                            <input type="text" class="form-control" id="qa_customer_phone" name="phone" placeholder="{{ __('customers.enter_phone') }}">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('common.close') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('common.save') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@php
    $__i18nCustomers = [
        'enter_email' => __('customers.enter_email'),
        'enter_phone' => __('customers.enter_phone'),
        'add_new_customer' => __('customers.add_new_customer'),
    ];
@endphp
<script>
    window.i18n_customers = @json($__i18nCustomers);
</script>
