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
            <form id="product_variation_unit_conversion_form" name="product_variation_unit_conversion_form"
                enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="product_variation_unit_conversion_id"
                        id="product_variation_unit_conversion_id">
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
                            <label class="form-label">From Unit <span class="text-danger">*</span></label>
                            <select id="from_unit_id" name="from_unit_id" class="form-select" required>
                                <option value="">--Select From Unit--</option>
                                @foreach ($units as $item)
                                    <option value="{{ $item->unit_id }}">
                                        {{ $item->name ?? '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">To Unit <span class="text-danger">*</span></label>
                            <select id="to_unit_id" name="to_unit_id" class="form-select" required>
                                <option value="">--Select To Unit--</option>
                                @foreach ($units as $item)
                                    <option value="{{ $item->unit_id }}">
                                        {{ $item->name ?? '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Conversion <span class="text-danger">*</span>
                            </label>
                            <input type="number" class="form-control" id="conversion_factor" name="conversion_factor"
                                placeholder="Enter Conversion" step="0.01" required>
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
