<?php

namespace App\Services\Concrete\Admin;

use App\Exceptions\PeriodClosedException;
use App\Models\AccountingPeriod;
use App\Models\AccountingSetting;
use App\Repository\Repository;
use App\Traits\Auditable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Owns the accounting-period lock: the single check every journal-posting
 * code path in the app (Expense, Supplier Payment, Purchase, Purchase
 * Return, Stock Taking, Opening Stock, GRN, POS/Order, manual Journal Entry
 * create/edit/post-unpost) calls before writing a posted JournalEntry, plus
 * the Advanced Accounting Mode open/close/reopen actions.
 */
class AccountingPeriodService
{
    use Auditable;

    protected $model_accounting_period;

    public function __construct()
    {
        $this->model_accounting_period = new Repository(new AccountingPeriod());
    }

    /**
     * Throws PeriodClosedException if $entryDate falls inside a CLOSED
     * accounting period for $businessId. Deliberately permissive when:
     *  - accounting is disabled for the business, or
     *  - no AccountingPeriod row covers $entryDate at all (an ungoverned
     *    date range - e.g. before the business ever turned on period
     *    management, or before the first period was created). This is what
     *    keeps the feature fully non-breaking for every business that has
     *    never touched the new settings: periods are only ever created
     *    going forward from "today", never backfilled over history.
     */
    public function assertPostable(?string $businessId, $entryDate): void
    {
        if (empty($businessId) || empty($entryDate)) {
            return;
        }

        $accounting_setting = AccountingSetting::where('business_id', $businessId)->first();

        if (!$accounting_setting || !$accounting_setting->enable_accounting) {
            return;
        }

        $period = AccountingPeriod::where('business_id', $businessId)
            ->where('is_deleted', 0)
            ->whereDate('start_date', '<=', $entryDate)
            ->whereDate('end_date', '>=', $entryDate)
            ->first();

        if ($period && $period->status === 'closed') {
            throw new PeriodClosedException(
                'Accounting period "' . $period->name . '" (' .
                    \Carbon\Carbon::parse($period->start_date)->format('d-m-Y') . ' to ' .
                    \Carbon\Carbon::parse($period->end_date)->format('d-m-Y') .
                    ') is closed. Reopen it in Advanced Accounting Mode before posting to this date.'
            );
        }
    }

    public function getData($obj)
    {
        $wh = [];

        if (!empty($obj['business_id'])) {
            $wh[] = ['business_id', $obj['business_id']];
        }

        $query = $this->model_accounting_period->getModel()::with(['fiscalYear'])
            ->where($wh)
            ->where('is_deleted', 0)
            ->orderBy('start_date', 'desc');

        return $query->get();
    }

    public function getById($accounting_period_id)
    {
        return $this->model_accounting_period->getModel()::with(['fiscalYear', 'closingAttempts.issues'])
            ->findOrFail($accounting_period_id);
    }

    /**
     * Latest closing-attempt issues for a period (the "why isn't this
     * closed" panel) - empty if the latest attempt closed cleanly or no
     * attempt has run yet.
     */
    public function latestIssues(string $accounting_period_id)
    {
        $latest_attempt = \App\Models\PeriodClosingAttempt::where('accounting_period_id', $accounting_period_id)
            ->orderByDesc('date_created')
            ->first();

        if (!$latest_attempt || $latest_attempt->result === 'closed') {
            return collect();
        }

        return \App\Models\PeriodClosingIssue::where('period_closing_attempt_id', $latest_attempt->period_closing_attempt_id)->get();
    }

    /**
     * Flips the next 'upcoming' period (by start_date) for the same business
     * to 'open'. If none exists yet, asks FiscalYearService to top up future
     * periods first, then retries once.
     */
    public function openNext(AccountingPeriod $closed_period): ?AccountingPeriod
    {
        $next = AccountingPeriod::where('business_id', $closed_period->business_id)
            ->where('is_deleted', 0)
            ->where('status', 'upcoming')
            ->where('start_date', '>', $closed_period->end_date)
            ->orderBy('start_date')
            ->first();

        if (!$next) {
            app(FiscalYearService::class)->ensureFuturePeriods($closed_period->business);

            $next = AccountingPeriod::where('business_id', $closed_period->business_id)
                ->where('is_deleted', 0)
                ->where('status', 'upcoming')
                ->where('start_date', '>', $closed_period->end_date)
                ->orderBy('start_date')
                ->first();
        }

        if (!$next) {
            return null;
        }

        $next->update([
            'status'       => 'open',
            'opened_at'    => now(),
            'opened_by_id' => Auth::id(),
            'updatedby_id' => Auth::id(),
            'date_updated' => now(),
        ]);

        return $next;
    }

    /**
     * Advanced Mode: open an 'upcoming' period ahead of its normal turn.
     */
    public function manualOpen(string $accounting_period_id)
    {
        $period = AccountingPeriod::findOrFail($accounting_period_id);

        $period->update([
            'status'       => 'open',
            'opened_at'    => now(),
            'opened_by_id' => Auth::id(),
            'updatedby_id' => Auth::id(),
            'date_updated' => now(),
        ]);

        $this->logActivity('accounting-period', $period->accounting_period_id, 'opened', null, ['status' => 'open'], null, $period->business_id);

        return $period;
    }

    /**
     * Advanced Mode manual close. Always runs the same checklist the
     * scheduler uses (PeriodClosingService) so the two paths can never
     * diverge. If blocking issues exist, returns the attempt (with issues
     * persisted) WITHOUT closing unless $override is true - overriding
     * always requires a reason, which is recorded to the audit log either
     * way.
     */
    public function manualClose(string $accounting_period_id, ?string $reason, bool $override = false)
    {
        $period = AccountingPeriod::findOrFail($accounting_period_id);
        $closing_service = app(PeriodClosingService::class);
        $issues = $closing_service->evaluate($period);

        // Blocking issues exist and the caller isn't overriding: persist the
        // attempt (for the "why isn't this closed" panel) and stop here -
        // this is the same shape the scheduler's attemptClose() produces.
        if (!empty($issues) && !$override) {
            return $closing_service->recordAttempt($period, $issues, 'manual', Auth::id());
        }

        if (!empty($issues) && $override && empty(trim((string) $reason))) {
            throw new \Exception('A reason is required to close this period while issues are still pending.');
        }

        return DB::transaction(function () use ($period, $reason, $closing_service) {
            $closing_service->recordAttempt($period, [], 'manual', Auth::id());

            $period->update([
                'status'               => 'closed',
                'closed_at'            => now(),
                'closed_by_id'         => Auth::id(),
                'close_reason'         => $reason,
                'closed_automatically' => false,
                'updatedby_id'         => Auth::id(),
                'date_updated'         => now(),
            ]);

            $this->openNext($period);

            $this->logActivity('accounting-period', $period->accounting_period_id, 'closed', null, ['status' => 'closed'], $reason, $period->business_id);

            return $period;
        });
    }

    /**
     * Advanced Mode: reopen a closed period so posting can resume in it.
     * Always requires a reason - recorded to the audit log. Reopening only
     * changes the period's status; it does not touch any JournalEntry rows
     * already validated against it.
     */
    public function manualReopen(string $accounting_period_id, string $reason)
    {
        if (empty(trim($reason))) {
            throw new \Exception('A reason is required to reopen this period.');
        }

        $period = AccountingPeriod::findOrFail($accounting_period_id);

        $old_status = $period->status;

        $period->update([
            'status'         => 'open',
            'reopened_at'    => now(),
            'reopened_by_id' => Auth::id(),
            'reopen_reason'  => $reason,
            'reopen_count'   => $period->reopen_count + 1,
            'updatedby_id'   => Auth::id(),
            'date_updated'   => now(),
        ]);

        $this->logActivity('accounting-period', $period->accounting_period_id, 'reopened', ['status' => $old_status], ['status' => 'open'], $reason, $period->business_id);

        return $period;
    }
}
