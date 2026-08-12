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
 * One row per account: opening Dr/Cr, period Dr/Cr, closing Dr/Cr - the
 * classic double-column Trial Balance. Totals always reconcile because
 * debit == credit is already enforced when a Journal Entry is saved
 * (see JournalEntryService/JournalEntryController), so summing every
 * account's closing debit/credit column here can never go out of balance.
 */
class TrialBalanceReportService
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
            $opening = $openingMap[$account->account_id] ?? ['debit' => 0, 'credit' => 0];
            $period = $periodMap[$account->account_id] ?? ['debit' => 0, 'credit' => 0];

            $openingNet = $opening['debit'] - $opening['credit'];
            $closingNet = $openingNet + $period['debit'] - $period['credit'];

            if (!$includeZero && abs($openingNet) < 0.009 && abs($period['debit']) < 0.009 && abs($period['credit']) < 0.009) {
                continue;
            }

            $openingSplit = $this->classifier->splitByNet($openingNet);
            $closingSplit = $this->classifier->splitByNet($closingNet);

            $rows->push((object) [
                'account_id'      => $account->account_id,
                'account_code'    => $account->code,
                'account_name'    => $account->name,
                'account_type'    => optional($account->accountType)->name,
                'opening_debit'   => round($openingSplit['debit'], 2),
                'opening_credit'  => round($openingSplit['credit'], 2),
                'period_debit'    => round($period['debit'], 2),
                'period_credit'   => round($period['credit'], 2),
                'closing_debit'   => round($closingSplit['debit'], 2),
                'closing_credit'  => round($closingSplit['credit'], 2),
            ]);
        }

        return $rows;
    }

    public function getData(array $obj)
    {
        $rows = $this->build($obj);

        $totals = [
            'total_opening_debit'  => currency(round($rows->sum('opening_debit'), 2)),
            'total_opening_credit' => currency(round($rows->sum('opening_credit'), 2)),
            'total_period_debit'   => currency(round($rows->sum('period_debit'), 2)),
            'total_period_credit'  => currency(round($rows->sum('period_credit'), 2)),
            'total_closing_debit'  => currency(round($rows->sum('closing_debit'), 2)),
            'total_closing_credit' => currency(round($rows->sum('closing_credit'), 2)),
        ];

        return DataTables::of($rows)
            ->addColumn('account', fn ($row) => trim($row->account_code . ' ' . $row->account_name))
            ->addColumn('account_type', fn ($row) => $row->account_type)
            ->addColumn('opening_debit', fn ($row) => $row->opening_debit > 0 ? currency($row->opening_debit) : '')
            ->addColumn('opening_credit', fn ($row) => $row->opening_credit > 0 ? currency($row->opening_credit) : '')
            ->addColumn('period_debit', fn ($row) => $row->period_debit > 0 ? currency($row->period_debit) : '')
            ->addColumn('period_credit', fn ($row) => $row->period_credit > 0 ? currency($row->period_credit) : '')
            ->addColumn('closing_debit', fn ($row) => $row->closing_debit > 0 ? currency($row->closing_debit) : '')
            ->addColumn('closing_credit', fn ($row) => $row->closing_credit > 0 ? currency($row->closing_credit) : '')
            ->with($totals)
            ->make(true);
    }
}
