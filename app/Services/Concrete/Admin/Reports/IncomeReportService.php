<?php

namespace App\Services\Concrete\Admin\Reports;

use App\Enums\AccountTypes;
use App\Enums\RoleNames;
use App\Enums\Status;
use App\Models\Account;
use App\Services\Concrete\Admin\Reports\Accounting\AccountingLedgerQueryService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;

/**
 * Account-wise summary of every Revenue-type Chart of Accounts entry for a
 * period - business/branch scoped like every other report in this module,
 * read entirely from posted journal_entry_details.
 */
class IncomeReportService
{
    protected array $allow_roles = [
        RoleNames::SUPERADMIN,
        RoleNames::BUSINESSADMIN,
        RoleNames::FINANCEMANAGER,
        RoleNames::ACCOUNTANT,
        RoleNames::REPORTINGANALYST,
    ];

    public function __construct(protected AccountingLedgerQueryService $ledger_query_service)
    {
    }

    public function build(array $obj)
    {
        $business_id = $obj['business_id'] ?? Auth::user()->business_id;
        $branch_id = $obj['branch_id'] ?? null;

        $accountsQuery = Account::with(['accountType', 'accountSubType'])
            ->whereHas('accountType', fn ($q) => $q->where('name', AccountTypes::REVENUE))
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
        $includeZero = !empty($obj['include_zero']);

        $periodMap = $this->ledger_query_service->periodMovements($filters, $from, $to);

        $rows = collect();

        foreach ($accounts as $account) {
            $period = $periodMap[$account->account_id] ?? ['debit' => 0, 'credit' => 0];
            $netAmount = round($period['credit'] - $period['debit'], 2);

            if (!$includeZero && abs($period['debit']) < 0.009 && abs($period['credit']) < 0.009) {
                continue;
            }

            $rows->push((object) [
                'account_id'      => $account->account_id,
                'account_code'    => $account->code,
                'account_name'    => $account->name,
                'account_subtype' => optional($account->accountSubType)->name,
                'period_debit'    => round($period['debit'], 2),
                'period_credit'   => round($period['credit'], 2),
                'net_amount'      => $netAmount,
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
            'total_net_amount'    => currency(round($rows->sum('net_amount'), 2)),
        ];

        return DataTables::of($rows)
            ->addColumn('account', fn ($row) => trim($row->account_code . ' ' . $row->account_name))
            ->addColumn('account_subtype', fn ($row) => $row->account_subtype)
            ->addColumn('period_debit', fn ($row) => $row->period_debit > 0 ? currency($row->period_debit) : '')
            ->addColumn('period_credit', fn ($row) => $row->period_credit > 0 ? currency($row->period_credit) : '')
            ->addColumn('net_amount', fn ($row) => currency($row->net_amount))
            ->with($totals)
            ->make(true);
    }
}
