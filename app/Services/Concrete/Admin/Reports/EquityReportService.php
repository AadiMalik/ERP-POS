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
use Yajra\DataTables\DataTables;

/**
 * Summary of every Equity-type Chart of Accounts entry (Capital, Retained
 * Earnings, Current Year Earnings, Drawings, Reserves) and their movements -
 * opening balance, period debit/credit, closing balance - read entirely
 * from posted journal_entry_details.
 */
class EquityReportService
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

    public function build(array $obj)
    {
        $business_id = $obj['business_id'] ?? Auth::user()->business_id;
        $branch_id = $obj['branch_id'] ?? null;

        $accountsQuery = Account::with(['accountType', 'accountSubType'])
            ->whereHas('accountType', fn ($q) => $q->where('code', AccountTypes::CODES[AccountTypes::EQUITY]))
            ->where('is_deleted', 0)
            ->where('status', Status::ACTIVE);

        if (!empty($business_id)) {
            $accountsQuery->where('business_id', $business_id);
        }
        if (!empty($obj['account_id'])) {
            $accountsQuery->where('account_id', $obj['account_id']);
        }

        $accounts = $accountsQuery->orderBy('code')->get();

        if ($accounts->isEmpty()) {
            return collect();
        }

        $filters = [
            'business_id' => $business_id,
            'branch_id'   => $branch_id,
            'account_ids' => $accounts->pluck('account_id')->all(),
            'allow_roles' => $this->allow_roles,
        ];

        $from = !empty($obj['start_date']) ? Carbon::parse($obj['start_date'])->startOfDay() : null;
        $to = !empty($obj['end_date']) ? Carbon::parse($obj['end_date'])->endOfDay() : null;

        $openingMap = $this->ledger_query_service->openingBalances($filters, $from);
        $periodMap = $this->ledger_query_service->periodMovements($filters, $from, $to);

        $rows = collect();

        foreach ($accounts as $account) {
            // Equity is always credit-normal.
            $debitNormal = false;
            $openingTotals = $openingMap[$account->account_id] ?? ['debit' => 0, 'credit' => 0];
            $period = $periodMap[$account->account_id] ?? ['debit' => 0, 'credit' => 0];

            $opening = $this->classifier->toBalance($openingTotals['debit'], $openingTotals['credit'], $debitNormal);
            $closingRaw = $opening['raw'] + $period['credit'] - $period['debit'];
            $closing = $this->classifier->toBalance(0, 0, $debitNormal, $closingRaw);

            $rows->push((object) [
                'account_id'           => $account->account_id,
                'account_code'         => $account->code,
                'account_name'         => $account->name,
                'account_subtype'      => optional($account->accountSubType)->name,
                'opening_balance'      => $opening['balance'],
                'opening_balance_type' => $opening['type'],
                'period_debit'         => round($period['debit'], 2),
                'period_credit'        => round($period['credit'], 2),
                'closing_balance'      => $closing['balance'],
                'closing_balance_type' => $closing['type'],
            ]);
        }

        return $rows;
    }

    public function getData(array $obj)
    {
        $rows = $this->build($obj);

        $totals = [
            'total_period_debit'  => currency(round($rows->sum('period_debit'), 2)),
            'total_period_credit' => currency(round($rows->sum('period_credit'), 2)),
            'total_closing'       => currency(round($rows->sum('closing_balance'), 2)),
        ];

        return DataTables::of($rows)
            ->addColumn('account', fn ($row) => trim($row->account_code . ' ' . $row->account_name))
            ->addColumn('account_subtype', fn ($row) => $row->account_subtype)
            ->addColumn('opening_balance', fn ($row) => currency($row->opening_balance) . ' ' . $row->opening_balance_type)
            ->addColumn('period_debit', fn ($row) => $row->period_debit > 0 ? currency($row->period_debit) : '')
            ->addColumn('period_credit', fn ($row) => $row->period_credit > 0 ? currency($row->period_credit) : '')
            ->addColumn('closing_balance', fn ($row) => currency($row->closing_balance) . ' ' . $row->closing_balance_type)
            ->with($totals)
            ->make(true);
    }
}
