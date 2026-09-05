@php
    use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">{{ isset($service_sale) ? 'Update' : 'New' }} Service Sale</h4>
        <div class="card">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0">{{ isset($service_sale) ? 'Update' : 'Create' }} Service Sale</h5>
            </div>
            <div class="card-body">
                <form action="{{ url('admin/service-sale') }}" method="POST" id="serviceSaleForm">
                    @csrf
                    <input type="hidden" name="service_sale_id" value="{{ $service_sale->service_sale_id ?? '' }}">
                    {{-- ================= HEADER ================= --}}
                    <div class="row">
                        @if (!empty($business) && RoleNames::SUPERADMIN == getRoleName())
                            <div class="col-md-3 mb-3">
                                <label>Business <span class="text-danger">*</span></label>
                                <select class="form-control select2" name="business_id" id="business_id">
                                    <option value="">--Select Business--</option>
                                    @foreach ($business as $item)
                                        <option value="{{ $item->business_id }}"
                                            {{ old('business_id', $service_sale->business_id ?? '') == $item->business_id ? 'selected' : '' }}>
                                            {{ $item->code }} - {{ $item->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div class="col-md-3 mb-3">
                            <div class="d-flex align-items-center justify-content-between">
                                <label class="mb-0">Customer <span class="text-danger">*</span></label>
                                @include('admin.partials.quick-add-btn', ['permission' => 'customer.create', 'modal' => 'quickAddCustomerModal', 'label' => 'Customer'])
                            </div>
                            <select class="form-control select2" name="customer_id" id="customer_id">
                                <option value="">--Select Customer--</option>
                                @foreach ($customers as $item)
                                    <option value="{{ $item->user_id }}"
                                        {{ old('customer_id', $service_sale->customer_id ?? '') == $item->user_id ? 'selected' : '' }}>
                                        {{ $item->code }} - {{ $item->user->name ?? '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Service Sale No.</label>
                            <input type="text" class="form-control" name="service_sale_no" readonly
                                value="{{ $service_sale->service_sale_no ?? ($service_sale_no ?? 'Auto Generated') }}">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Sale Date <span class="text-danger">*</span></label>
                            <input type="text" class="form-control datepicker" name="service_sale_date"
                                value="{{ old('service_sale_date', isset($service_sale) ? localDate($service_sale->service_sale_date) : localDate(date('Y-m-d'))) }}">
                        </div>
                        <div class="col-md-12 mb-3">
                            <label>Description</label>
                            <textarea class="form-control" rows="2" name="description">{{ old('description', $service_sale->description ?? '') }}</textarea>
                        </div>
                    </div>
                    <hr>
                    {{-- ================= ITEM TABLE ================= --}}
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                Items
                            </h5>
                            <button type="button" class="btn btn-primary" id="addItemBtn">
                                <i class="fa fa-plus"></i> Add Item
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" id="itemTable">
                                <thead>
                                    <tr>
                                        <th style="min-width:200px;">Product (optional)</th>
                                        <th style="min-width:200px;">Item / Service Description</th>
                                        <th style="min-width:100px;">Qty</th>
                                        <th style="min-width:120px;">Unit Price</th>
                                        <th style="min-width:120px;">Subtotal</th>
                                        <th style="min-width:90px;">Disc %</th>
                                        <th style="min-width:110px;">Disc Amount</th>
                                        <th style="min-width:90px;">Tax %</th>
                                        <th style="min-width:110px;">Tax Amount</th>
                                        <th style="min-width:120px;">Total</th>
                                        <th style="min-width:50px;"></th>
                                    </tr>
                                </thead>
                                <tbody id="itemRows">
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <br>
                    {{-- ================= FOOTER TOTALS ================= --}}
                    <div class="row">
                        <div class="offset-md-6 col-md-6">
                            <table class="table table-bordered">
                                <tr>
                                    <th>Subtotal</th>
                                    <td>
                                        <input class="form-control" id="subtotal" name="subtotal" readonly>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Discount</th>
                                    <td>
                                        <input class="form-control" id="discount_amount" name="discount_amount"
                                            readonly>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Tax</th>
                                    <td>
                                        <input class="form-control" id="tax_amount" name="tax_amount" readonly>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Total</th>
                                    <td>
                                        <input class="form-control fw-bold" id="total" name="total" readonly>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    <input type="hidden" id="discount" name="discount" value="0">
                    <input type="hidden" id="tax" name="tax" value="0">
                    <div class="row">
                        <div class="col-md-12">
                            <button class="text-end btn btn-primary" id="submitBtn">
                                {{ isset($service_sale) ? '{{ __('service_sales.update_heading') }}' : 'Save Service Sale' }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @include('admin.customer.model.quick-create', ['business' => $business ?? []])
@endsection

@section('js')
    @if ($errors->any())
        <script>
            errorMessage("{{ $errors->first() }}");
        </script>
    @endif
    @if (session('error'))
        <script>
            errorMessage("{{ session('error') }}");
        </script>
    @endif
    <script>
        var itemIndex = 0;
        var productsData = [];
        @foreach ($products as $item)
            productsData.push({
                id: "{{ $item->product_id }}",
                name: @json($item->name)
            });
        @endforeach
        var isEditMode = {{ isset($service_sale) ? 'true' : 'false' }};
        var editItems = @json($service_sale_details['items'] ?? []);

        $(function() {
            if ($.fn.select2) {
                $('.select2').select2({
                    width: '100%'
                });
            }

            initQuickAdd({
                modalId: '#quickAddCustomerModal',
                formId: '#quickAddCustomerForm',
                url: url_local + '/admin/customer',
                valueField: 'id',
                labelField: 'name',
                targetSelectIds: ['customer_id'],
            });

            if (isEditMode && editItems.length) {
                $.each(editItems, function(_, item) {
                    addItemRow(item);
                });
            } else {
                addItemRow();
            }

            calculateGrandTotal();
        });

        $(document).on('click', '#addItemBtn', function() {
            addItemRow();
        });

        function productOptions(selectedId) {
            let html = '<option value="">--None / Free Text--</option>';
            $.each(productsData, function(_, p) {
                html += `<option value="${p.id}" ${p.id === selectedId ? 'selected' : ''}>${p.name}</option>`;
            });
            return html;
        }

        function addItemRow(item) {
            item = item || {};
            const index = itemIndex++;

            let row = $(`
                <tr class="item-row">
                    <td>
                        <select class="form-select product-select" name="items[${index}][product_id]">
                            ${productOptions(item.product_id ?? '')}
                        </select>
                    </td>
                    <td>
                        <input type="text" class="form-control item-name" name="items[${index}][item_name]"
                            value="${item.item_name ?? ''}" placeholder="e.g. Decoration Service">
                    </td>
                    <td>
                        <input type="text" class="form-control qty" name="items[${index}][quantity]"
                            value="${decimal(item.quantity ?? 1)}">
                    </td>
                    <td>
                        <input type="text" class="form-control unit-price" name="items[${index}][unit_price]"
                            value="${decimal(item.unit_price ?? 0)}">
                    </td>
                    <td>
                        <input type="text" class="form-control subtotal-cell" name="items[${index}][subtotal]"
                            value="${decimal(item.subtotal ?? 0)}" readonly>
                    </td>
                    <td>
                        <input type="text" class="form-control discount-percent" name="items[${index}][discount]"
                            value="${decimal(item.discount ?? 0)}">
                    </td>
                    <td>
                        <input type="text" class="form-control discount-amount-cell"
                            name="items[${index}][discount_amount]" value="${decimal(item.discount_amount ?? 0)}"
                            readonly>
                    </td>
                    <td>
                        <input type="text" class="form-control tax-percent" name="items[${index}][tax]"
                            value="${decimal(item.tax ?? 0)}">
                    </td>
                    <td>
                        <input type="text" class="form-control tax-amount-cell" name="items[${index}][tax_amount]"
                            value="${decimal(item.tax_amount ?? 0)}" readonly>
                    </td>
                    <td>
                        <input type="text" class="form-control total-cell" name="items[${index}][total]"
                            value="${decimal(item.total ?? 0)}" readonly>
                    </td>
                    <td>
                        <button type="button" class="btn btn-icon btn-outline-danger remove-item-row">
                            <i class="fa fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `);

            $('#itemRows').append(row);
            if ($.fn.select2) {
                row.find('.select2').select2({
                    width: '100%'
                });
            }
            calculateRow(row);
        }

        $(document).on('click', '.remove-item-row', function() {
            if ($('#itemRows tr.item-row').length <= 1) {
                errorMessage('At least one item is required.');
                return;
            }
            $(this).closest('tr').remove();
            calculateGrandTotal();
        });

        $(document).on('change', '.product-select', function() {
            let row = $(this).closest('tr');
            let selectedId = $(this).val();
            let nameField = row.find('.item-name');
            if (selectedId && !nameField.val()) {
                let product = productsData.find(p => p.id === selectedId);
                if (product) {
                    nameField.val(product.name);
                }
            }
        });

        $(document).on('keyup change blur', '.qty, .unit-price, .discount-percent, .tax-percent', function() {
            let row = $(this).closest('tr');
            calculateRow(row);
            calculateGrandTotal();
        });

        function calculateRow(row) {
            let qty = decimal(row.find('.qty').val() || 0);
            let unitPrice = decimal(row.find('.unit-price').val() || 0);
            let discountPercent = decimal(row.find('.discount-percent').val() || 0);
            let taxPercent = decimal(row.find('.tax-percent').val() || 0);

            let subtotal = qty * unitPrice;
            let discountAmount = round(subtotal * discountPercent / 100, 3);
            let taxable = subtotal - discountAmount;
            let taxAmount = round(taxable * taxPercent / 100, 3);
            let total = taxable + taxAmount;

            row.find('.subtotal-cell').val(decimal(subtotal));
            row.find('.discount-amount-cell').val(decimal(discountAmount));
            row.find('.tax-amount-cell').val(decimal(taxAmount));
            row.find('.total-cell').val(decimal(total));
        }

        function round(value, precision) {
            let factor = Math.pow(10, precision);
            return Math.round((value + Number.EPSILON) * factor) / factor;
        }

        function calculateGrandTotal() {
            let subtotal = 0,
                discount_amount = 0,
                tax_amount = 0,
                total = 0;

            $('#itemRows tr.item-row').each(function() {
                subtotal += decimal($(this).find('.subtotal-cell').val() || 0);
                discount_amount += decimal($(this).find('.discount-amount-cell').val() || 0);
                tax_amount += decimal($(this).find('.tax-amount-cell').val() || 0);
                total += decimal($(this).find('.total-cell').val() || 0);
            });

            $('#subtotal').val(decimal(subtotal));
            $('#discount_amount').val(decimal(discount_amount));
            $('#tax_amount').val(decimal(tax_amount));
            $('#total').val(decimal(total));
        }

        $('#serviceSaleForm').on('submit', function(e) {
            if (!$('#customer_id').val()) {
                e.preventDefault();
                errorMessage('Please select a customer.');
                return false;
            }
            if ($('#itemRows tr.item-row').length == 0) {
                e.preventDefault();
                errorMessage('Please add at least one item.');
                return false;
            }
            let valid = true;
            $('#itemRows tr.item-row').each(function() {
                if (!$(this).find('.item-name').val()) {
                    valid = false;
                }
            });
            if (!valid) {
                e.preventDefault();
                errorMessage('Please describe every item/service.');
                return false;
            }
        });
    </script>
@endsection
