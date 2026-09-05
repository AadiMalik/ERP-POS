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
            <form id="journal_form" name="journal_form" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="journal_id" id="journal_id">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">
                                {{ __('common.name') }} <span class="text-danger">**</span>
                            </label>
                            <input type="text" class="form-control" id="name" name="name" placeholder="{{ __('common.enter_name') }}" required>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">
                                {{ __('journals.short') }} <span class="text-danger">**</span>
                            </label>
                            <input type="text" class="form-control" id="short" name="short" placeholder="{{ __('journals.enter_short') }}" required>
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