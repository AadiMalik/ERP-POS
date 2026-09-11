<?php

namespace App\Services\Concrete\Admin\Reports\BusinessSummary\Providers;

use App\Models\CustomerProfile;
use App\Models\Order;
use App\Services\Concrete\Admin\Dashboard\DashboardFinanceService;
use App\Services\Concrete\Admin\Reports\BusinessSummary\BusinessSummaryContext;
use App\Services\Concrete\Admin\Reports\BusinessSummary\BusinessSummaryInsightFactory as Insight;
use App\Services\Concrete\Admin\Reports\ExpenseReportService;
use App\Services\Concrete\Admin\Reports\ProfitLossReportService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class FinanceProvider
{
    public function __construct(
        protected ProfitLossReportService $profit_loss_service,
        protected ExpenseReportService $expense_report_service,
        protected DashboardFinanceService $finance_service
    ) {
    }

    public function collect(BusinessSummaryContext $context): array
    {
        if (!$context->hasModule('accounting') || !$context->canAny([
            'reports.profit-loss.view',
            'reports.expense-report.view',
            'reports.customer-aging.view',
            'reports.accounts-payable.view',
            'reports.cash-flow.view',
        ])) {
            return ['kpis' => [], 'insights' => []];
        }

        $obj = [
            'business_id' => $context->business_id,
            'branch_id' => $context->branch_id,
            'start_date' => $context->start_date,
            'end_date' => $context->end_date,
        ];

        $profitLoss = $context->can('reports.profit-loss.view')
            ? $this->profit_loss_service->build($obj)
            : [];

        $expenses = collect();
        if ($context->can('reports.expense-report.view')) {
            $expenses = $this->expense_report_service->build($obj);
        }

        $scope = [
            'business_id' => $context->business_id,
            'effective_branch_id' => $context->branch_id,
            'start_date' => Carbon::parse($context->start_date, $context->timezone)->startOfDay(),
            'end_date' => Carbon::parse($context->end_date, $context->timezone)->endOfDay(),
            'is_finance' => true,
        ];
        $finance = $this->finance_service->build($scope);

        $creditRisk = $this->creditLimitRisk($context);

        $kpis = [
            'gross_profit' => $profitLoss['gross_profit'] ?? null,
            'net_profit' => $profitLoss['net_profit'] ?? null,
            'expenses' => $finance['total_expenses'] ?? round($expenses->sum('net_amount'), 2),
            'cash_bank' => $finance['cash_bank_balance'] ?? 0,
            'receivables' => $finance['receivables']['total'] ?? 0,
            'payables' => $finance['payables']['total'] ?? 0,
        ];

        $insights = [];

        if ($context->can('reports.profit-loss.view')) {
            $margin = ($profitLoss['total_revenue'] ?? 0) > 0
                ? round((($profitLoss['gross_profit'] ?? 0) / $profitLoss['total_revenue']) * 100, 1)
                : 0;
            $lowMargin = (float) $context->threshold('low_margin_percent', 10);
            $severity = 'informational';
            if (($profitLoss['net_profit'] ?? 0) < 0) {
                $severity = 'critical';
            } elseif ($margin > 0 && $margin < $lowMargin && ($profitLoss['total_revenue'] ?? 0) > 0) {
                $severity = 'important';
            } elseif (($profitLoss['net_profit'] ?? 0) > 0 && $margin >= $lowMargin) {
                $severity = 'good';
            }

            $insights[] = Insight::make([
                'id' => 'profit_loss',
                'module' => 'accounting',
                'severity' => $severity,
                'icon' => 'fa-scale-balanced',
                'title_key' => 'reports.business_summary.pl_title',
                'title_params' => ['amount' => currency($profitLoss['net_profit'] ?? 0)],
                'description_key' => 'reports.business_summary.pl_desc',
                'description_params' => [
                    'revenue' => currency($profitLoss['total_revenue'] ?? 0),
                    'cogs' => currency($profitLoss['total_cost_of_revenue'] ?? 0),
                    'gross' => currency($profitLoss['gross_profit'] ?? 0),
                    'margin' => Insight::formatMetric($margin, 'percent'),
                ],
                'metric' => $profitLoss['net_profit'] ?? 0,
                'metric_type' => 'money',
                'value' => $profitLoss['net_profit'] ?? 0,
                'impact' => abs($profitLoss['net_profit'] ?? 0),
                'why_key' => ($profitLoss['net_profit'] ?? 0) < 0
                    ? 'reports.business_summary.why.net_loss'
                    : ($margin < $lowMargin ? 'reports.business_summary.why.low_margin' : null),
                'action' => Insight::action($context, 'reports.business_summary.actions.view_profit_loss', '/admin/reports/profit-loss', [], 'reports.profit-loss.view'),
            ]);
        }

        $expenseTotal = (float) ($kpis['expenses'] ?? 0);
        if ($expenseTotal > 0 || $expenses->isNotEmpty()) {
            $top = $expenses->sortByDesc('net_amount')->take(5)->map(fn ($row) => [
                'label' => $row->account_name ?? $row->name ?? $row->label ?? '',
                'value' => currency($row->net_amount ?? 0),
            ])->values()->all();

            $insights[] = Insight::make([
                'id' => 'expenses',
                'module' => 'accounting',
                'severity' => 'informational',
                'icon' => 'fa-money-bill-wave',
                'title_key' => 'reports.business_summary.expenses_title',
                'title_params' => ['amount' => currency($expenseTotal)],
                'description_key' => 'reports.business_summary.expenses_desc',
                'metric' => $expenseTotal,
                'metric_type' => 'money',
                'value' => $expenseTotal,
                'details' => $top,
                'action' => Insight::action($context, 'reports.business_summary.actions.view_expenses', '/admin/reports/expense-report', [], 'reports.expense-report.view'),
            ]);
        }

        $insights[] = Insight::make([
            'id' => 'cash_bank',
            'module' => 'accounting',
            'severity' => ($finance['cash_bank_balance'] ?? 0) < 0 ? 'critical' : 'informational',
            'icon' => 'fa-building-columns',
            'title_key' => 'reports.business_summary.cash_bank_title',
            'title_params' => ['amount' => currency($finance['cash_bank_balance'] ?? 0)],
            'description_key' => 'reports.business_summary.cash_bank_desc',
            'metric' => $finance['cash_bank_balance'] ?? 0,
            'metric_type' => 'money',
            'value' => $finance['cash_bank_balance'] ?? 0,
            'why_key' => ($finance['cash_bank_balance'] ?? 0) < 0 ? 'reports.business_summary.why.negative_cash' : null,
            'action' => Insight::action($context, 'reports.business_summary.actions.view_cash_flow', '/admin/reports/cash-flow', [], 'reports.cash-flow.view'),
        ]);

        $receivableTotal = (float) ($finance['receivables']['total'] ?? 0);
        if ($receivableTotal > 0) {
            $top = collect($finance['receivables']['top'] ?? [])->take(5)->map(fn ($row) => [
                'label' => $row['name'] ?? '',
                'value' => currency($row['balance'] ?? 0),
            ])->all();

            $insights[] = Insight::make([
                'id' => 'receivables',
                'module' => 'accounting',
                'severity' => $receivableTotal > 0 ? 'important' : 'informational',
                'icon' => 'fa-hand-holding-dollar',
                'title_key' => 'reports.business_summary.receivables_title',
                'title_params' => ['amount' => currency($receivableTotal)],
                'description_key' => 'reports.business_summary.receivables_desc',
                'metric' => $receivableTotal,
                'metric_type' => 'money',
                'value' => $receivableTotal,
                'impact' => $receivableTotal,
                'details' => $top,
                'why_key' => 'reports.business_summary.why.receivables',
                'action' => Insight::action($context, 'reports.business_summary.actions.view_customer_aging', '/admin/reports/customer-aging', [], 'reports.customer-aging.view'),
            ]);
        }

        if ($creditRisk['count'] > 0) {
            $insights[] = Insight::make([
                'id' => 'credit_limit_risk',
                'module' => 'accounting',
                'severity' => 'critical',
                'icon' => 'fa-user-lock',
                'title_key' => 'reports.business_summary.credit_limit_title',
                'title_params' => ['count' => $creditRisk['count']],
                'description_key' => 'reports.business_summary.credit_limit_desc',
                'description_params' => ['threshold' => $creditRisk['threshold']],
                'metric' => $creditRisk['count'],
                'value' => $creditRisk['value'],
                'impact' => $creditRisk['value'],
                'details' => $creditRisk['details'],
                'why_key' => 'reports.business_summary.why.credit_limit',
                'action' => Insight::action($context, 'reports.business_summary.actions.view_due_credit', '/admin/reports/due-credit-sales', [], 'reports.due-credit-sales.view'),
            ]);
        }

        $payableTotal = (float) ($finance['payables']['total'] ?? 0);
        if ($payableTotal > 0) {
            $top = collect($finance['payables']['top'] ?? [])->take(5)->map(fn ($row) => [
                'label' => $row['name'] ?? '',
                'value' => currency($row['balance'] ?? 0),
            ])->all();

            $insights[] = Insight::make([
                'id' => 'payables',
                'module' => 'accounting',
                'severity' => 'important',
                'icon' => 'fa-file-invoice-dollar',
                'title_key' => 'reports.business_summary.supplier_dues_title',
                'title_params' => ['amount' => currency($payableTotal)],
                'description_key' => 'reports.business_summary.supplier_dues_desc',
                'metric' => $payableTotal,
                'metric_type' => 'money',
                'value' => $payableTotal,
                'impact' => $payableTotal,
                'details' => $top,
                'why_key' => 'reports.business_summary.why.supplier_dues',
                'action' => Insight::action($context, 'reports.business_summary.actions.view_payables', '/admin/reports/accounts-payable', [], 'reports.accounts-payable.view'),
            ]);
        }

        return compact('kpis', 'insights');
    }

    /**
     * Customers whose outstanding posted-order due has reached the configured
     * credit-limit threshold percent (same rule as CheckNotificationAlertsCommand).
     */
    protected function creditLimitRisk(BusinessSummaryContext $context): array
    {
        $threshold = (float) $context->threshold('credit_limit_threshold_percent', 100);
        $dues = Order::query()
            ->where('business_id', $context->business_id)
            ->where('is_deleted', 0)
            ->where('status', 'posted')
            ->when($context->branch_id, fn ($q) => $q->where('branch_id', $context->branch_id))
            ->whereNotNull('user_id')
            ->select('user_id', DB::raw('SUM(total - paid_amount) as due'))
            ->groupBy('user_id')
            ->havingRaw('SUM(total - paid_amount) > 0.009')
            ->pluck('due', 'user_id');

        if ($dues->isEmpty()) {
            return ['count' => 0, 'value' => 0, 'details' => [], 'threshold' => $threshold];
        }

        $profiles = CustomerProfile::query()
            ->where('business_id', $context->business_id)
            ->where('is_deleted', 0)
            ->whereIn('user_id', $dues->keys())
            ->where('credit_limit', '>', 0)
            ->with('user:id,name')
            ->get();

        $atRisk = [];
        $value = 0.0;
        foreach ($profiles as $profile) {
            $due = (float) $dues->get($profile->user_id, 0);
            $limit = (float) $profile->credit_limit;
            if ($limit <= 0) {
                continue;
            }
            $percent = ($due / $limit) * 100;
            if ($percent >= $threshold) {
                $atRisk[] = [
                    'label' => $profile->user->name ?? $profile->user_id,
                    'value' => currency($due) . ' / ' . currency($limit),
                ];
                $value += $due;
            }
        }

        return [
            'count' => count($atRisk),
            'value' => $value,
            'details' => array_slice($atRisk, 0, 8),
            'threshold' => $threshold,
        ];
    }
}
