<?php

namespace App\Services\Concrete\Admin\Reports;

use App\Enums\JournalSourceTypes;
use App\Enums\RoleNames;
use App\Enums\Status;
use App\Models\CustomerPayment;
use App\Models\SupplierPayment;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

/**
 * Payments made against Service Sales (customer receipts) and Service
 * Purchases (supplier payments), combined into one reusable report via the
 * `payment_type` filter (Receipt/Payment/All) - mirrors
 * CustomerPaymentHistoryReportService/SupplierPaymentHistoryReportService's
 * posted+journal-verified trust check, since these are the two existing
 * "payment report" precedents in this codebase.
 */
class ServicePaymentReportService
{
    public const PAYMENT_TYPE_OPTIONS = [
        ''         => 'All',
        'receipt'  => 'Receipt (from Customer)',
        'payment'  => 'Payment (to Supplier)',
    ];

    protected array $allow_roles = [
        RoleNames::SUPERADMIN,
        RoleNames::BUSINESSADMIN,
        RoleNames::BRANCHADMIN,
        RoleNames::SALEMANAGER,
        RoleNames::PURCHASEMANAGER,
        RoleNames::FINANCEMANAGER,
        RoleNames::ACCOUNTANT,
        RoleNames::REPORTINGANALYST,
    ];

    protected function filters(array $obj): array
    {
        return [
            'business_id'    => $obj['business_id'] ?? Auth::user()->business_id,
            'branch_id'      => $obj['branch_id'] ?? null,
            'payment_method' => $obj['payment_method'] ?? null,
            'start_date'     => $obj['start_date'] ?? null,
            'end_date'       => $obj['end_date'] ?? null,
        ];
    }

    protected function receiptsQuery(array $filters)
    {
        $query = CustomerPayment::query()
            ->whereNotNull('customer_payments.service_sale_id')
            ->where('customer_payments.is_deleted', 0)
            ->where('customer_payments.status', Status::POSTED)
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('journal_entries')
                    ->whereColumn('journal_entries.source_id', 'customer_payments.customer_payment_id')
                    ->where('journal_entries.source_type', JournalSourceTypes::CUSTOMER_PAYMENT)
                    ->where('journal_entries.status', Status::POSTED)
                    ->where('journal_entries.is_deleted', 0);
            })
            ->with(['user', 'serviceSale', 'paymentAccount', 'postedby']);

        $this->applyCommonFilters($query, $filters, 'customer_payments');

        return applyRoleScope($query, $this->allow_roles, 'customer_payments.business_id', 'customer_payments.branch_id');
    }

    protected function paymentsQuery(array $filters)
    {
        $query = SupplierPayment::query()
            ->whereNotNull('supplier_payments.service_purchase_id')
            ->where('supplier_payments.is_deleted', 0)
            ->where('supplier_payments.status', Status::POSTED)
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('journal_entries')
                    ->whereColumn('journal_entries.source_id', 'supplier_payments.supplier_payment_id')
                    ->where('journal_entries.source_type', JournalSourceTypes::SUPPLIER_PAYMENT)
                    ->where('journal_entries.status', Status::POSTED)
                    ->where('journal_entries.is_deleted', 0);
            })
            ->with(['supplier', 'servicePurchase', 'paymentAccount', 'postedby']);

        $this->applyCommonFilters($query, $filters, 'supplier_payments');

        return applyRoleScope($query, $this->allow_roles, 'supplier_payments.business_id', 'supplier_payments.branch_id');
    }

    protected function applyCommonFilters($query, array $filters, string $table): void
    {
        if (!empty($filters['business_id'])) {
            $query->where("$table.business_id", $filters['business_id']);
        }
        if (!empty($filters['branch_id'])) {
            $query->where("$table.branch_id", $filters['branch_id']);
        }
        if (!empty($filters['payment_method'])) {
            $query->where("$table.payment_method", $filters['payment_method']);
        }
        if (!empty($filters['start_date'])) {
            $query->where("$table.payment_date", '>=', Carbon::parse($filters['start_date'])->startOfDay());
        }
        if (!empty($filters['end_date'])) {
            $query->where("$table.payment_date", '<=', Carbon::parse($filters['end_date'])->endOfDay());
        }
    }

    /**
     * Normalized Receipt (Customer Payment against a Service Sale) and/or
     * Payment (Supplier Payment against a Service Purchase) rows, per the
     * payment_type filter. Shared by getData(), print, PDF and export.
     */
    public function build(array $obj): Collection
    {
        $filters = $this->filters($obj);
        $type = $obj['payment_type'] ?? '';

        $rows = collect();

        if ($type !== 'payment') {
            $rows = $rows->merge($this->receiptsQuery($filters)->get()->map(fn ($row) => (object) [
                'payment_type'   => 'Receipt',
                'payment_date'   => $row->payment_date,
                'payment_no'     => $row->payment_no,
                'party_name'     => $row->user->name ?? 'Walk-in',
                'reference_no'   => $row->serviceSale->service_sale_no ?? '',
                'payment_method' => $row->payment_method,
                'payment_account' => $row->paymentAccount->name ?? '',
                'tax_amount'     => (float) $row->tax_amount,
                'discount_amount' => (float) $row->discount_amount,
                'net_amount'     => (float) $row->net_amount,
                'postedby'       => $row->postedby->name ?? '',
                'view_url'       => url('admin/customer-payment/' . $row->customer_payment_id . '/print'),
            ]));
        }

        if ($type !== 'receipt') {
            $rows = $rows->merge($this->paymentsQuery($filters)->get()->map(fn ($row) => (object) [
                'payment_type'   => 'Payment',
                'payment_date'   => $row->payment_date,
                'payment_no'     => $row->payment_no,
                'party_name'     => $row->supplier->name ?? '',
                'reference_no'   => $row->servicePurchase->service_purchase_no ?? '',
                'payment_method' => $row->payment_method,
                'payment_account' => $row->paymentAccount->name ?? '',
                'tax_amount'     => (float) $row->tax_amount,
                'discount_amount' => (float) $row->discount_amount,
                'net_amount'     => (float) $row->net_amount,
                'postedby'       => $row->postedby->name ?? '',
                'view_url'       => url('admin/supplier-payment/' . $row->supplier_payment_id . '/print'),
            ]));
        }

        return $rows->sortByDesc('payment_date')->values();
    }

    public function getData(array $obj)
    {
        $rows = $this->build($obj);

        $totals = [
            'total_receipts' => currency(round((float) $rows->where('payment_type', 'Receipt')->sum('net_amount'), 2)),
            'total_payments' => currency(round((float) $rows->where('payment_type', 'Payment')->sum('net_amount'), 2)),
            'net_cash_flow'  => currency(round(
                (float) $rows->where('payment_type', 'Receipt')->sum('net_amount')
                - (float) $rows->where('payment_type', 'Payment')->sum('net_amount'),
                2
            )),
        ];

        return DataTables::of($rows)
            ->addColumn('payment_date', fn ($row) => localDate($row->payment_date))
            ->addColumn('payment_type', fn ($row) => $row->payment_type)
            ->addColumn('payment_no', fn ($row) => $row->payment_no)
            ->addColumn('party_name', fn ($row) => $row->party_name)
            ->addColumn('reference_no', fn ($row) => $row->reference_no)
            ->addColumn('payment_method', fn ($row) => ucwords(str_replace('_', ' ', $row->payment_method)))
            ->addColumn('payment_account', fn ($row) => $row->payment_account)
            ->addColumn('tax_amount', fn ($row) => currency($row->tax_amount))
            ->addColumn('discount_amount', fn ($row) => currency($row->discount_amount))
            ->addColumn('net_amount', fn ($row) => currency($row->net_amount))
            ->addColumn('postedby', fn ($row) => $row->postedby)
            ->addColumn('action', fn ($row) => '<a class="btn btn-icon btn-outline-primary" target="_blank" href="' . $row->view_url . '" title="View Payment"><i class="fa fa-eye"></i></a>')
            ->rawColumns(['action'])
            ->with($totals)
            ->make(true);
    }
}
