@php
    use App\Enums\RoleNames;
@endphp
<div class="modal fade" id="ajaxModel" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="modelHeading"></h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="product_variation_batch_form" name="product_variation_batch_form" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="product_variation_batch_id" id="product_variation_batch_id">
                    <div class="row">
                        @if (RoleNames::SUPERADMIN == getRoleName())
                            <div class="col-md-6">
                                <label class="form-label">Business <span class="text-danger">*</span></label>
                                <select id="business_id" name="business_id" class="form-select" required>
                                    <option value="">--Select Business--</option>
                                    @foreach ($business as $item)
                                        <option value="{{ $item->business_id }}">
                                            {{ isset($item->code) ? $item->code : '' }}
                                            {{ $item->name ?? '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div class="col-md-6">
                            <label class="form-label">Product <span class="text-danger">*</span></label>
                            <select id="product_id" name="product_id" class="form-select" required>
                                <option value="">--Select Product--</option>
                                @if (RoleNames::SUPERADMIN != getRoleName())
                                    @foreach ($products as $item)
                                        <option value="{{ $item->product_id }}">
                                            {{ $item->name ?? '' }}
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Product Variation <span class="text-danger">*</span></label>
                            <select id="product_variation_id" name="product_variation_id" class="form-select" required>
                                <option value="">--Select Product Variation--</option>

                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Warehouse <span class="text-danger">*</span></label>
                            <select id="warehouse_id" name="warehouse_id" class="form-select" required>
                                <option value="">--Select Warehouse--</option>
                                @foreach ($warehouse as $item)
                                    <option value="{{ $item->warehouse_id }}">
                                        {{ $item->name ?? '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Avg Cost <span class="text-danger">*</span>
                            </label>
                            <input type="number" class="form-control" id="avg_price" name="avg_price"
                                placeholder="Enter Cost" step="0.01" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Quantity <span class="text-danger">*</span>
                            </label>
                            <input type="number" class="form-control" id="quantity" name="quantity"
                                placeholder="Enter Quantity" step="0.01" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Manufacturing Date <span class="text-danger">*</span>
                            </label>
                            <input type="date" class="form-control" id="manufacturing_date" name="manufacturing_date"
                                placeholder="Enter Manufacturing Date" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Expiry Date <span class="text-danger">*</span>
                            </label>
                            <input type="date" class="form-control" id="expiry_date" name="expiry_date"
                                placeholder="Enter Expiry Date" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        Close
                    </button>
                    <button type="submit" id="saveBtn" class="btn btn-primary">
                        Save
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>
