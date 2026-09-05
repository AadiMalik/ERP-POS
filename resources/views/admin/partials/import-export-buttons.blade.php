{{--
    Generic Import/Export buttons, included beside the "Add New" button on
    any CRUD listing page.

    Required: $importExportModule - the module key (matches PermissionRegistry
        and this module's ImportExportModuleRegistry entry, e.g. 'category',
        'transfer-note', 'brand').
    Optional: $importExportRouteSegment - the URL path segment this module's
        routes actually live under, if different from $importExportModule
        (e.g. Brand's permission/module key is 'brand' but its routes are
        registered under the plural 'brands' prefix). Defaults to
        $importExportModule when omitted.
    Optional: $importExportLabel - human label used in modal headings
        (defaults to a title-cased version of the module key).
    Optional: $importExportRefreshFn - JS function name (no parentheses) to
        call after a successful import confirm to refresh this page's
        DataTable, e.g. 'initDataTablecategory_table'. Falls back to a full
        page reload if omitted.
    Optional: $importExportExportParamsSelector - comma-separated CSS
        selector list of filter inputs (e.g. '#filter_business_id') read at
        click-time and appended to the export download link.
--}}
@php
    $importExportRouteSegment = $importExportRouteSegment ?? $importExportModule;
@endphp
@can($importExportModule . '.import')
    <button type="button" class="btn btn-outline-primary import-export-import-btn"
        data-module="{{ $importExportRouteSegment }}"
        data-label="{{ $importExportLabel ?? ucfirst(str_replace('-', ' ', $importExportModule)) }}"
        data-export-params-selector="{{ $importExportExportParamsSelector ?? '' }}"
        @if (!empty($importExportRefreshFn)) data-refresh-fn="{{ $importExportRefreshFn }}" @endif>
        <i class="fa fa-upload mr-5"></i>{{ __('common.import') }}
    </button>
@endcan
@can($importExportModule . '.export')
    <a href="javascript:void(0)" class="btn btn-outline-success import-export-export-btn"
        data-module="{{ $importExportRouteSegment }}"
        data-export-params-selector="{{ $importExportExportParamsSelector ?? '' }}">
        <i class="fa fa-download mr-5"></i>{{ __('common.export') }}
    </a>
@endcan
