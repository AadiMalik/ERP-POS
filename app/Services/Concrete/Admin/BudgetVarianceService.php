<?php

namespace App\Services\Concrete\Admin;

use App\Enums\Status;
use App\Models\Budget;
use App\Models\BudgetLine;
use App\Models\JournalEntryDetail;
use Carbon\Carbon;

/**
 * Computes Budget vs Actual + variance LIVE against posted JournalEntryDetail
 * rows at report-render time - no caching layer, matching every other
 * financial report in this app (Trial Balance, P&L, Balance Sheet, etc. are
 * all computed on-demand). Actuals for the budget's whole date span are
 * fetched in a single query, then matched to each BudgetLine's own
 * account/branch/period window in PHP - simpler and fast enough at this
 * data volume than joining per-line; revisit only if a specific business's
 * report is measured slow (see plan Risks).
 */
class BudgetVarianceService
{
    public function varianceReport(Budget $budget, ?string $branchId = null)
    {
        $lines = BudgetLine::where('budget_id', $budget->budget_id)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->with('account', 'branch')
            ->orderBy('period_start')
            ->get();

        if ($lines->isEmpty()) {
            return collect();
        }

        $min_start = $lines->min('period_start');
        $max_end = $lines->max('period_end');

        $actual_rows = JournalEntryDetail::query()
            ->join('journal_entries', 'journal_entries.journal_entry_id', '=', 'journal_entry_details.journal_entry_id')
            ->where('journal_entries.business_id', $budget->business_id)
            ->where('journal_entries.status', Status::POSTED)
            ->where('journal_entries.is_deleted', 0)
            ->whereDate('journal_entries.entry_date', '>=', $min_start)
            ->whereDate('journal_entries.entry_date', '<=', $max_end)
            ->when($branchId, fn ($q) => $q->where('journal_entries.branch_id', $branchId))
            ->select(
                'journal_entry_details.account_id',
                'journal_entries.branch_id',
                'journal_entries.entry_date',
                'journal_entry_details.debit',
                'journal_entry_details.credit'
            )
            ->get()
            ->map(function ($row) {
                $row->entry_date = Carbon::parse($row->entry_date);

                return $row;
            });

        return $lines->map(function (BudgetLine $line) use ($actual_rows) {
            $period_start = Carbon::parse($line->period_start)->startOfDay();
            $period_end = Carbon::parse($line->period_end)->endOfDay();

            $matching = $actual_rows->filter(function ($row) use ($line, $period_start, $period_end) {
                return $row->account_id === $line->account_id
                    && (string) $row->branch_id === (string) $line->branch_id
                    && $row->entry_date->gte($period_start)
                    && $row->entry_date->lte($period_end);
            });

            $total_debit = (float) $matching->sum('debit');
            $total_credit = (float) $matching->sum('credit');
            $actual = $line->account_debit_normal ? ($total_debit - $total_credit) : ($total_credit - $total_debit);
            $budgeted = (float) $line->budgeted_amount;
            $variance = $actual - $budgeted;

            return [
                'budget_line_id'   => $line->budget_line_id,
                'account_id'       => $line->account_id,
                'account_name'     => optional($line->account)->name,
                'account_code'     => optional($line->account)->code,
                'branch_id'        => $line->branch_id,
                'branch_name'      => optional($line->branch)->name,
                'period_start'     => $line->period_start,
                'period_end'       => $line->period_end,
                'budgeted'         => round($budgeted, 2),
                'actual'           => round($actual, 2),
                'variance'         => round($variance, 2),
                'variance_percent' => $budgeted != 0.0 ? round(($variance / abs($budgeted)) * 100, 2) : null,
            ];
        });
    }
}
