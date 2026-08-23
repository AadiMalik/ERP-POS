<?php

namespace App\Services\Concrete\Admin\Reports;

use App\Enums\AccountTypes;
use App\Enums\RoleNames;
use App\Enums\Status;
use App\Models\Account;
use App\Services\Concrete\Admin\Reports\Accounting\AccountClassifier;
use App\Services\Concrete\Admin\Reports\Accounting\AccountingLedgerQueryService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * Profit & Loss statement: Revenue, Cost of Revenue, Direct Expenses,
 * Operating Expenses, Other Income/Expense and Net Profit/Loss, computed
 * directly from posted journal_entry_details and classified via
 * AccountClassifier::plBucket() (driven by each account's sub-type).
 *
 * Unlike the other reports in this module, this renders as a structured
 * financial statement (nested sections with subtotals) rather than a flat
 * DataTable - the natural shape for a P&L, which every ERP presents this
 * way rather than as a paginated row-per-account grid.
 */
class ProfitLossReportService
{
    protected array $allow_roles = [
        RoleNames::SUPERADMIN,
        RoleNames::BUSINESSADMIN,
        RoleNames::FINANCEMANAGER,
        RoleNames::ACCOUNTANT,
        RoleNames::REPORTINGANALYST,
    ];

    public function __construct(
        protected AccountingLedgerQueryService $ledger_query_service,
        protected AccountClassifier $classifier
    ) {
    }

    public function build(array $obj): array
    {
        $business_id = $obj['business_id'] ?? Auth::user()->business_id;
        $branch_id = $obj['branch_id'] ?? null;

        $from = !empty($obj['start_date']) ? Carbon::parse($obj['start_date'])->startOfDay() : $this->fiscalYearStart();
        $to = !empty($obj['end_date']) ? Carbon::parse($obj['end_date'])->endOfDay() : Carbon::today()->endOfDay();

        $accountsQuery = Account::with(['accountType', 'accountSubType'])
            ->whereHas('accountType', fn ($q) => $q->whereIn('code', [
                AccountTypes::CODES[AccountTypes::REVENUE],
                AccountTypes::CODES[AccountTypes::EXPENSES],
            ]))
            ->where('is_deleted', 0)
            ->where('status', Status::ACTIVE);

        if (!empty($business_id)) {
            $accountsQuery->where('business_id', $business_id);
        }

        $accounts = $accountsQuery->orderBy('code')->get();

        $buckets = [
            'revenue'           => collect(),
            'cost_of_revenue'   => collect(),
            'direct_expense'    => collect(),
            'operating_expense' => collect(),
            'other_income'      => collect(),
            'other_expense'     => collect(),
        ];

        if ($accounts->isNotEmpty()) {
            $filters = [
                'business_id' => $business_id,
                'branch_id'   => $branch_id,
                'account_ids' => $accounts->pluck('account_id')->all(),
                'allow_roles' => $this->allow_roles,
            ];

            $periodMap = $this->ledger_query_service->periodMovements($filters, $from, $to);

            foreach ($accounts as $account) {
                $bucket = $this->classifier->plBucket(optional($account->accountSubType)->code);

                if (!$bucket) {
                    continue;
                }

                $period = $periodMap[$account->account_id] ?? ['debit' => 0, 'credit' => 0];

                if (abs($period['debit']) < 0.009 && abs($period['credit']) < 0.009) {
                    continue;
                }

                $isCreditBucket = in_array($bucket, ['revenue', 'other_income'], true);
                $amount = round($isCreditBucket ? $period['credit'] - $period['debit'] : $period['debit'] - $period['credit'], 2);

                $buckets[$bucket]->push((object) [
                    'account_code' => $account->code,
                    'account_name' => $account->name,
                    'amount'       => $amount,
                ]);
            }
        }

        $totalRevenue = round($buckets['revenue']->sum('amount'), 2);
        $totalCostOfRevenue = round($buckets['cost_of_revenue']->sum('amount'), 2);
        $grossProfit = round($totalRevenue - $totalCostOfRevenue, 2);
        $totalDirectExpense = round($buckets['direct_expense']->sum('amount'), 2);
        $totalOperatingExpense = round($buckets['operating_expense']->sum('amount'), 2);
        $operatingProfit = round($grossProfit - $totalDirectExpense - $totalOperatingExpense, 2);
        $totalOtherIncome = round($buckets['other_income']->sum('amount'), 2);
        $totalOtherExpense = round($buckets['other_expense']->sum('amount'), 2);
        $netProfit = round($operatingProfit + $totalOtherIncome - $totalOtherExpense, 2);

        return [
            'start_date'              => $from,
            'end_date'                => $to,
            'revenue'                 => $buckets['revenue'],
            'total_revenue'           => $totalRevenue,
            'cost_of_revenue'         => $buckets['cost_of_revenue'],
            'total_cost_of_revenue'   => $totalCostOfRevenue,
            'gross_profit'            => $grossProfit,
            'direct_expense'          => $buckets['direct_expense'],
            'total_direct_expense'    => $totalDirectExpense,
            'operating_expense'       => $buckets['operating_expense'],
            'total_operating_expense' => $totalOperatingExpense,
            'operating_profit'        => $operatingProfit,
            'other_income'            => $buckets['other_income'],
            'total_other_income'      => $totalOtherIncome,
            'other_expense'           => $buckets['other_expense'],
            'total_other_expense'     => $totalOtherExpense,
            'net_profit'              => $netProfit,
        ];
    }

    /**
     * Start of the current financial year (Jul 1 - Jun 30), matching the
     * fixed financial-year convention already used by the date filter
     * (public/assets/js/admin/global-date-filter.js).
     */
    protected function fiscalYearStart(): Carbon
    {
        $today = Carbon::today();

        return $today->month >= 7
            ? Carbon::create($today->year, 7, 1)->startOfDay()
            : Carbon::create($today->year - 1, 7, 1)->startOfDay();
    }
}
