<?php

namespace App\Services\Concrete\Admin\Reports;

use App\Enums\RoleNames;
use App\Models\Account;
use App\Services\Concrete\Admin\Reports\Accounting\AccountClassifier;
use App\Services\Concrete\Admin\Reports\Accounting\AccountingLedgerQueryService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;

/**
 * Full transaction history and running balance of one selected Chart of
 * Account, read entirely from posted journal_entry_details. Generalizes
 * SupplierLedgerReportService (which is pinned to a supplier's control
 * account) to any account in the Chart of Accounts.
 */
class AccountLedgerReportService
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

    /**
     * Builds the full ledger dataset (opening balance, ordered transactions
     * with a running balance, closing balance) for one account. Shared by
     * the DataTable, print, PDF and export actions so they never diverge.
     */
    public function build(array $obj): array
    {
        $account_id = $obj['account_id'] ?? null;

        if (empty($account_id)) {
            return $this->emptyResult();
        }

        $account = Account::with(['accountType', 'accountSubType'])->find($account_id);

        if (!$account) {
            return $this->emptyResult();
        }

        $business_id = $obj['business_id'] ?? Auth::user()->business_id;
        $branch_id = $obj['branch_id'] ?? null;

        $debitNormal = $this->classifier->isDebitNormal(optional($account->accountType)->code);

        $filters = [
            'business_id' => $business_id,
            'branch_id'   => $branch_id,
            'account_id'  => $account_id,
            'allow_roles' => $this->allow_roles,
        ];

        $from = !empty($obj['start_date']) ? Carbon::parse($obj['start_date'])->startOfDay() : null;
        $to = !empty($obj['end_date']) ? Carbon::parse($obj['end_date'])->endOfDay() : null;

        $openingTotals = $this->ledger_query_service->openingBalances($filters, $from)[$account_id] ?? ['debit' => 0, 'credit' => 0];
        $opening = $this->classifier->toBalance($openingTotals['debit'], $openingTotals['credit'], $debitNormal);

        $rows = $this->ledger_query_service->transactions($filters, $from, $to);
        $rows = $this->ledger_query_service->withRunningBalance($rows, $opening['raw'], $debitNormal);

        $totalDebit = round((float) $rows->sum('debit'), 2);
        $totalCredit = round((float) $rows->sum('credit'), 2);
        $closingRaw = $debitNormal
            ? $opening['raw'] + $totalDebit - $totalCredit
            : $opening['raw'] + $totalCredit - $totalDebit;
        $closing = $this->classifier->toBalance(0, 0, $debitNormal, $closingRaw);

        return [
            'account'               => $account,
            'debit_normal'          => $debitNormal,
            'opening_balance'       => $opening['balance'],
            'opening_balance_type'  => $opening['type'],
            'closing_balance'       => $closing['balance'],
            'closing_balance_type'  => $closing['type'],
            'total_debit'           => $totalDebit,
            'total_credit'          => $totalCredit,
            'start_date'            => $from,
            'end_date'              => $to,
            'rows'                  => $rows,
        ];
    }

    public function getData(array $obj)
    {
        $result = $this->build($obj);

        return DataTables::of($result['rows'])
            ->addColumn('voucher_date', fn ($row) => localDate($row->entry_date))
            ->addColumn('voucher_type', fn ($row) => $row->voucher_name ?? $row->source_type ?? '')
            ->addColumn('voucher_number', fn ($row) => $row->entry_no)
            ->addColumn('reference_number', fn ($row) => $row->reference_no)
            ->addColumn('description', fn ($row) => $row->detail_description ?: $row->entry_description)
            ->addColumn('debit', fn ($row) => $row->debit > 0 ? currency($row->debit) : '')
            ->addColumn('credit', fn ($row) => $row->credit > 0 ? currency($row->credit) : '')
            ->addColumn('running_balance', fn ($row) => currency($row->running_balance) . ' ' . $row->running_balance_type)
            ->with([
                'opening_balance' => currency($result['opening_balance']) . ' ' . $result['opening_balance_type'],
                'closing_balance' => currency($result['closing_balance']) . ' ' . $result['closing_balance_type'],
                'total_debit'     => currency($result['total_debit']),
                'total_credit'    => currency($result['total_credit']),
            ])
            ->make(true);
    }

    protected function emptyResult(): array
    {
        return [
            'account'              => null,
            'debit_normal'         => true,
            'opening_balance'      => 0,
            'opening_balance_type' => '',
            'closing_balance'      => 0,
            'closing_balance_type' => '',
            'total_debit'          => 0,
            'total_credit'         => 0,
            'start_date'           => null,
            'end_date'             => null,
            'rows'                 => collect(),
        ];
    }
}
