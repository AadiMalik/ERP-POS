@php
    $page = $erpTable['page'] ?? [];
    $erpTableId = $page['table_id'] ?? ($erpTable['key'] . '_table');
    $refreshFn = 'initDataTable' . $erpTableId;
    $createPermission = $page['create_permission'] ?? null;
    $importExportModule = $page['import_export_module'] ?? null;
@endphp
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">{{ $page['title'] ?? $erpTable['label'] }}</h4>
    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <div>
                @if (!empty($erpTable['filters']))
                    <button type="button" id="toggleFilter" class="btn btn-outline-primary">
                        <i class="fa fa-filter"></i>
                        {{ __('common.filters') }}
                    </button>
                @endif
            </div>
            <div class="d-flex gap-2">
                @if ($importExportModule)
                    @include('admin.partials.import-export-buttons', [
                        'importExportModule' => $importExportModule,
                        'importExportLabel' => $page['title'] ?? $erpTable['label'],
                        'importExportRefreshFn' => $refreshFn,
                        'importExportDatatableKey' => !empty($erpTable['can_export']) ? $erpTable['key'] : null,
                        'importExportShowExport' => !empty($erpTable['can_export']),
                        'importExportExportParamsSelector' => $page['export_params_selector'] ?? '',
                    ])
                @endif
                @if (!empty($page['create_url']) && (!$createPermission || auth()->user()?->can($createPermission)))
                    <a href="{{ $page['create_url'] }}" class="btn btn-primary rounded-pill">
                        <i class="fa fa-plus"></i>
                        {{ __('common.add_new') }}
                    </a>
                @endif
            </div>
        </div>
        <div class="card-body erp-dt-wrap" data-erp-dt="{{ $erpTable['key'] }}">
            @if (!empty($erpTable['filters']))
                <div id="filterSection" class="card-body border-bottom" style="display:none;">
                    <div class="row g-3">
                        @foreach ($erpTable['filters'] as $filter)
                            <div class="col-md-3">
                                <label class="form-label">{{ $filter['label'] }}</label>
                                @if ($filter['type'] === 'date_range')
                                    <div data-erp-filter="{{ $filter['key'] }}">
                                        @include('admin.partials.date_filter')
                                    </div>
                                @elseif ($filter['type'] === 'text')
                                    <input type="text" id="{{ $filter['key'] }}" class="form-control"
                                        data-erp-filter="{{ $filter['key'] }}"
                                        placeholder="{{ $filter['placeholder'] ?? '' }}">
                                @elseif ($filter['type'] === 'numeric_range')
                                    <div class="erp-dt-filter-range" data-erp-filter="{{ $filter['key'] }}">
                                        <input type="number" class="form-control erp-dt-min" placeholder="{{ __('datatable.min') }}">
                                        <input type="number" class="form-control erp-dt-max" placeholder="{{ __('datatable.max') }}">
                                    </div>
                                @else
                                    <select id="{{ $filter['key'] }}" class="form-select"
                                        data-erp-filter="{{ $filter['key'] }}"
                                        @if ($filter['type'] === 'multi_select') multiple @endif>
                                        @if ($filter['type'] !== 'multi_select')
                                            <option value="">{{ $filter['placeholder'] ?: __('common.all') }}</option>
                                        @endif
                                        @foreach ($filter['options'] ?? [] as $opt)
                                            <option value="{{ $opt['value'] }}">{{ $opt['label'] }}</option>
                                        @endforeach
                                    </select>
                                @endif
                            </div>
                        @endforeach
                        <div class="col-md-3 d-flex align-items-end gap-2">
                            <button type="button" id="search_btn" class="btn btn-primary">
                                {{ __('common.search') }}
                            </button>
                            <button type="button" id="reset_filter" class="btn btn-outline-secondary">
                                {{ __('common.reset') }}
                            </button>
                        </div>
                    </div>
                </div>
            @endif
            <div class="table-responsive p-4">
                <table id="{{ $erpTableId }}" class="table erp-datatable" style="width:100%">
                    <thead></thead>
                </table>
            </div>
        </div>
    </div>
</div>
@if ($importExportModule)
    @include('admin.partials.import-export-modal')
@endif

<div class="modal fade" id="erpDtCustomizeModal-{{ $erpTable['key'] }}" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('datatable.customize_table') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small">{{ __('datatable.column_order') }}</p>
                <div class="erp-dt-col-list"></div>
                <div class="row g-3 mt-2">
                    <div class="col-md-4">
                        <label class="form-label">{{ __('datatable.sort_column') }}</label>
                        <select class="form-select erp-dt-sort-column"></select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('datatable.sort_direction') }}</label>
                        <select class="form-select erp-dt-sort-dir">
                            <option value="asc">{{ __('datatable.ascending') }}</option>
                            <option value="desc">{{ __('datatable.descending') }}</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('datatable.page_length') }}</label>
                        <select class="form-select erp-dt-page-length"></select>
                    </div>
                </div>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-outline-secondary erp-dt-reset-btn">{{ __('datatable.reset_layout') }}</button>
                <div>
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                    <button type="button" class="btn btn-primary erp-dt-apply-btn">{{ __('datatable.apply') }}</button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('js')
<script>
    window.{{ $refreshFn }} = function () {
        if (window.ErpDataTable) {
            window.ErpDataTable.reload(@json($erpTable['key']));
        }
    };
    $(function () {
        window.ErpDataTable.init(@json($erpTable), {
            tableId: @json($erpTableId),
            refreshFn: @json($refreshFn),
            i18n: {
                show_column: @json(__('datatable.show_column')),
                export_this: @json(__('datatable.export_this')),
                min: @json(__('datatable.min')),
                max: @json(__('datatable.max')),
                search: @json(__('common.search')),
                reset: @json(__('common.reset')),
                all: @json(__('common.all')),
                move_up: @json(__('datatable.move_up')),
                move_down: @json(__('datatable.move_down'))
            }
        });
    });
</script>
@endpush
