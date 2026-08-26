@php
    $header = $print_setting->header_config ?? config('print_defaults.header');
    $footer = $print_setting->footer_config ?? config('print_defaults.footer');
    $page = $print_setting->page_config ?? config('print_defaults.page');
@endphp

<style>
    .print-setting-nav .nav-link {
        color: #495057;
    }

    .print-setting-nav .nav-link.active {
        background: #3833C8;
        color: #fff;
    }

    .pt-field-table th,
    .pt-field-table td {
        vertical-align: middle;
    }

    .pt-field-table input[type="number"],
    .pt-field-table select {
        min-width: 90px;
    }
</style>

<form id="printSettingForm">
    @csrf
    <h4>Print Settings</h4>
    <p class="text-muted">Controls the header, footer, page and printer behavior of every printed CRUD document and
        report for this business. Leave everything as-is to keep using the built-in Dukanaz default layout.
    </p>
    <hr>

    <ul class="nav nav-pills print-setting-nav mb-3" id="print-setting-tab" role="tablist">
        <li class="nav-item">
            <button class="nav-link active" type="button" data-bs-toggle="pill"
                data-bs-target="#print-header">Header</button>
        </li>
        <li class="nav-item">
            <button class="nav-link" type="button" data-bs-toggle="pill" data-bs-target="#print-footer">Footer</button>
        </li>
        <li class="nav-item">
            <button class="nav-link" type="button" data-bs-toggle="pill"
                data-bs-target="#print-page">Page &amp; Printer</button>
        </li>
        <li class="nav-item">
            <button class="nav-link" type="button" data-bs-toggle="pill" data-bs-target="#print-body">Body / Table</button>
        </li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="print-header">
            @include('admin.setting.tabs.print.header', ['header' => $header])
        </div>
        <div class="tab-pane fade" id="print-footer">
            @include('admin.setting.tabs.print.footer', ['footer' => $footer])
        </div>
        <div class="tab-pane fade" id="print-page">
            @include('admin.setting.tabs.print.page', ['page' => $page])
        </div>
        <div class="tab-pane fade" id="print-body">
            @include('admin.setting.tabs.print.body')
        </div>
    </div>

    <div class="col-md-12">
        <hr>
        <div class="text-end">
            <button type="button" class="btn btn-primary"
                onclick="saveSetting('#printSettingForm','{{ url('admin/setting/print') }}')">
                Save Changes
            </button>
        </div>
    </div>
</form>
