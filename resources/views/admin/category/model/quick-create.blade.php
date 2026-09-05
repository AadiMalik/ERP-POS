@php
use App\Enums\RoleNames;
@endphp
{{-- Quick-add Category modal for use on foreign forms (e.g. Product create).
     Mirrors admin/category/model/create.blade.php but with prefixed ids so it
     can be included alongside a page's own fields without id collisions, and
     posts to the same category.store route/validation. --}}
<div class="modal fade" id="quickAddCategoryModal" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('categories.create_title') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="quickAddCategoryForm" name="quickAddCategoryForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="row">
                        @if (RoleNames::SUPERADMIN == getRoleName())
                        <div class="col-md-12 mb-3">
                            <label class="form-label">{{ __('common.business') }} <span class="text-danger">*</span></label>
                            <select id="qa_category_business_id" name="business_id" class="form-select" required>
                                <option value="">{{ __('common.select_business') }}</option>
                                @foreach ($business ?? [] as $item)
                                <option value="{{ $item->business_id }}">{{ $item->code ?? '' }} {{ $item->name ?? '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endif
                        <div class="col-md-12 mb-3">
                            <label class="form-label">{{ __('common.name') }} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="qa_category_name" name="name" placeholder="{{ __('common.enter_name') }}" required>
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
