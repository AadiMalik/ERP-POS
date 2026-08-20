<?php

namespace App\Services\Concrete\Admin;

use App\Enums\Status;
use App\Models\Budget;
use App\Models\BudgetLine;
use App\Models\FiscalYear;
use App\Models\JournalEntryDetail;
use App\Services\Concrete\Admin\Reports\Accounting\AccountClassifier;
use App\Traits\Auditable;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Auto-generates BudgetLine rows from the SAME fiscal-year-ago slice of
 * posted actuals (JournalEntryDetail joined to JournalEntry, status =
 * posted), grown by the budget's configured growth_percent. Idempotent via
 * firstOrNew/save on the same (budget_id, account_id, branch_id,
 * period_start) key BudgetLine's unique index enforces, so re-generating
 * after adjusting the growth % safely overwrites existing lines rather than
 * duplicating them.
 */
class BudgetGenerationService
{
    use Auditable;

    public function __construct(protected AccountClassifier $classifier)
    {
    }

    public function generate(Budget $budget): int
    {
        $fiscal_year = $budget->fiscalYear ?? FiscalYear::findOrFail($budget->fiscal_year_id);
        $slices = $this->buildSlices($fiscal_year, $budget->granularity);
        $growth = $budget->growth_percent !== null ? (float) $budget->growth_percent : 0.0;
        $lines_written = 0;

        DB::transaction(function () use ($budget, $slices, $growth, &$lines_written) {
            foreach ($slices as [$slice_start, $slice_end]) {
                $prior_start = Carbon::parse($slice_start)->subYear();
                $prior_end = Carbon::parse($slice_end)->subYear();

                $actuals = $this->priorActuals($budget->business_id, $prior_start, $prior_end);

                foreach ($actuals as $key => $row) {
                    [$account_id, $branch_id] = explode('|', $key, 2);
                    $branch_id = $branch_id === '' ? null : $branch_id;

                    $debit_normal = $this->classifier->isDebitNormal($row->account_type_name);
                    $prior_amount = $debit_normal
                        ? ((float) $row->total_debit - (float) $row->total_credit)
                        : ((float) $row->total_credit - (float) $row->total_debit);
                    $budgeted_amount = round($prior_amount * (1 + $growth / 100), 2);

                    $line = BudgetLine::firstOrNew([
                        'budget_id'    => $budget->budget_id,
                        'account_id'   => $account_id,
                        'branch_id'    => $branch_id,
                        'period_start' => $slice_start,
                    ]);

                    if (!$line->exists) {
                        $line->budget_line_id = generateUuid();
                        $line->createdby_id = Auth::id();
                        $line->date_created = now();
                    }

                    $line->period_end = $slice_end;
                    $line->account_debit_normal = $debit_normal;
                    $line->budgeted_amount = $budgeted_amount;
                    $line->updatedby_id = Auth::id();
                    $line->date_updated = now();
                    $line->save();

                    $lines_written++;
                }
            }

            $budget->update([
                'status'       => 'active',
                'growth_percent' => $growth,
                'updatedby_id' => Auth::id(),
                'date_updated' => now(),
            ]);
        });

        $this->logActivity('budget', $budget->budget_id, 'generated', null, ['lines' => $lines_written], null, $budget->business_id);

        return $lines_written;
    }

    /**
     * Splits the fiscal year into monthly/quarterly/yearly [start, end] date
     * pairs, clipped to the fiscal year's own boundaries.
     */
    protected function buildSlices(FiscalYear $fiscal_year, string $granularity): array
    {
        $start = Carbon::parse($fiscal_year->start_date)->startOfDay();
        $end = Carbon::parse($fiscal_year->end_date)->startOfDay();

        if ($granularity === 'yearly') {
            return [[$start->toDateString(), $end->toDateString()]];
        }

        $months_per_slice = $granularity === 'quarterly' ? 3 : 1;
        $slices = [];
        $cursor = $start->copy();

        while ($cursor->lte($end)) {
            $slice_end = $cursor->copy()->addMonths($months_per_slice)->subDay();
            if ($slice_end->gt($end)) {
                $slice_end = $end->copy();
            }
            $slices[] = [$cursor->toDateString(), $slice_end->toDateString()];
            $cursor = $slice_end->copy()->addDay();
        }

        return $slices;
    }

    /**
     * Posted actuals for $businessId within [$start, $end], grouped by
     * account + branch, keyed 'account_id|branch_id' ('' when branch-less).
     */
    protected function priorActuals(string $businessId, Carbon $start, Carbon $end)
    {
        return JournalEntryDetail::query()
            ->join('journal_entries', 'journal_entries.journal_entry_id', '=', 'journal_entry_details.journal_entry_id')
            ->join('accounts', 'accounts.account_id', '=', 'journal_entry_details.account_id')
            ->join('account_types', 'account_types.account_type_id', '=', 'accounts.account_type_id')
            ->where('journal_entries.business_id', $businessId)
            ->where('journal_entries.status', Status::POSTED)
            ->where('journal_entries.is_deleted', 0)
            ->whereDate('journal_entries.entry_date', '>=', $start->toDateString())
            ->whereDate('journal_entries.entry_date', '<=', $end->toDateString())
            ->selectRaw('journal_entry_details.account_id as account_id, journal_entries.branch_id as branch_id, account_types.name as account_type_name, SUM(journal_entry_details.debit) as total_debit, SUM(journal_entry_details.credit) as total_credit')
            ->groupBy('journal_entry_details.account_id', 'journal_entries.branch_id', 'account_types.name')
            ->get()
            ->keyBy(fn ($row) => $row->account_id . '|' . ($row->branch_id ?? ''));
    }
}
