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
            <form id="sub_category_form" name="sub_category_form" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="sub_category_id" id="sub_category_id">
                    <div class="row">
                        @if (RoleNames::SUPERADMIN == getRoleName())
                        <div class="col-md-12">
                            <label class="form-label">{{ __('common.business') }} <span class="text-danger">*</span></label>
                            <select id="business_id" name="business_id" class="form-select" required>
                                <option value="">{{ __('common.select_business') }}</option>
                                @foreach ($business as $item)
                                <option value="{{ $item->business_id }}">{{ isset($item->code) ? $item->code : '' }}
                                    {{ $item->name ?? '' }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        @endif
                        <div class="col-md-12">
                            <div class="d-flex align-items-center justify-content-between">
                                <label class="form-label mb-0">{{ __('common.category') }} <span class="text-danger">*</span></label>
                                @include('admin.partials.quick-add-btn', ['permission' => 'category.create', 'modal' => 'quickAddCategoryModal', 'label' => __('common.category')])
                            </div>
                            <select id="category_id" name="category_id" class="form-select" required>
                                <option value="">{{ __('common.select_category') }}</option>
                                @if (RoleNames::SUPERADMIN != getRoleName())
                                @foreach ($categories as $item)
                                <option value="{{ $item->category_id }}">
                                    {{ $item->name ?? '' }}
                                </option>
                                @endforeach
                                @endif
                            </select>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">
                                {{ __('common.name') }} <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="name" name="name" placeholder="{{ __('common.enter_name') }}" required>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">{{ __('common.logo') }}</label>
                            <input type="file" class="form-control" id="logo" name="logo" accept="image/*">
                            <div class="mt-2 text-center">
                                <img id="logo_preview" src="" style="max-height:120px;display:none;" class="img-thumbnail">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        {{ __('common.close') }}
                    </button>
                    <button type="submit" id="saveBtn" class="btn btn-primary">
                        {{ __('common.save') }}
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>
