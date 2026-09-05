@extends('layouts.pos')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">{{ __('pos.enter_pos') }}</h5>
                        <small class="text-muted">{{ __('pos.enter_pos_hint') }}</small>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('pos-screen.context') }}" method="POST">
                            @csrf

                            @if ($is_superadmin)
                                <div class="mb-3">
                                    <label class="form-label">{{ __('common.business') }} <span class="text-danger">*</span></label>
                                    <select class="form-select select2" name="business_id" id="business_id" required>
                                        <option value="">{{ __('common.select_business') }}</option>
                                        @foreach ($businesses as $item)
                                            <option value="{{ $item->business_id }}">{{ $item->name ?? '' }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif

                            <div class="mb-3">
                                <label class="form-label">{{ __('common.branch') }} <span class="text-danger">*</span></label>
                                <select class="form-select select2" name="branch_id" id="branch_id" required>
                                    <option value="">{{ __('common.select_branch') }}</option>
                                    @foreach ($branches as $item)
                                        <option value="{{ $item->branch_id }}">{{ $item->name ?? '' }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">{{ __('common.warehouse') }}</label>
                                <select class="form-select select2" name="warehouse_id" id="warehouse_id">
                                    <option value="">{{ __('common.select_warehouse') }}</option>
                                    @foreach ($warehouses as $item)
                                        <option value="{{ $item->warehouse_id }}">{{ $item->name ?? '' }}</option>
                                    @endforeach
                                </select>
                                <small class="text-muted">{{ __('pos.warehouse_context_hint') }}</small>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">{{ __('pos.continue_to_pos') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('js')
@php
    $__i18nPos = [
        'select_branch' => __('common.select_branch'),
        'select_warehouse' => __('common.select_warehouse'),
    ];
@endphp
<script>window.i18n_pos = @json($__i18nPos);</script>
<script src="{{ asset('public/assets/js/admin/order-history.js') }}"></script>
    <script>
        $(document).ready(function() {
            $('.select2').select2();
        });

        $('#business_id').on('change', function() {
            let business_id = $(this).val();

            $('#branch_id').html('<option value="">{{ __('common.select_branch') }}</option>');
            $('#warehouse_id').html('<option value="">{{ __('common.select_warehouse') }}</option>');

            if (!business_id) {
                return;
            }

            ajaxRequest({
                url: url_local + '/admin/pos-screen/context-options/' + business_id,
                data: {}
            }).then(function(response) {
                let data = response.Data;

                let branchOptions = '<option value="">{{ __('common.select_branch') }}</option>';
                $.each(data.branches, function(_, item) {
                    branchOptions += `<option value="${item.branch_id}">${item.name}</option>`;
                });
                $('#branch_id').html(branchOptions);

                let warehouseOptions = '<option value="">{{ __('common.select_warehouse') }}</option>';
                $.each(data.warehouses, function(_, item) {
                    warehouseOptions += `<option value="${item.warehouse_id}">${item.name}</option>`;
                });
                $('#warehouse_id').html(warehouseOptions);
            }).catch(function(err) {
                errorMessage(err.Message ?? window.i18n?.something_went_wrong || 'Something went wrong');
            });
        });
    </script>
@endsection
