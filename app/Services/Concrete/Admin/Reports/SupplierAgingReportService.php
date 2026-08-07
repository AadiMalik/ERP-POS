<?php

namespace App\Services\Concrete\Admin\Reports;

use App\Enums\RoleNames;
use App\Models\AccountingSetting;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;

class SupplierAgingReportService
{
    protected array $allow_roles = [
        RoleNames::SUPERADMIN,
        RoleNames::BUSINESSADMIN,
        RoleNames::FINANCEMANAGER,
        RoleNames::ACCOUNTANT,
        RoleNames::REPORTINGANALYST,
        RoleNames::PURCHASEMANAGER,
    ];

    protected array $buckets = ['current', '1-30', '31-60', '61-90', '91-120', '120+'];

    public function __construct(
        protected AccountsPayableInvoiceService $invoice_service,
        protected AccountsPayableReportService $ap_report_service,
        protected SupplierLedgerQueryService $ledger_query_service
    ) {
    }

    protected function bucketKey(string $bucket): string
    {
        return 'bucket_' . str_replace(['-', '+'], ['_', '_plus'], $bucket);
    }

    /**
     * One row per supplier with outstanding balance bucketed by age. Uses
     * AccountsPayableInvoiceService for the outstanding-per-GRN figures and
     * SupplierLedgerQueryService for the raw ledger balance shown alongside
     * it, so any drift between the two (e.g. a manual Journal Voucher
     * posted directly to the supplier account) stays visible rather than
     * silently hidden.
     */
    public function build(array $obj)
    {
        $business_id = $obj['business_id'] ?? Auth::user()->business_id;
        $branch_id = $obj['branch_id'] ?? null;

        $asOf = !empty($obj['as_of_date']) ? Carbon::parse($obj['as_of_date'])->endOfDay() : Carbon::now();

        $basis = $obj['aging_basis']
            ?? AccountingSetting::where('business_id', $business_id)->value('aging_basis')
            ?? 'due_date';

        $filters = [
            'business_id' => $business_id,
            'branch_id'   => $branch_id,
            'supplier_id' => $obj['supplier_id'] ?? null,
            'allow_roles' => $this->allow_roles,
        ];

        $invoices = $this->invoice_service->getInvoices($filters)
            ->filter(fn ($row) => $row->outstanding_amount > 0.009);

        $rows = $invoices->groupBy('supplier_id')->map(function ($supplierInvoices, $supplier_id) use ($basis, $asOf, $business_id, $branch_id) {
            $first = $supplierInvoices->first();

            $buckets = array_fill_keys($this->buckets, 0.0);

            foreach ($supplierInvoices as $invoice) {
                $basisDate = Carbon::parse($basis === 'invoice_date' ? $invoice->invoice_date : $invoice->due_date);
                $bucket = $this->ap_report_service->resolveBucket($basisDate, $asOf);
                $buckets[$bucket] += (float) $invoice->outstanding_amount;
            }

            $totalOutstanding = round(array_sum($buckets), 2);

            $supplier = Supplier::find($supplier_id);
            $accountId = $supplier->account_id ?? null;

            $ledgerBalance = 0.0;
            $ledgerType = '';
            $lastPaymentDate = null;

            if ($accountId) {
                $balance = $this->ledger_query_service->totalBalance($business_id, $branch_id, $supplier_id, $accountId, $this->allow_roles);
                $ledgerBalance = $balance['balance'];
                $ledgerType = $balance['type'];
                $lastPaymentDate = $this->ledger_query_service->lastPaymentDate($business_id, $branch_id, $supplier_id, $accountId, $this->allow_roles);
            }

            $bucketValues = [];
            foreach ($this->buckets as $bucket) {
                $bucketValues[$this->bucketKey($bucket)] = round($buckets[$bucket], 2);
            }

            return (object) array_merge([
                'supplier_id'        => $supplier_id,
                'supplier_name'      => $first->supplier_name,
                'total_outstanding'  => $totalOutstanding,
                'total_balance'      => $ledgerBalance,
                'total_balance_type' => $ledgerType,
                'last_payment_date'  => $lastPaymentDate,
                'reconciled'         => abs($totalOutstanding - $ledgerBalance) <= 0.01,
            ], $bucketValues);
        })->values();

        return $rows->sortByDesc('total_outstanding')->values();
    }

    public function getData(array $obj)
    {
        $rows = $this->build($obj);
        $asOfDate = !empty($obj['as_of_date']) ? $obj['as_of_date'] : now()->format('Y-m-d');
        $agingBasis = $obj['aging_basis'] ?? '';

        $totals = [
            'grand_total_outstanding' => currency(round($rows->sum('total_outstanding'), 2)),
        ];

        $dt = DataTables::of($rows)
            ->addColumn('supplier_name', fn ($row) => $row->supplier_name)
            ->addColumn('total_outstanding', fn ($row) => currency($row->total_outstanding))
            ->addColumn('last_payment_date', fn ($row) => $row->last_payment_date ? localDate($row->last_payment_date) : 'N/A')
            ->addColumn('total_balance', function ($row) {
                $html = currency($row->total_balance) . ' ' . $row->total_balance_type;

                if (!$row->reconciled) {
                    $html .= ' <i class="fa fa-exclamation-triangle text-warning" title="Does not reconcile with the raw ledger balance"></i>';
                }

                return $html;
            });

        foreach ($this->buckets as $bucket) {
            $key = $this->bucketKey($bucket);
            $dt->addColumn($key, function ($row) use ($key, $bucket, $asOfDate, $agingBasis) {
                if ($row->$key <= 0) {
                    return '';
                }

                $url = url('admin/reports/accounts-payable') . '?' . http_build_query([
                    'supplier_id' => $row->supplier_id,
                    'bucket'      => $bucket,
                    'as_of_date'  => $asOfDate,
                    'aging_basis' => $agingBasis,
                ]);

                return '<a href="' . $url . '" target="_blank">' . currency($row->$key) . '</a>';
            });
        }

        return $dt->rawColumns(array_merge(['total_balance'], array_map(fn ($b) => $this->bucketKey($b), $this->buckets)))
            ->with($totals)
            ->make(true);
    }
}
