<?php

namespace App\Services\Concrete\Admin\Reports;

use App\Enums\AccountSubTypes;
use App\Enums\AccountTypes;
use App\Enums\RoleNames;
use App\Enums\Status;
use App\Models\Account;
use App\Services\Concrete\Admin\Reports\Accounting\AccountClassifier;
use App\Services\Concrete\Admin\Reports\Accounting\AccountingLedgerQueryService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * Balance Sheet: Assets, Liabilities and Equity as of a given date, computed
 * from posted journal_entry_details balances (cumulative since inception,
 * not period-bound). Current-year earnings are only injected as a synthetic
 * Equity line when the "Current Year Earnings" account has no posted
 * activity for the period - i.e. no explicit closing entry exists yet - so
 * real posted data is never double-counted or overridden.
 */
class BalanceSheetReportService
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
        protected AccountClassifier $classifier,
        protected ProfitLossReportService $profit_loss_report_service
    ) {
    }

    public function build(array $obj): array
    {
        $business_id = $obj['business_id'] ?? Auth::user()->business_id;
        $branch_id = $obj['branch_id'] ?? null;

        $asOf = !empty($obj['as_of_date']) ? Carbon::parse($obj['as_of_date'])->endOfDay() : Carbon::today()->endOfDay();

        $accountsQuery = Account::with(['accountType', 'accountSubType'])
            ->whereHas('accountType', fn ($q) => $q->whereIn('code', [
                AccountTypes::CODES[AccountTypes::ASSETS],
                AccountTypes::CODES[AccountTypes::LIABILITIES],
                AccountTypes::CODES[AccountTypes::EQUITY],
            ]))
            ->where('is_deleted', 0)
            ->where('status', Status::ACTIVE);

        if (!empty($business_id)) {
            $accountsQuery->where('business_id', $business_id);
        }

        $accounts = $accountsQuery->orderBy('code')->get();

        $assetBuckets = ['current_asset' => collect(), 'fixed_asset' => collect(), 'other_asset' => collect()];
        $liabilityBuckets = ['current_liability' => collect(), 'long_term_liability' => collect(), 'other_liability' => collect()];
        $equityRows = collect();
        $cyeHasPostedActivity = false;

        if ($accounts->isNotEmpty()) {
            $filters = [
                'business_id' => $business_id,
                'branch_id'   => $branch_id,
                'account_ids' => $accounts->pluck('account_id')->all(),
                'allow_roles' => $this->allow_roles,
            ];

            $totalMap = $this->ledger_query_service->totalBalances($filters, $asOf);

            foreach ($accounts as $account) {
                $typeCode = optional($account->accountType)->code;
                $subTypeCode = optional($account->accountSubType)->code;
                $subTypeName = optional($account->accountSubType)->name;
                $debitNormal = $this->classifier->isDebitNormal($typeCode);
                $totals = $totalMap[$account->account_id] ?? ['debit' => 0, 'credit' => 0];
                $amount = round($debitNormal ? $totals['debit'] - $totals['credit'] : $totals['credit'] - $totals['debit'], 2);

                if ($subTypeCode === AccountSubTypes::CODES[AccountSubTypes::CURRENT_YEAR_EARNINGS] && (abs($totals['debit']) > 0.009 || abs($totals['credit']) > 0.009)) {
                    $cyeHasPostedActivity = true;
                }

                if (abs($amount) < 0.009) {
                    continue;
                }

                $row = (object) [
                    'account_code'    => $account->code,
                    'account_name'    => $account->name,
                    'account_subtype' => $subTypeName,
                    'amount'          => $amount,
                ];

                if ($typeCode === AccountTypes::CODES[AccountTypes::ASSETS]) {
                    $assetBuckets[$this->classifier->bsBucket($typeCode, $subTypeCode)]->push($row);
                } elseif ($typeCode === AccountTypes::CODES[AccountTypes::LIABILITIES]) {
                    $liabilityBuckets[$this->classifier->bsBucket($typeCode, $subTypeCode)]->push($row);
                } else {
                    $equityRows->push($row);
                }
            }
        }

        $computedCurrentYearEarnings = 0;

        if (!$cyeHasPostedActivity) {
            $plResult = $this->profit_loss_report_service->build([
                'business_id' => $business_id,
                'branch_id'   => $branch_id,
                'start_date'  => $this->fiscalYearStart($asOf)->format('Y-m-d'),
                'end_date'    => $asOf->format('Y-m-d'),
            ]);

            $computedCurrentYearEarnings = $plResult['net_profit'];

            if (abs($computedCurrentYearEarnings) > 0.009) {
                $equityRows->push((object) [
                    'account_code'    => '',
                    'account_name'    => 'Current Year Earnings (Unposted)',
                    'account_subtype' => AccountSubTypes::CURRENT_YEAR_EARNINGS,
                    'amount'          => round($computedCurrentYearEarnings, 2),
                ]);
            }
        }

        $totalCurrentAssets = round($assetBuckets['current_asset']->sum('amount'), 2);
        $totalFixedAssets = round($assetBuckets['fixed_asset']->sum('amount'), 2);
        $totalOtherAssets = round($assetBuckets['other_asset']->sum('amount'), 2);
        $totalAssets = round($totalCurrentAssets + $totalFixedAssets + $totalOtherAssets, 2);

        $totalCurrentLiabilities = round($liabilityBuckets['current_liability']->sum('amount'), 2);
        $totalLongTermLiabilities = round($liabilityBuckets['long_term_liability']->sum('amount'), 2);
        $totalOtherLiabilities = round($liabilityBuckets['other_liability']->sum('amount'), 2);
        $totalLiabilities = round($totalCurrentLiabilities + $totalLongTermLiabilities + $totalOtherLiabilities, 2);

        $totalEquity = round($equityRows->sum('amount'), 2);

        return [
            'as_of_date'                 => $asOf,
            'current_assets'             => $assetBuckets['current_asset'],
            'total_current_assets'       => $totalCurrentAssets,
            'fixed_assets'               => $assetBuckets['fixed_asset'],
            'total_fixed_assets'         => $totalFixedAssets,
            'other_assets'               => $assetBuckets['other_asset'],
            'total_other_assets'         => $totalOtherAssets,
            'total_assets'               => $totalAssets,
            'current_liabilities'        => $liabilityBuckets['current_liability'],
            'total_current_liabilities'  => $totalCurrentLiabilities,
            'long_term_liabilities'      => $liabilityBuckets['long_term_liability'],
            'total_long_term_liabilities' => $totalLongTermLiabilities,
            'other_liabilities'          => $liabilityBuckets['other_liability'],
            'total_other_liabilities'    => $totalOtherLiabilities,
            'total_liabilities'          => $totalLiabilities,
            'equity'                     => $equityRows,
            'total_equity'               => $totalEquity,
            'total_liabilities_and_equity' => round($totalLiabilities + $totalEquity, 2),
        ];
    }

    protected function fiscalYearStart(Carbon $asOf): Carbon
    {
        return $asOf->month >= 7
            ? Carbon::create($asOf->year, 7, 1)->startOfDay()
            : Carbon::create($asOf->year - 1, 7, 1)->startOfDay();
    }
}
