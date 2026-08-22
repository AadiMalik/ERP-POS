<?php

namespace App\Services\Concrete\Admin\Reports;

use App\Enums\RoleNames;
use App\Models\AccountingSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;

class CustomerAgingReportService
{
    protected array $allow_roles = [
        RoleNames::SUPERADMIN,
        RoleNames::BUSINESSADMIN,
        RoleNames::FINANCEMANAGER,
        RoleNames::ACCOUNTANT,
        RoleNames::REPORTINGANALYST,
        RoleNames::SALEMANAGER,
    ];

    protected array $buckets = ['current', '1-30', '31-60', '61-90', '91-120', '120+'];

    public function __construct(
        protected CustomerAgingInvoiceService $invoice_service,
        protected AccountsPayableReportService $ap_report_service,
        protected CustomerLedgerQueryService $ledger_query_service
    ) {
    }

    protected function bucketKey(string $bucket): string
    {
        return 'bucket_' . str_replace(['-', '+'], ['_', '_plus'], $bucket);
    }

    /**
     * One row per customer with outstanding balance bucketed by age. Mirrors
     * SupplierAgingReportService::build() - uses CustomerAgingInvoiceService
     * for the outstanding-per-order figures and CustomerLedgerQueryService
     * for the raw ledger balance shown alongside it, so any drift between
     * the two stays visible rather than silently hidden.
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
            'user_id'     => $obj['user_id'] ?? null,
            'allow_roles' => $this->allow_roles,
        ];

        $invoices = $this->invoice_service->getInvoices($filters)
            ->filter(fn ($row) => $row->outstanding_amount > 0.009);

        $rows = $invoices->groupBy('user_id')->map(function ($customerInvoices, $user_id) use ($basis, $asOf, $business_id, $branch_id) {
            $first = $customerInvoices->first();

            $buckets = array_fill_keys($this->buckets, 0.0);

            foreach ($customerInvoices as $invoice) {
                $basisDate = Carbon::parse($basis === 'invoice_date' ? $invoice->invoice_date : $invoice->due_date);
                $bucket = $this->ap_report_service->resolveBucket($basisDate, $asOf);
                $buckets[$bucket] += (float) $invoice->outstanding_amount;
            }

            $totalOutstanding = round(array_sum($buckets), 2);

            $profile = $this->ledger_query_service->resolveCustomerProfile($user_id, $business_id);
            $accountId = $this->ledger_query_service->resolveAccountId($profile, $business_id);

            $ledgerBalance = 0.0;
            $ledgerType = '';
            $lastPaymentDate = null;

            if ($accountId) {
                $balance = $this->ledger_query_service->totalBalance($business_id, $branch_id, $user_id, $accountId, $this->allow_roles);
                $ledgerBalance = $balance['balance'];
                $ledgerType = $balance['type'];
                $lastPaymentDate = $this->ledger_query_service->lastPaymentDate($business_id, $branch_id, $user_id, $accountId, $this->allow_roles);
            }

            $bucketValues = [];
            foreach ($this->buckets as $bucket) {
                $bucketValues[$this->bucketKey($bucket)] = round($buckets[$bucket], 2);
            }

            return (object) array_merge([
                'user_id'            => $user_id,
                'customer_name'      => $first->customer_name,
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

        $totals = [
            'grand_total_outstanding' => currency(round($rows->sum('total_outstanding'), 2)),
        ];

        $dt = DataTables::of($rows)
            ->addColumn('customer_name', fn ($row) => $row->customer_name)
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
            $dt->addColumn($key, function ($row) use ($key) {
                if ($row->$key <= 0) {
                    return '';
                }

                $url = url('admin/reports/customer-ledger') . '?' . http_build_query([
                    'user_id' => $row->user_id,
                ]);

                return '<a href="' . $url . '" target="_blank">' . currency($row->$key) . '</a>';
            });
        }

        return $dt->rawColumns(array_merge(['total_balance'], array_map(fn ($b) => $this->bucketKey($b), $this->buckets)))
            ->with($totals)
            ->make(true);
    }
}
