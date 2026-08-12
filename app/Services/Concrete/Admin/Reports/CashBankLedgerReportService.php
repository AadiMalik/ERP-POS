<?php

namespace App\Services\Concrete\Admin\Reports;

use App\Enums\RoleNames;
use App\Enums\Status;
use App\Models\Account;
use App\Models\AccountingSetting;
use App\Services\Concrete\Admin\Reports\Accounting\AccountClassifier;
use App\Services\Concrete\Admin\Reports\Accounting\AccountingLedgerQueryService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;

/**
 * Ledger restricted to the Cash & Cash Equivalents accounts configured in
 * the Chart of Accounts, plus whichever accounts are set as the business's
 * default cash/bank account in accounting_settings. Same opening/running/
 * closing-balance engine as General Ledger, scoped to that account subset,
 * with Receipts/Payments column labels in place of generic Debit/Credit.
 */
class CashBankLedgerReportService
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

        $settings = AccountingSetting::where('business_id', $business_id)->first();

        $accountsQuery = Account::with(['accountType', 'accountSubType'])
            ->where('is_deleted', 0)
            ->where('status', Status::ACTIVE);

        if (!empty($business_id)) {
            $accountsQuery->where('business_id', $business_id);
        }
        if (!empty($obj['account_id'])) {
            $accountsQuery->where('account_id', $obj['account_id']);
        }

        $accounts = $accountsQuery->orderBy('code')->get()
            ->filter(fn ($account) => $this->classifier->isCashOrBank($account, $settings))
            ->values();

        if ($accounts->isEmpty()) {
            return $this->emptyResult();
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
        $transactions = $this->ledger_query_service->transactions($filters, $from, $to)->groupBy('account_id');

        $rows = collect();
        $grandReceipts = 0;
        $grandPayments = 0;

        foreach ($accounts as $account) {
            $debitNormal = true; // Cash & Bank accounts are Assets - always debit-normal.
            $openingTotals = $openingMap[$account->account_id] ?? ['debit' => 0, 'credit' => 0];
            $opening = $this->classifier->toBalance($openingTotals['debit'], $openingTotals['credit'], $debitNormal);

            $acctRows = $transactions->get($account->account_id, collect());
            $acctRows = $this->ledger_query_service->withRunningBalance($acctRows, $opening['raw'], $debitNormal);

            $totalDebit = round((float) $acctRows->sum('debit'), 2);
            $totalCredit = round((float) $acctRows->sum('credit'), 2);
            $closingRaw = $opening['raw'] + $totalDebit - $totalCredit;
            $closing = $this->classifier->toBalance(0, 0, $debitNormal, $closingRaw);

            $rows->push($this->summaryRow($account, 'Opening Balance', $opening['balance'], $opening['type']));

            foreach ($acctRows as $row) {
                $row->account_code = $account->code;
                $row->account_name = $account->name;
                $rows->push($row);
            }

            $rows->push($this->summaryRow($account, 'Closing Balance', $closing['balance'], $closing['type']));

            $grandReceipts += $totalDebit;
            $grandPayments += $totalCredit;
        }

        return [
            'accounts_count' => $accounts->count(),
            'total_receipts' => round($grandReceipts, 2),
            'total_payments' => round($grandPayments, 2),
            'start_date'     => $from,
            'end_date'       => $to,
            'rows'           => $rows,
        ];
    }

    public function getData(array $obj)
    {
        $result = $this->build($obj);

        return DataTables::of($result['rows'])
            ->addColumn('account', fn ($row) => trim(($row->account_code ?? '') . ' ' . ($row->account_name ?? '')))
            ->addColumn('voucher_date', fn ($row) => $row->entry_date ? localDate($row->entry_date) : '')
            ->addColumn('voucher_type', fn ($row) => $row->voucher_name ?? $row->source_type ?? '')
            ->addColumn('voucher_number', fn ($row) => $row->entry_no)
            ->addColumn('reference_number', fn ($row) => $row->reference_no)
            ->addColumn('description', fn ($row) => $row->detail_description ?: $row->entry_description)
            ->addColumn('receipt', fn ($row) => $row->debit > 0 ? currency($row->debit) : '')
            ->addColumn('payment', fn ($row) => $row->credit > 0 ? currency($row->credit) : '')
            ->addColumn('running_balance', fn ($row) => currency($row->running_balance) . ' ' . $row->running_balance_type)
            ->with([
                'total_receipts' => currency($result['total_receipts']),
                'total_payments' => currency($result['total_payments']),
            ])
            ->make(true);
    }

    protected function summaryRow(Account $account, string $label, float $balance, string $type)
    {
        return (object) [
            'account_id'           => $account->account_id,
            'account_code'         => $account->code,
            'account_name'         => $account->name,
            'entry_date'           => null,
            'voucher_name'         => null,
            'source_type'          => null,
            'entry_no'             => null,
            'reference_no'         => null,
            'detail_description'   => $label,
            'entry_description'    => $label,
            'debit'                => 0,
            'credit'                => 0,
            'running_balance'      => $balance,
            'running_balance_type' => $type,
            'is_summary'           => true,
        ];
    }

    protected function emptyResult(): array
    {
        return [
            'accounts_count' => 0,
            'total_receipts' => 0,
            'total_payments' => 0,
            'start_date'     => null,
            'end_date'       => null,
            'rows'           => collect(),
        ];
    }
}
