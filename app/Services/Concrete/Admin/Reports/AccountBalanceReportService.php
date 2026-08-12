<?php

namespace App\Services\Concrete\Admin\Reports;

use App\Enums\RoleNames;
use App\Enums\Status;
use App\Models\Account;
use App\Services\Concrete\Admin\Reports\Accounting\AccountClassifier;
use App\Services\Concrete\Admin\Reports\Accounting\AccountingLedgerQueryService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;

/**
 * One row per account: opening balance, period debit/credit, closing
 * balance - presented as a single net balance + Dr/Cr indicator (like
 * SupplierLedgerQueryService::toBalance()) rather than Trial Balance's
 * split debit/credit columns. A lighter "what does each account currently
 * hold/owe" view over the same underlying figures as Trial Balance.
 */
class AccountBalanceReportService
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
            ->where('is_deleted', 0)
            ->where('status', Status::ACTIVE);

        if (!empty($business_id)) {
            $accountsQuery->where('business_id', $business_id);
        }
        if (!empty($obj['account_type_id'])) {
            $accountsQuery->where('account_type_id', $obj['account_type_id']);
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
        $includeZero = !empty($obj['include_zero']);

        $openingMap = $this->ledger_query_service->openingBalances($filters, $from);
        $periodMap = $this->ledger_query_service->periodMovements($filters, $from, $to);

        $rows = collect();

        foreach ($accounts as $account) {
            $debitNormal = $this->classifier->isDebitNormal(optional($account->accountType)->name);
            $openingTotals = $openingMap[$account->account_id] ?? ['debit' => 0, 'credit' => 0];
            $period = $periodMap[$account->account_id] ?? ['debit' => 0, 'credit' => 0];

            $opening = $this->classifier->toBalance($openingTotals['debit'], $openingTotals['credit'], $debitNormal);
            $closingRaw = $debitNormal
                ? $opening['raw'] + $period['debit'] - $period['credit']
                : $opening['raw'] + $period['credit'] - $period['debit'];
            $closing = $this->classifier->toBalance(0, 0, $debitNormal, $closingRaw);

            if (!$includeZero && abs($opening['raw']) < 0.009 && abs($period['debit']) < 0.009 && abs($period['credit']) < 0.009) {
                continue;
            }

            $rows->push((object) [
                'account_id'           => $account->account_id,
                'account_code'         => $account->code,
                'account_name'         => $account->name,
                'account_type'         => optional($account->accountType)->name,
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
        ];

        return DataTables::of($rows)
            ->addColumn('account', fn ($row) => trim($row->account_code . ' ' . $row->account_name))
            ->addColumn('opening_balance', fn ($row) => currency($row->opening_balance) . ' ' . $row->opening_balance_type)
            ->addColumn('period_debit', fn ($row) => $row->period_debit > 0 ? currency($row->period_debit) : '')
            ->addColumn('period_credit', fn ($row) => $row->period_credit > 0 ? currency($row->period_credit) : '')
            ->addColumn('closing_balance', fn ($row) => currency($row->closing_balance) . ' ' . $row->closing_balance_type)
            ->with($totals)
            ->make(true);
    }
}
