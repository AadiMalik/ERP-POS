<?php

namespace App\Services\Concrete\Admin\Reports;

use App\Enums\RoleNames;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;

class AccountsPayableReportService
{
    public const STATUS_LABELS = [
        'paid'    => 'Paid',
        'partial' => 'Partially Paid',
        'unpaid'  => 'Unpaid',
    ];

    protected array $allow_roles = [
        RoleNames::SUPERADMIN,
        RoleNames::BUSINESSADMIN,
        RoleNames::FINANCEMANAGER,
        RoleNames::ACCOUNTANT,
        RoleNames::REPORTINGANALYST,
        RoleNames::PURCHASEMANAGER,
    ];

    public function __construct(protected AccountsPayableInvoiceService $invoice_service)
    {
    }

    /**
     * Returns the filtered, GRN-level invoice Collection (each row an
     * outstanding-or-otherwise posted GRN "invoice"). Shared by getData(),
     * print, PDF and export so every output stays in sync.
     */
    public function build(array $obj)
    {
        $filters = [
            'business_id' => $obj['business_id'] ?? Auth::user()->business_id,
            'branch_id'   => $obj['branch_id'] ?? null,
            'supplier_id' => $obj['supplier_id'] ?? null,
            'allow_roles' => $this->allow_roles,
        ];

        $invoices = $this->invoice_service->getInvoices($filters);

        if (!empty($obj['status']) && $obj['status'] !== 'all') {
            $invoices = $invoices->where('status', $obj['status'])->values();
        } elseif (empty($obj['status'])) {
            // Default view: only invoices with an outstanding balance.
            $invoices = $invoices->where('outstanding_amount', '>', 0.009)->values();
        }

        $dateField = (!empty($obj['date_basis']) && $obj['date_basis'] === 'due_date') ? 'due_date' : 'invoice_date';

        if (!empty($obj['start_date'])) {
            $start = Carbon::parse($obj['start_date'])->startOfDay();
            $invoices = $invoices->filter(fn ($row) => Carbon::parse($row->$dateField)->gte($start))->values();
        }

        if (!empty($obj['end_date'])) {
            $end = Carbon::parse($obj['end_date'])->endOfDay();
            $invoices = $invoices->filter(fn ($row) => Carbon::parse($row->$dateField)->lte($end))->values();
        }

        // Aging report drill-down: filter to invoices falling in a specific bucket as-of a date.
        if (!empty($obj['bucket'])) {
            $asOf = !empty($obj['as_of_date']) ? Carbon::parse($obj['as_of_date']) : Carbon::today();
            $basis = $obj['aging_basis'] ?? 'due_date';

            $invoices = $invoices->filter(function ($row) use ($obj, $asOf, $basis) {
                if ($row->outstanding_amount <= 0.009) {
                    return false;
                }

                $basisDate = Carbon::parse($basis === 'invoice_date' ? $row->invoice_date : $row->due_date);

                return $this->resolveBucket($basisDate, $asOf) === $obj['bucket'];
            })->values();
        }

        return $invoices->sortBy([
            ['supplier_name', 'asc'],
            ['invoice_date', 'asc'],
        ])->values();
    }

    public function resolveBucket(Carbon $basisDate, Carbon $asOf): string
    {
        $age = $basisDate->diffInDays($asOf, false);

        if ($age <= 0) {
            return 'current';
        }
        if ($age <= 30) {
            return '1-30';
        }
        if ($age <= 60) {
            return '31-60';
        }
        if ($age <= 90) {
            return '61-90';
        }
        if ($age <= 120) {
            return '91-120';
        }

        return '120+';
    }

    public function getData(array $obj)
    {
        $invoices = $this->build($obj);

        $totals = [
            'total_invoiced'    => currency(round($invoices->sum('invoiced_amount'), 2)),
            'total_paid'        => currency(round($invoices->sum('paid_amount'), 2)),
            'total_outstanding' => currency(round($invoices->sum('outstanding_amount'), 2)),
        ];

        return DataTables::of($invoices)
            ->addColumn('supplier_name', fn ($row) => $row->supplier_name)
            ->addColumn('purchase_no', fn ($row) => $row->purchase_no)
            ->addColumn('invoice_number', fn ($row) => $row->invoice_number)
            ->addColumn('invoice_date', fn ($row) => localDate($row->invoice_date))
            ->addColumn('due_date', fn ($row) => localDate($row->due_date))
            ->addColumn('invoiced_amount', fn ($row) => currency($row->invoiced_amount))
            ->addColumn('paid_amount', fn ($row) => currency($row->paid_amount))
            ->addColumn('outstanding_amount', fn ($row) => currency($row->outstanding_amount))
            ->addColumn('remaining_balance', fn ($row) => currency($row->remaining_balance))
            ->addColumn('status', fn ($row) => self::STATUS_LABELS[$row->status] ?? ucfirst($row->status))
            ->addColumn('payment_references', fn ($row) => $row->payment_references)
            ->with($totals)
            ->make(true);
    }
}
