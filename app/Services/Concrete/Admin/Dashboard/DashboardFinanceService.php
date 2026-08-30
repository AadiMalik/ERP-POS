<?php

namespace App\Services\Concrete\Admin\Dashboard;

use App\Enums\Status;
use App\Models\Account;
use App\Models\AccountingSetting;
use App\Models\CustomerProfile;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\User;
use App\Services\Concrete\Admin\Reports\Accounting\AccountClassifier;
use App\Services\Concrete\Admin\Reports\Accounting\AccountingLedgerQueryService;
use App\Services\Concrete\Admin\Reports\BalanceSheetReportService;
use App\Services\Concrete\Admin\Reports\ExpenseReportService;
use App\Services\Concrete\Admin\Reports\ProfitLossReportService;

/**
 * Financial-statement-derived dashboard widgets: Gross/Net Profit, Total
 * Expenses, Account/COA Summary, Cash/Bank Balance, Ledger Activity, Total
 * Receivables/Payables and Recent Payments. Composes the existing
 * accounting report services rather than re-deriving any of their logic.
 * Only ever invoked by DashboardService when scope is_finance is true
 * (FINANCE_ROLES + package `accounting` module), mirroring exactly what those
 * report services already allow via their own hardcoded allow_roles and the
 * `module:accounting` route group — no new privilege beyond the standalone
 * Financial Reports.
 */
class DashboardFinanceService
{
    public function __construct(
        protected ProfitLossReportService $profit_loss_service,
        protected BalanceSheetReportService $balance_sheet_service,
        protected ExpenseReportService $expense_report_service,
        protected AccountingLedgerQueryService $ledger_query_service,
        protected AccountClassifier $classifier
    ) {
    }

    public function build(array $scope): array
    {
        $obj = [
            'business_id' => $scope['business_id'],
            'branch_id' => $scope['effective_branch_id'],
            'start_date' => $scope['start_date']->format('Y-m-d'),
            'end_date' => $scope['end_date']->format('Y-m-d'),
        ];

        $profit_loss = $this->profit_loss_service->build($obj);

        $balance_sheet = $this->balance_sheet_service->build([
            'business_id' => $obj['business_id'],
            'branch_id' => $obj['branch_id'],
            'as_of_date' => $obj['end_date'],
        ]);

        $expense_rows = $this->expense_report_service->build($obj);

        return [
            'gross_profit' => $profit_loss['gross_profit'],
            'net_profit' => $profit_loss['net_profit'],
            'total_expenses' => round($expense_rows->sum('net_amount'), 2),
            'total_assets' => $balance_sheet['total_assets'],
            'total_liabilities' => $balance_sheet['total_liabilities'],
            'total_equity' => $balance_sheet['total_equity'],
            'cash_bank_balance' => $this->cashBankBalance($scope),
            'receivables' => $this->receivablesSummary($scope),
            'payables' => $this->payablesSummary($scope),
            'ledger_activity' => $this->recentLedgerActivity($scope),
            'recent_payments' => $this->recentPayments($scope),
        ];
    }

    protected function cashBankBalance(array $scope): float
    {
        $settings = AccountingSetting::where('business_id', $scope['business_id'])->first();

        $accountIds = Account::where('business_id', $scope['business_id'])
            ->where('is_deleted', 0)
            ->where('status', Status::ACTIVE)
            ->with('accountSubType')
            ->get()
            ->filter(fn ($account) => $this->classifier->isCashOrBank($account, $settings))
            ->pluck('account_id')
            ->all();

        if (empty($accountIds)) {
            return 0.0;
        }

        $totals = $this->ledger_query_service->totalBalances([
            'business_id' => $scope['business_id'],
            'branch_id' => $scope['effective_branch_id'],
            'account_ids' => $accountIds,
            'allow_roles' => DashboardAccessService::FINANCE_ROLES,
        ]);

        $balance = 0.0;

        foreach ($totals as $total) {
            // Cash/Bank accounts are Assets - debit-normal.
            $balance += $total['debit'] - $total['credit'];
        }

        return round($balance, 2);
    }

    /**
     * Single GROUP BY query across every customer instead of a per-customer
     * loop (which CustomerService::getCustomerLedger() does one at a time) -
     * avoids N+1 across the full customer list. Reuses
     * AccountingLedgerQueryService::baseQuery() so the same business/branch/
     * role scoping already proven there applies here unchanged.
     */
    protected function receivablesSummary(array $scope): array
    {
        $accountIds = $this->customerAccountIds($scope['business_id']);

        if (empty($accountIds)) {
            return ['total' => 0.0, 'top' => collect()];
        }

        $rows = $this->ledger_query_service->baseQuery([
            'business_id' => $scope['business_id'],
            'branch_id' => $scope['effective_branch_id'],
            'account_ids' => $accountIds,
            'allow_roles' => DashboardAccessService::FINANCE_ROLES,
        ])
            ->whereNotNull('journal_entry_details.user_id')
            ->selectRaw('journal_entry_details.user_id as party_id, SUM(journal_entry_details.credit) - SUM(journal_entry_details.debit) as raw_balance')
            ->groupBy('journal_entry_details.user_id')
            ->havingRaw('SUM(journal_entry_details.credit) - SUM(journal_entry_details.debit) > 0.009')
            ->get();

        return $this->summarizeParty($rows, User::class, 'id', 'name');
    }

    /**
     * Same GROUP BY methodology as receivablesSummary(), grouped by
     * supplier_id against Supplier.account_id - kept consistent with
     * Receivables so the two figures are computed the same way.
     */
    protected function payablesSummary(array $scope): array
    {
        $accountIds = $this->supplierAccountIds($scope['business_id']);

        if (empty($accountIds)) {
            return ['total' => 0.0, 'top' => collect()];
        }

        $rows = $this->ledger_query_service->baseQuery([
            'business_id' => $scope['business_id'],
            'branch_id' => $scope['effective_branch_id'],
            'account_ids' => $accountIds,
            'allow_roles' => DashboardAccessService::FINANCE_ROLES,
        ])
            ->whereNotNull('journal_entry_details.supplier_id')
            ->selectRaw('journal_entry_details.supplier_id as party_id, SUM(journal_entry_details.credit) - SUM(journal_entry_details.debit) as raw_balance')
            ->groupBy('journal_entry_details.supplier_id')
            ->havingRaw('SUM(journal_entry_details.credit) - SUM(journal_entry_details.debit) > 0.009')
            ->get();

        return $this->summarizeParty($rows, Supplier::class, 'supplier_id', 'name');
    }

    protected function summarizeParty($rows, string $model, string $modelKey, string $nameField): array
    {
        $total = round($rows->sum('raw_balance'), 2);

        $top = $rows->sortByDesc('raw_balance')->take(5)->values();

        $names = $model::whereIn($modelKey, $top->pluck('party_id'))->pluck($nameField, $modelKey);

        $top = $top->map(fn ($row) => [
            'id' => $row->party_id,
            'name' => $names[$row->party_id] ?? 'Unknown',
            'balance' => round((float) $row->raw_balance, 2),
        ]);

        return ['total' => $total, 'top' => $top];
    }

    protected function customerAccountIds($business_id): array
    {
        $settings = AccountingSetting::where('business_id', $business_id)->first();

        $ids = CustomerProfile::where('business_id', $business_id)
            ->where('is_deleted', 0)
            ->whereNotNull('account_id')
            ->distinct()
            ->pluck('account_id');

        return $ids->push($settings->default_customer_account_id ?? null)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    protected function supplierAccountIds($business_id): array
    {
        $settings = AccountingSetting::where('business_id', $business_id)->first();

        $ids = Supplier::where('business_id', $business_id)
            ->where('is_deleted', 0)
            ->whereNotNull('account_id')
            ->distinct()
            ->pluck('account_id');

        return $ids->push($settings->default_supplier_account_id ?? null)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    protected function recentLedgerActivity(array $scope, int $limit = 8)
    {
        return $this->ledger_query_service->transactions([
            'business_id' => $scope['business_id'],
            'branch_id' => $scope['effective_branch_id'],
            'allow_roles' => DashboardAccessService::FINANCE_ROLES,
        ], $scope['start_date'], $scope['end_date'])
            ->sortByDesc('entry_date')
            ->take($limit)
            ->values();
    }

    protected function recentPayments(array $scope, int $limit = 8)
    {
        return SupplierPayment::with(['supplier', 'paymentAccount', 'branch'])
            ->where('business_id', $scope['business_id'])
            ->where('is_deleted', 0)
            ->when($scope['effective_branch_id'], fn ($q, $b) => $q->where('branch_id', $b))
            ->orderByDesc('payment_date')
            ->limit($limit)
            ->get();
    }
}
