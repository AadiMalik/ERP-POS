@php
use App\Enums\RoleNames;
@endphp
<div class="modal fade" id="ajaxModel" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="modelHeading"></h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="website_section_form" name="website_section_form" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="section_id" id="section_id">
                    <div class="row">
                        @if (RoleNames::SUPERADMIN == getRoleName())
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Business <span class="text-danger">*</span></label>
                            <select id="business_id" name="business_id" class="form-select" required>
                                <option value="">--Select Business--</option>
                                @foreach ($business as $item)
                                <option value="{{ $item->business_id }}">{{ $item->name ?? '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endif
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Section Type <span class="text-danger">*</span></label>
                            <select id="type" name="type" class="form-select" required>
                                <option value="">--Select Type--</option>
                                @foreach ($types as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-8 mb-3">
                            <label class="form-label">Tagline / Badge <small class="text-muted">(small label shown above the heading, if any)</small></label>
                            <input type="text" class="form-control" id="tagline" name="tagline" placeholder="100% Fresh & Quality Guaranteed">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Tagline Icon <small class="text-muted">(FontAwesome class)</small></label>
                            <input type="text" class="form-control" id="tagline_icon" name="tagline_icon" placeholder="fa-solid fa-leaf">
                        </div>
                        <div class="col-md-8 mb-3">
                            <label class="form-label">Heading</label>
                            <input type="text" class="form-control" id="heading" name="heading">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Heading Icon <small class="text-muted">(FontAwesome class)</small></label>
                            <input type="text" class="form-control" id="heading_icon" name="heading_icon" placeholder="fa fa-star">
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Image (Desktop)</label>
                            <input type="file" class="form-control" id="image" name="image" accept="image/*">
                            <img id="image_preview" src="" style="max-height:100px;display:none;" class="img-thumbnail mt-2">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Image (Mobile)</label>
                            <input type="file" class="form-control" id="image_mobile" name="image_mobile" accept="image/*">
                            <img id="image_mobile_preview" src="" style="max-height:100px;display:none;" class="img-thumbnail mt-2">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Button Text</label>
                            <input type="text" class="form-control" id="button_text" name="button_text">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Button Link (custom URL)</label>
                            <input type="text" class="form-control" id="button_link" name="button_link">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Link Type</label>
                            <select id="link_type" name="link_type" class="form-select">
                                <option value="">--None--</option>
                                <option value="product">Product</option>
                                <option value="category">Category</option>
                                <option value="collection">Collection</option>
                                <option value="shop">Shop Page</option>
                                <option value="promotion">Promotion</option>
                                <option value="custom">Custom URL</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Link Target ID</label>
                            <input type="text" class="form-control" id="link_target_id" name="link_target_id" placeholder="product_id / category_id">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Sort Order</label>
                            <input type="number" class="form-control" id="sort_order" name="sort_order" value="0">
                        </div>
                        <div class="col-md-12"><hr><small class="text-muted">Optional secondary call-to-action (e.g. hero's second button) and countdown deadline (e.g. for a promo/discount banner):</small></div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Secondary Button Text</label>
                            <input type="text" class="form-control" id="secondary_button_text" name="secondary_button_text">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Secondary Button Link (custom URL)</label>
                            <input type="text" class="form-control" id="secondary_button_link" name="secondary_button_link">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Secondary Link Type</label>
                            <select id="secondary_link_type" name="secondary_link_type" class="form-select">
                                <option value="">--None--</option>
                                <option value="product">Product</option>
                                <option value="category">Category</option>
                                <option value="collection">Collection</option>
                                <option value="shop">Shop Page</option>
                                <option value="promotion">Promotion</option>
                                <option value="custom">Custom URL</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Secondary Link Target ID</label>
                            <input type="text" class="form-control" id="secondary_link_target_id" name="secondary_link_target_id">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Countdown Deadline <small class="text-muted">(leave blank to hide countdown)</small></label>
                            <input type="datetime-local" class="form-control" id="countdown_end_at" name="countdown_end_at">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('common.close') }}</button>
                    <button type="submit" id="saveBtn" class="btn btn-primary">{{ __('common.save') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
