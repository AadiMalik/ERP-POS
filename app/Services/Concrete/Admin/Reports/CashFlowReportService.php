<?php

namespace App\Services\Concrete\Admin\Reports;

use App\Enums\RoleNames;
use App\Enums\Status;
use App\Models\Account;
use App\Models\AccountingSetting;
use App\Models\JournalEntryDetail;
use App\Services\Concrete\Admin\Reports\Accounting\AccountClassifier;
use App\Services\Concrete\Admin\Reports\Accounting\AccountingLedgerQueryService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * Direct-method Cash Flow Statement built from posted journal_entry_details.
 *
 * Cash/bank accounts are identified via AccountClassifier::isCashOrBank()
 * (Cash & Cash Equivalents sub-type plus accounting_settings defaults) —
 * never by hard-coded account IDs. Period cash movements are attributed to
 * Operating / Investing / Financing by the counterparty (non-cash) account's
 * type/sub-type via AccountClassifier::cashFlowBucket(). Pure cash↔cash
 * transfers are excluded so they do not inflate activity totals.
 *
 * Reconciliation: Opening + Net Operating + Net Investing + Net Financing
 * must equal Closing (within rounding).
 */
class CashFlowReportService
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

        $cashAccounts = $this->resolveCashAccounts($business_id);

        if ($cashAccounts->isEmpty()) {
            return $this->emptyResult($from, $to);
        }

        $cashIds = $cashAccounts->pluck('account_id')->all();
        $filters = [
            'business_id' => $business_id,
            'branch_id'   => $branch_id,
            'account_ids' => $cashIds,
            'allow_roles' => $this->allow_roles,
        ];

        $openingCash = $this->sumCashBalance(
            $this->ledger_query_service->openingBalances($filters, $from)
        );
        $closingCash = $this->sumCashBalance(
            $this->ledger_query_service->totalBalances($filters, $to)
        );

        $sections = $this->classifyPeriodMovements($filters, $cashIds, $from, $to);

        $netOperating = round($sections['operating']->sum('amount'), 2);
        $netInvesting = round($sections['investing']->sum('amount'), 2);
        $netFinancing = round($sections['financing']->sum('amount'), 2);
        $netIncrease = round($netOperating + $netInvesting + $netFinancing, 2);
        $reconciledClosing = round($openingCash + $netIncrease, 2);
        $difference = round($reconciledClosing - $closingCash, 2);

        $totalInflows = round(
            $sections['operating']->sum(fn ($r) => max(0, $r->amount))
            + $sections['investing']->sum(fn ($r) => max(0, $r->amount))
            + $sections['financing']->sum(fn ($r) => max(0, $r->amount)),
            2
        );
        $totalOutflows = round(
            abs($sections['operating']->sum(fn ($r) => min(0, $r->amount)))
            + abs($sections['investing']->sum(fn ($r) => min(0, $r->amount)))
            + abs($sections['financing']->sum(fn ($r) => min(0, $r->amount))),
            2
        );

        return [
            'start_date'            => $from,
            'end_date'              => $to,
            'cash_accounts'         => $cashAccounts->map(fn ($a) => (object) [
                'account_code' => $a->code,
                'account_name' => $a->name,
            ]),
            'cash_accounts_count'   => $cashAccounts->count(),
            'operating'             => $sections['operating'],
            'investing'             => $sections['investing'],
            'financing'             => $sections['financing'],
            'net_operating'         => $netOperating,
            'net_investing'         => $netInvesting,
            'net_financing'         => $netFinancing,
            'net_increase'          => $netIncrease,
            'total_inflows'         => $totalInflows,
            'total_outflows'        => $totalOutflows,
            'opening_cash'          => $openingCash,
            'closing_cash'          => $closingCash,
            'reconciled_closing'    => $reconciledClosing,
            'reconciliation_difference' => $difference,
        ];
    }

    /**
     * For every journal entry that touches a cash/bank account in the period,
     * attribute the cash effect of each non-cash counterparty line to an
     * O/I/F bucket. Identity: cash_net = -Σ non_cash (debit - credit), so
     * each cash rupee is counted once.
     */
    protected function classifyPeriodMovements(array $filters, array $cashIds, Carbon $from, Carbon $to): array
    {
        $aggregates = [
            'operating' => [],
            'investing' => [],
            'financing' => [],
        ];

        $cashJeIds = $this->ledger_query_service->baseQuery($filters)
            ->where('journal_entries.entry_date', '>=', $from)
            ->where('journal_entries.entry_date', '<=', $to)
            ->distinct()
            ->pluck('journal_entries.journal_entry_id');

        if ($cashJeIds->isEmpty()) {
            return [
                'operating' => collect(),
                'investing' => collect(),
                'financing' => collect(),
            ];
        }

        $cashIdSet = array_flip($cashIds);

        $lines = JournalEntryDetail::query()
            ->join('journal_entries', 'journal_entries.journal_entry_id', '=', 'journal_entry_details.journal_entry_id')
            ->join('accounts', 'accounts.account_id', '=', 'journal_entry_details.account_id')
            ->leftJoin('account_types', 'account_types.account_type_id', '=', 'accounts.account_type_id')
            ->leftJoin('account_sub_types', 'account_sub_types.account_sub_type_id', '=', 'accounts.account_sub_type_id')
            ->whereIn('journal_entries.journal_entry_id', $cashJeIds->all())
            ->where('journal_entries.is_deleted', 0)
            ->where('journal_entries.status', Status::POSTED)
            ->when(!empty($filters['business_id']), fn ($q) => $q->where('journal_entries.business_id', $filters['business_id']))
            ->when(!empty($filters['branch_id']), fn ($q) => $q->where('journal_entries.branch_id', $filters['branch_id']))
            ->tap(fn ($q) => applyRoleScope($q, $filters['allow_roles'] ?? [], 'journal_entries.business_id', 'journal_entries.branch_id'))
            ->get([
                'journal_entry_details.journal_entry_id',
                'journal_entry_details.account_id',
                'journal_entry_details.debit',
                'journal_entry_details.credit',
                'account_types.code as account_type_code',
                'account_sub_types.code as account_sub_type_code',
            ])
            ->groupBy('journal_entry_id');

        foreach ($lines as $jeLines) {
            $nonCashLines = $jeLines->filter(fn ($line) => !isset($cashIdSet[$line->account_id]));

            // Cash↔cash only (bank transfer / deposit between cash accounts):
            // net cash across the cash universe is zero — skip entirely.
            if ($nonCashLines->isEmpty()) {
                continue;
            }

            foreach ($nonCashLines as $line) {
                // Balanced JE identity: cash effect of a non-cash line is the
                // opposite of that line's (debit - credit).
                $cashEffect = round((float) $line->credit - (float) $line->debit, 2);

                if (abs($cashEffect) < 0.009) {
                    continue;
                }

                $bucket = $this->classifier->cashFlowBucket(
                    $line->account_type_code,
                    $line->account_sub_type_code
                );

                if (!$bucket) {
                    continue;
                }

                $section = $bucket['section'];
                $key = $bucket['key'];

                if (!isset($aggregates[$section][$key])) {
                    $aggregates[$section][$key] = [
                        'label'   => $bucket['label'],
                        'inflow'  => 0.0,
                        'outflow' => 0.0,
                        'amount'  => 0.0,
                    ];
                }

                $aggregates[$section][$key]['amount'] = round(
                    $aggregates[$section][$key]['amount'] + $cashEffect,
                    2
                );

                if ($cashEffect > 0) {
                    $aggregates[$section][$key]['inflow'] = round(
                        $aggregates[$section][$key]['inflow'] + $cashEffect,
                        2
                    );
                } else {
                    $aggregates[$section][$key]['outflow'] = round(
                        $aggregates[$section][$key]['outflow'] + abs($cashEffect),
                        2
                    );
                }
            }
        }

        return [
            'operating' => $this->toRows($aggregates['operating']),
            'investing' => $this->toRows($aggregates['investing']),
            'financing' => $this->toRows($aggregates['financing']),
        ];
    }

    protected function toRows(array $aggregates): Collection
    {
        return collect($aggregates)
            ->filter(fn ($row) => abs($row['amount']) >= 0.009 || $row['inflow'] >= 0.009 || $row['outflow'] >= 0.009)
            ->map(fn ($row) => (object) [
                'label'   => $row['label'],
                'inflow'  => round($row['inflow'], 2),
                'outflow' => round($row['outflow'], 2),
                'amount'  => round($row['amount'], 2),
            ])
            ->sortBy('label')
            ->values();
    }

    protected function resolveCashAccounts(?string $business_id): Collection
    {
        $settings = $business_id
            ? AccountingSetting::where('business_id', $business_id)->first()
            : null;

        $accountsQuery = Account::with(['accountType', 'accountSubType'])
            ->where('is_deleted', 0)
            ->where('status', Status::ACTIVE);

        if (!empty($business_id)) {
            $accountsQuery->where('business_id', $business_id);
        }

        return $accountsQuery->orderBy('code')->get()
            ->filter(fn ($account) => $this->classifier->isCashOrBank($account, $settings))
            ->values();
    }

    protected function sumCashBalance(array $balanceMap): float
    {
        $total = 0.0;

        foreach ($balanceMap as $totals) {
            // Cash & bank are debit-normal assets.
            $total += (float) $totals['debit'] - (float) $totals['credit'];
        }

        return round($total, 2);
    }

    protected function emptyResult(Carbon $from, Carbon $to): array
    {
        return [
            'start_date'            => $from,
            'end_date'              => $to,
            'cash_accounts'         => collect(),
            'cash_accounts_count'   => 0,
            'operating'             => collect(),
            'investing'             => collect(),
            'financing'             => collect(),
            'net_operating'         => 0.0,
            'net_investing'         => 0.0,
            'net_financing'         => 0.0,
            'net_increase'          => 0.0,
            'total_inflows'         => 0.0,
            'total_outflows'        => 0.0,
            'opening_cash'          => 0.0,
            'closing_cash'          => 0.0,
            'reconciled_closing'    => 0.0,
            'reconciliation_difference' => 0.0,
        ];
    }

    /**
     * Start of the current financial year (Jul 1 - Jun 30), matching the
     * fixed financial-year convention already used by Profit & Loss and the
     * global date filter.
     */
    protected function fiscalYearStart(): Carbon
    {
        $today = Carbon::today();

        return $today->month >= 7
            ? Carbon::create($today->year, 7, 1)->startOfDay()
            : Carbon::create($today->year - 1, 7, 1)->startOfDay();
    }
}
