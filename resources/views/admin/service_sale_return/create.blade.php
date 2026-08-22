@php
    use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">{{ isset($service_sale_return) ? 'Update' : 'New' }} Service Sale Return</h4>
        <div class="card">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0">{{ isset($service_sale_return) ? 'Update' : 'Create' }} Service Sale Return</h5>
            </div>
            <div class="card-body">
                <form action="{{ url('admin/service-sale-return') }}" method="POST" id="serviceSaleReturnForm">
                    @csrf
                    <input type="hidden" name="service_sale_return_id"
                        value="{{ $service_sale_return->service_sale_return_id ?? '' }}">
                    {{-- ================= HEADER ================= --}}
                    <div class="row">
                        @if (!empty($business) && RoleNames::SUPERADMIN == getRoleName())
                            <div class="col-md-3 mb-3">
                                <label>Business <span class="text-danger">*</span></label>
                                <select class="form-control select2" name="business_id" id="business_id">
                                    <option value="">--Select Business--</option>
                                    @foreach ($business as $item)
                                        <option value="{{ $item->business_id }}"
                                            {{ old('business_id', $service_sale_return->business_id ?? '') == $item->business_id ? 'selected' : '' }}>
                                            {{ $item->code }} - {{ $item->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div class="col-md-3 mb-3">
                            <label>
                                Service Sale<span class="text-danger">*</span>
                            </label>
                            <select class="form-control select2" name="service_sale_id" id="service_sale_id"
                                {{ isset($service_sale_return) ? 'disabled' : '' }}>
                                <option value="">--Select Service Sale--</option>
                                @foreach ($service_sales as $item)
                                    <option value="{{ $item->service_sale_id }}"
                                        {{ old('service_sale_id', $service_sale_return->service_sale_id ?? '') == $item->service_sale_id ? 'selected' : '' }}>
                                        {{ $item->service_sale_no }} - {{ $item->customer->name ?? '' }}
                                    </option>
                                @endforeach
                            </select>
                            @if (isset($service_sale_return))
                                <input type="hidden" name="service_sale_id"
                                    value="{{ $service_sale_return->service_sale_id }}">
                            @endif
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Customer</label>
                            <input type="text" class="form-control" id="customer_name" readonly
                                value="{{ $service_sale_return->customer->name ?? '' }}">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Return Number</label>
                            <input type="text" class="form-control" name="service_sale_return_no" readonly
                                value="{{ $service_sale_return->service_sale_return_no ?? ($service_sale_return_no ?? 'Auto Generated') }}">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Return Date</label>
                            <input type="text" class="form-control datepicker" name="service_sale_return_date"
                                value="{{ old('service_sale_return_date', isset($service_sale_return) ? localDate($service_sale_return->service_sale_return_date) : localDate(date('Y-m-d'))) }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Reason</label>
                            <input type="text" class="form-control" name="reason"
                                value="{{ old('reason', $service_sale_return->reason ?? '') }}">
                        </div>
                        <div class="col-md-12">
                            <label>Description</label>
                            <textarea class="form-control" rows="3" name="description">{{ old('description', $service_sale_return->description ?? '') }}</textarea>
                        </div>
                    </div>
                    <hr>
                    {{-- ================= ITEM TABLE ================= --}}
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                Items
                            </h5>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" id="itemTable">
                                <thead>
                                    <tr>
                                        <th style="min-width:220px;">Item / Service</th>
                                        <th style="min-width:100px;">Qty</th>
                                        <th style="min-width:120px;">Already Returned</th>
                                        <th style="min-width:110px;">Returnable</th>
                                        <th style="min-width:130px;">Return Qty</th>
                                        <th style="min-width:120px;">Unit Price</th>
                                        <th style="min-width:90px;">Discount %</th>
                                        <th style="min-width:90px;">Tax %</th>
                                        <th style="min-width:130px">Total</th>
                                    </tr>
                                </thead>
                                <tbody id="itemRows">
                                    <tr id="emptyRow">
                                        <td colspan="9" class="text-center text-muted">
                                            Select a Service Sale
                                        </td>
                                    </tr>
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
                                        <input class="form-control" id="subtotal" readonly>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Discount</th>
                                    <td>
                                        <input class="form-control" id="discount_amount" readonly>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Tax</th>
                                    <td>
                                        <input class="form-control" id="tax_amount" readonly>
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
                    <div class="row">
                        <div class="col-md-12">
                            <button class="text-end btn btn-primary" id="submitBtn">
                                {{ isset($service_sale_return) ? 'Update Service Sale Return' : 'Save Service Sale Return' }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
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
        var isEditMode = {{ isset($service_sale_return) ? 'true' : 'false' }};
        var editReturnData = @json($service_sale_return_details ?? null);

        $(function() {
            if ($.fn.select2) {
                $('.select2').select2({
                    width: '100%'
                });
            }

            if (isEditMode) {
                loadReturnForEdit();
            } else {
                let sourceId = $('#service_sale_id').val();
                if (sourceId) {
                    loadSourceLines(sourceId);
                }
            }
        });

        $(document).on('change', '#service_sale_id', function() {
            let service_sale_id = $(this).val();

            if (!service_sale_id) {
                resetItemRows();
                return;
            }

            loadSourceLines(service_sale_id);
        });

        function resetItemRows() {
            $('#customer_name').val('');
            $('#itemRows').html(`
                <tr id="emptyRow">
                    <td colspan="9" class="text-center text-muted">
                        Select a Service Sale
                    </td>
                </tr>
            `);
            calculateGrandTotal();
        }

        function loadSourceLines(service_sale_id) {
            $.ajax({
                url: url_local + '/admin/service-sale-return/source-lines/' + service_sale_id,
                type: 'GET',
                dataType: 'json',
                beforeSend: function() {
                    $('#itemRows').html(`
                        <tr>
                            <td colspan="9" class="text-center">
                                <div class="spinner-border spinner-border-sm"></div>
                                Loading...
                            </td>
                        </tr>
                    `);
                },
                success: function(response) {
                    if (!response.Success) {
                        errorMessage(response.Message);
                        return;
                    }
                    bindSourceHeader(response.Data.header);
                    bindItemRows(response.Data.lines);
                },
                error: function(error) {
                    errorMessage(error.responseJSON?.Message || 'Unable to load returnable lines.');
                }
            });
        }

        function bindSourceHeader(header) {
            if (!header) return;
            $('#customer_name').val(header.customer_name ?? '');
        }

        function bindItemRows(lines) {
            $('#itemRows').html('');

            if (!lines || !lines.length) {
                $('#itemRows').html(`
                    <tr id="emptyRow">
                        <td colspan="9" class="text-center text-muted">
                            Nothing remaining to return for this service sale.
                        </td>
                    </tr>
                `);
                calculateGrandTotal();
                return;
            }

            $.each(lines, function(_, line) {
                addItemRow(line);
            });

            calculateGrandTotal();
        }

        function addItemRow(line, return_quantity) {
            return_quantity = return_quantity ?? 0;

            let label = line.item_name || line.product_name || 'Item';

            let row = $(`
                <tr class="item-row">
                    <td>
                        <input type="hidden" name="items[][service_sale_detail_id]" value="${line.service_sale_detail_id}">
                        <input type="hidden" name="items[][reason]" value="">
                        ${label}
                    </td>
                    <td class="qty-cell">${decimal(line.quantity)}</td>
                    <td class="already-returned-qty">${decimal(line.already_returned_quantity)}</td>
                    <td class="returnable-qty">${decimal(line.returnable_quantity)}</td>
                    <td>
                        <input type="text" class="form-control return-qty" name="items[${$('#itemRows tr.item-row').length}][return_quantity]"
                            value="${decimal(return_quantity)}" data-returnable="${line.returnable_quantity}">
                    </td>
                    <td class="unit-price">${decimal(line.unit_price)}</td>
                    <td class="discount-percent">${decimal(line.discount)}</td>
                    <td class="tax-percent">${decimal(line.tax)}</td>
                    <td class="row-total">${decimal(0)}</td>
                </tr>
            `);

            row.data('unit_price', line.unit_price);
            row.data('discount_percent', line.discount || 0);
            row.data('tax_percent', line.tax || 0);
            row.find('input[name*="[service_sale_detail_id]"]').attr('name',
                `items[${$('#itemRows tr.item-row').length}][service_sale_detail_id]`);

            $('#itemRows').append(row);
            reindexRows();
            calculateRow(row);
        }

        function reindexRows() {
            $('#itemRows tr.item-row').each(function(index) {
                $(this).find('input[name*="[service_sale_detail_id]"]').attr('name',
                    `items[${index}][service_sale_detail_id]`);
                $(this).find('input.return-qty').attr('name', `items[${index}][return_quantity]`);
                $(this).find('input[name*="[reason]"]').attr('name', `items[${index}][reason]`);
            });
        }

        function calculateRow(row) {
            let returnable = decimal(row.find('.return-qty').data('returnable'));
            let qty = decimal(row.find('.return-qty').val());

            if (qty > returnable) {
                qty = returnable;
                row.find('.return-qty').val(decimal(qty));
            }
            if (qty < 0) {
                qty = 0;
                row.find('.return-qty').val(decimal(qty));
            }

            let unitPrice = decimal(row.data('unit_price'));
            let discountPercent = decimal(row.data('discount_percent'));
            let taxPercent = decimal(row.data('tax_percent'));

            let subtotal = qty * unitPrice;
            let discountAmount = round(subtotal * discountPercent / 100, 3);
            let taxable = subtotal - discountAmount;
            let taxAmount = round(taxable * taxPercent / 100, 3);
            let total = taxable + taxAmount;

            row.find('.row-total').html(decimal(total));
            row.data('subtotal', subtotal);
            row.data('discount_amount', discountAmount);
            row.data('tax_amount', taxAmount);
            row.data('total', total);
        }

        function round(value, precision) {
            let factor = Math.pow(10, precision);
            return Math.round((value + Number.EPSILON) * factor) / factor;
        }

        $(document).on('keyup change', '.return-qty', function() {
            let row = $(this).closest('tr');
            calculateRow(row);
            calculateGrandTotal();
        });

        $(document).on('blur', '.return-qty', function() {
            $(this).val(decimal($(this).val()));
        });

        function calculateGrandTotal() {
            let subtotal = 0;
            let discount_amount = 0;
            let tax_amount = 0;
            let total = 0;

            $('#itemRows tr.item-row').each(function() {
                subtotal += decimal($(this).data('subtotal'));
                discount_amount += decimal($(this).data('discount_amount'));
                tax_amount += decimal($(this).data('tax_amount'));
                total += decimal($(this).data('total'));
            });

            $('#subtotal').val(decimal(subtotal));
            $('#discount_amount').val(decimal(discount_amount));
            $('#tax_amount').val(decimal(tax_amount));
            $('#total').val(decimal(total));
        }

        function loadReturnForEdit() {
            if (!editReturnData || !editReturnData.items || !editReturnData.items.length) {
                return;
            }

            $('#itemRows').html('');

            $.each(editReturnData.items, function(_, item) {
                addItemRow({
                    service_sale_detail_id: item.service_sale_detail_id,
                    item_name: item.item_name,
                    product_name: item.product_name,
                    quantity: item.quantity,
                    already_returned_quantity: item.already_returned_quantity,
                    returnable_quantity: (decimal(item.quantity) - decimal(item.already_returned_quantity)) +
                        decimal(item.return_quantity),
                    unit_price: item.unit_price,
                    discount: item.discount,
                    tax: item.tax
                }, item.return_quantity);
            });

            calculateGrandTotal();
        }

        $('#serviceSaleReturnForm').on('submit', function(e) {
            if ($('#itemRows tr.item-row').length == 0) {
                e.preventDefault();
                errorMessage('Please select a service sale with returnable items.');
                return false;
            }

            let hasQuantity = false;
            $('#itemRows .return-qty').each(function() {
                if (decimal($(this).val()) > 0) {
                    hasQuantity = true;
                }
            });

            if (!hasQuantity) {
                e.preventDefault();
                errorMessage('Please enter a return quantity for at least one item.');
                return false;
            }
        });
    </script>
@endsection
