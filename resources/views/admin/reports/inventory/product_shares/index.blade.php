<?php /** @var array<int,string> $platforms */ ?>
@php
    use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">{{ $report_title ?? __('reports.product_shares') }}</h4>
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <button type="button" id="toggleFilter" class="btn btn-outline-primary">
                        <i class="fa fa-filter"></i> {{ __('common.filters') }}
                    </button>
                </div>
                <div class="d-flex gap-2">
                    @canAccess('reports.product-shares.print')
                    <a href="javascript:void(0);" id="btn_print" target="_blank" class="btn btn-outline-secondary">
                        <i class="fa fa-print"></i> {{ __('common.print') }}
                    </a>
                    @endcanAccess
                    @canAccess('reports.product-shares.pdf')
                    <a href="javascript:void(0);" id="btn_pdf" target="_blank" class="btn btn-outline-danger">
                        <i class="fa fa-file-pdf"></i> {{ __('common.pdf') }}
                    </a>
                    @endcanAccess
                    @canAccess('reports.product-shares.export')
                    <a href="javascript:void(0);" id="btn_excel" class="btn btn-outline-success">
                        <i class="fa fa-file-excel"></i> {{ __('common.excel') }}
                    </a>
                    @endcanAccess
                    @canAccess('reports.product-shares.export-csv')
                    <a href="javascript:void(0);" id="btn_csv" class="btn btn-outline-success">
                        <i class="fa fa-file-text"></i> {{ __('common.csv') }}
                    </a>
                    @endcanAccess
                </div>
            </div>
            <div class="card-body">
                <div id="filterSection" class="card-body border-bottom">
                    <div class="row g-3">
                        @if (RoleNames::SUPERADMIN == getRoleName())
                            <div class="col-md-3">
                                <label class="form-label">{{ __('common.business') }}</label>
                                <select id="business_id" class="form-select">
                                    <option value="">{{ __('common.all_businesses') }}</option>
                                    @foreach ($business as $item)
                                        <option value="{{ $item->business_id }}">{{ $item->name ?? '' }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div class="col-md-3">
                            <label class="form-label">{{ __('common.product') }}</label>
                            <select id="product_id" class="form-select">
                                <option value="">{{ __('common.all_products') }}</option>
                                @foreach ($products as $item)
                                    <option value="{{ $item->product_id }}">{{ $item->name ?? '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('common.category') }}</label>
                            <select id="category_id" class="form-select">
                                <option value="">{{ __('common.all_categories') }}</option>
                                @foreach ($categories as $item)
                                    <option value="{{ $item->category_id }}">{{ $item->name ?? '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('common.brand') }}</label>
                            <select id="brand_id" class="form-select">
                                <option value="">{{ __('common.all_brands') }}</option>
                                @foreach ($brands as $item)
                                    <option value="{{ $item->brand_id }}">{{ $item->name ?? '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('common.start_date') }}</label>
                            <input type="text" id="start_date" class="form-control datepicker" autocomplete="off">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('common.end_date') }}</label>
                            <input type="text" id="end_date" class="form-control datepicker" autocomplete="off">
                        </div>
                        <div class="col-md-3 d-flex align-items-end gap-2">
                            <button type="button" id="search_btn" class="btn btn-primary">{{ __('common.search') }}</button>
                            <button type="button" id="reset_filter" class="btn btn-outline-secondary">{{ __('common.reset') }}</button>
                        </div>
                    </div>
                </div>
                <div class="row g-3 p-4 pb-0">
                    <div class="col-md-3">
                        <div class="alert alert-info mb-0">
                            <strong>{{ __('reports.col_total_shares') }}:</strong> <span id="total_shares_display">-</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="alert alert-info mb-0">
                            <strong>{{ __('common.product') }}:</strong> <span id="total_products_display">-</span>
                        </div>
                    </div>
                </div>
                <div class="table-responsive p-4">
                    <table id="product_shares_table" class="table datatables">
                        <thead>
                            <tr>
                                <th>{{ __('reports.col_product') }}</th>
                                <th>{{ __('reports.col_category') }}</th>
                                <th>{{ __('reports.col_brand') }}</th>
                                <th>{{ __('reports.col_total_shares') }}</th>
                                @foreach ($platforms as $platform)
                                    <th>{{ __('reports.col_share_' . $platform) }}</th>
                                @endforeach
                                <th>{{ __('reports.col_last_shared') }}</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('js')
    @php
        $columns = "{data:'product_name',name:'product_name',sortable:false},"
            . "{data:'category_name',name:'category_name',sortable:false},"
            . "{data:'brand_name',name:'brand_name',sortable:false},"
            . "{data:'total_shares',name:'total_shares',sortable:false,className:'text-end'},";
        foreach ($platforms as $platform) {
            $columns .= "{data:'{$platform}',name:'{$platform}',sortable:false,className:'text-end'},";
        }
        $columns .= "{data:'last_shared_at',name:'last_shared_at',sortable:false}";
    @endphp
    @include('admin.partials.datatable', [
        'columns' => $columns,
        'route' => 'product-shares/data',
        'buttons' => false,
        'pageLength' => 25,
        'notordering' => true,
        'class' => 'product_shares_table',
        'variable' => 'product_shares_table',
        'params' => "business_id:$('#business_id').val(),product_id:$('#product_id').val(),category_id:$('#category_id').val(),brand_id:$('#brand_id').val(),start_date:$('#start_date').val(),end_date:$('#end_date').val()",
    ])
    <script>
        function currentReportParams() {
            let p = {};
            ['business_id','product_id','category_id','brand_id'].forEach(function(id) {
                if ($('#' + id).length) p[id] = $('#' + id).val() || '';
            });
            p.start_date = $('#start_date').val() || '';
            p.end_date = $('#end_date').val() || '';
            return p;
        }
        function buildReportUrl(path) {
            return url_local + path + '?' + $.param(currentReportParams());
        }
        $(document).ready(function() {
            $('select.form-select').select2();
            let q = new URLSearchParams(window.location.search);
            q.forEach(function(v, k) { if ($('#' + k).length) $('#' + k).val(v).trigger('change'); });
            product_shares_table.on('xhr.dt', function() { refreshTotals(); });
        });
        $('#search_btn').click(function() { product_shares_table.ajax.reload(); refreshTotals(); });
        $('#reset_filter').click(function() {
            $('#filterSection select').val('').trigger('change');
            $('#filterSection input').val('');
            product_shares_table.ajax.reload();
            refreshTotals();
        });
        $('#toggleFilter').click(function() { $('#filterSection').slideToggle(); });
        $('#btn_print').click(function() { window.open(buildReportUrl('/admin/reports/product-shares/print'), '_blank'); });
        $('#btn_pdf').click(function() { window.open(buildReportUrl('/admin/reports/product-shares/pdf'), '_blank'); });
        $('#btn_excel').click(function() { window.location = buildReportUrl('/admin/reports/product-shares/export'); });
        $('#btn_csv').click(function() { window.location = buildReportUrl('/admin/reports/product-shares/export-csv'); });
        function refreshTotals() {
            setTimeout(function() {
                let json = product_shares_table.ajax.json();
                if (!json) return;
                if (json.total_shares !== undefined) $('#total_shares_display').text(json.total_shares);
                if (json.total_products !== undefined) $('#total_products_display').text(json.total_products);
            }, 400);
        }
    </script>
@endsection
