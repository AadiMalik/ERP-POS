@php
    use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">Voucher Usage Report</h4>
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <button type="button" id="toggleFilter" class="btn btn-outline-primary">
                        <i class="fa fa-filter"></i> Filters
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div id="filterSection" class="card-body border-bottom">
                    <div class="row g-3">
                        @if (RoleNames::SUPERADMIN == getRoleName())
                            <div class="col-md-3">
                                <label class="form-label">Business</label>
                                <select id="business_id" class="form-select">
                                    <option value="">--All Businesses--</option>
                                    @foreach ($business as $item)
                                        <option value="{{ $item->business_id }}">{{ $item->code ?? '' }} {{ $item->name ?? '' }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div class="col-md-3">
                            <label class="form-label">Voucher</label>
                            <select id="voucher_id" class="form-select">
                                <option value="">--All Vouchers--</option>
                                @foreach ($vouchers as $item)
                                    <option value="{{ $item->voucher_id }}">{{ $item->code }} — {{ $item->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Period</label>
                            @include('admin.partials.date_filter')
                        </div>
                        <div class="col-md-3 d-flex align-items-end gap-2">
                            <button type="button" id="search_btn" class="btn btn-primary">Search</button>
                            <button type="button" id="reset_filter" class="btn btn-outline-secondary">Reset</button>
                        </div>
                    </div>
                </div>

                <div class="row g-3 p-4 pb-0">
                    <div class="col-md-3">
                        <div class="alert alert-info mb-0"><strong>Total Uses:</strong> <span id="total_redemptions">-</span></div>
                    </div>
                    <div class="col-md-3">
                        <div class="alert alert-secondary mb-0"><strong>Total Discount:</strong> <span id="total_discount">-</span></div>
                    </div>
                    <div class="col-md-3">
                        <div class="alert alert-secondary mb-0"><strong>Unique Vouchers:</strong> <span id="unique_vouchers">-</span></div>
                    </div>
                    <div class="col-md-3">
                        <div class="alert alert-secondary mb-0"><strong>Unique Customers:</strong> <span id="unique_customers">-</span></div>
                    </div>
                </div>

                <div class="table-responsive text-nowrap p-4">
                    <table id="voucher_usage_report_table" class="table display datatables" style="width:100%">
                        <thead>
                            <tr>
                                <th>Used At</th>
                                <th>Voucher Code</th>
                                <th>Voucher Name</th>
                                <th>Customer</th>
                                <th>Email</th>
                                <th>Order #</th>
                                <th>Order Status</th>
                                <th>Discount</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('js')
<script src="{{ asset('public/assets/js/admin/reports/voucher-usage-report.js') }}"></script>
@include('admin.partials.datatable', [
    'columns' => "
{data: 'used_at', name: 'date_created'},
{data: 'voucher_code', name: 'voucher.code'},
{data: 'voucher_name', name: 'voucher.name'},
{data: 'customer', name: 'user.name'},
{data: 'customer_email', name: 'user.email'},
{data: 'order_no', name: 'order.daily_order_id'},
{data: 'order_status', name: 'order.status'},
{data: 'discount_amount', name: 'discount_amount'},
",
    'tableId' => 'voucher_usage_report_table',
    'ajaxUrl' => url('/admin/reports/voucher-usage-report/data'),
    'initFunction' => 'initDataTablevoucher_usage_report_table',
])
@endsection
