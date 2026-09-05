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
                <h5 class="modal-title">{{ __('suppliers.add_new_supplier') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="quickAddSupplierForm" name="quickAddSupplierForm">
                <div class="modal-body">
                    <div class="row">
                        @if (RoleNames::SUPERADMIN == getRoleName())
                        <div class="col-md-12 mb-3">
                            <label class="form-label">{{ __('common.business') }} <span class="text-danger">*</span></label>
                            <select id="qa_supplier_business_id" name="business_id" class="form-select" required>
                                <option value="">{{ __('common.select_business') }}</option>
                                @foreach ($business ?? [] as $item)
                                <option value="{{ $item->business_id }}">{{ $item->code ?? '' }} {{ $item->name ?? '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endif
                        <div class="col-md-12 mb-3">
                            <label class="form-label">{{ __('common.name') }} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="qa_supplier_name" name="name" placeholder="{{ __('common.enter_name') }}" required>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">{{ __('suppliers.company_name') }} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="qa_supplier_company_name" name="company_name" placeholder="{{ __('suppliers.enter_company_name') }}" required>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">{{ __('common.phone') }}</label>
                            <input type="text" class="form-control" id="qa_supplier_phone" name="phone" placeholder="{{ __('suppliers.enter_phone') }}">
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">{{ __('common.email') }}</label>
                            <input type="email" class="form-control" id="qa_supplier_email" name="email" placeholder="{{ __('suppliers.enter_email') }}">
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
    $__i18nSuppliersQuick = [
        'add_new_supplier' => __('suppliers.add_new_supplier'),
        'enter_company_name' => __('suppliers.enter_company_name'),
        'enter_email' => __('suppliers.enter_email'),
        'enter_phone' => __('suppliers.enter_phone'),
    ];
@endphp
<script>
    window.i18n_suppliers = Object.assign(window.i18n_suppliers || {}, @json($__i18nSuppliersQuick));
</script>
