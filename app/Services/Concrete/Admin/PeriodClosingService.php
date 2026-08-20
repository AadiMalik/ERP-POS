<?php

namespace App\Services\Concrete\Admin;

use App\Enums\Status;
use App\Models\AccountingPeriod;
use App\Models\EmployeeAdvance;
use App\Models\EmployeeExit;
use App\Models\JournalEntry;
use App\Models\LeaveRequest;
use App\Models\PeriodClosingAttempt;
use App\Models\PeriodClosingIssue;
use App\Models\PeriodClosingRule;
use App\Models\PurchaseReturn;
use App\Traits\Auditable;
use Illuminate\Support\Facades\DB;

/**
 * The validation + close engine shared by BOTH the scheduler
 * (ProcessAccountingPeriodsCommand) and the Advanced Mode manual "Close"
 * action (AccountingPeriodService::manualClose), so the two paths can never
 * diverge on what counts as "safe to close". Bank reconciliation is
 * deliberately not one of the checks below - the app has no reconciliation
 * feature yet - but every check here is config-driven (PeriodClosingRule),
 * so adding a new one later is additive.
 */
class PeriodClosingService
{
    use Auditable;

    /**
     * Returns a list of blocking issues for $period (empty = clean, safe to
     * close). Does not persist anything - see recordAttempt()/attemptClose().
     */
    public function evaluate(AccountingPeriod $period): array
    {
        $rules = PeriodClosingRule::firstOrCreate(['business_id' => $period->business_id], [
            'period_closing_rule_id' => generateUuid(),
            'date_created'           => now(),
        ]);

        $issues = [];

        if ($rules->check_unposted_journal_entries) {
            $issues = array_merge($issues, $this->findUnpostedJournalEntries($period));
        }

        if ($rules->check_pending_purchase_returns && $this->hasModule('inventory', $period->business_id)) {
            $issues = array_merge($issues, $this->findPendingApprovals(
                PurchaseReturn::class,
                'purchase_return_date',
                'purchase_return_no',
                'Purchase Return',
                'pending_purchase_returns',
                $period
            ));
        }

        if ($rules->check_pending_leave_requests && $this->hasModule('hrm', $period->business_id)) {
            $issues = array_merge($issues, $this->findPendingLeaveRequests($period));
        }

        if ($rules->check_pending_employee_advances && $this->hasModule('hrm', $period->business_id)) {
            $issues = array_merge($issues, $this->findPendingApprovals(
                EmployeeAdvance::class,
                'request_date',
                'employee_advance_id',
                'Employee Advance',
                'pending_employee_advances',
                $period
            ));
        }

        if ($rules->check_pending_employee_exits && $this->hasModule('hrm', $period->business_id)) {
            $issues = array_merge($issues, $this->findPendingApprovals(
                EmployeeExit::class,
                'request_date',
                'employee_exit_id',
                'Employee Exit',
                'pending_employee_exits',
                $period
            ));
        }

        return $issues;
    }

    /**
     * Persists a closing attempt (and its issues, if any) without changing
     * the period's status - used both for a blocked attempt and to log a
     * successful one alongside the caller's own status update.
     */
    public function recordAttempt(AccountingPeriod $period, array $issues, string $trigger, ?int $actorId): PeriodClosingAttempt
    {
        $attempt = PeriodClosingAttempt::updateOrCreate(
            [
                'accounting_period_id' => $period->accounting_period_id,
                'attempt_date'         => today()->toDateString(),
            ],
            [
                'period_closing_attempt_id' => generateUuid(),
                'trigger'                   => $trigger,
                'triggered_by_id'           => $actorId,
                'result'                    => empty($issues) ? 'closed' : 'blocked',
                'createdby_id'               => $actorId,
                'date_created'               => now(),
            ]
        );

        // Replace any issues from an earlier attempt the same day (re-runs).
        PeriodClosingIssue::where('period_closing_attempt_id', $attempt->period_closing_attempt_id)->delete();

        foreach ($issues as $issue) {
            PeriodClosingIssue::create([
                'period_closing_issue_id'   => generateUuid(),
                'period_closing_attempt_id' => $attempt->period_closing_attempt_id,
                'accounting_period_id'      => $period->accounting_period_id,
                'check_key'                 => $issue['check_key'],
                'source_type'               => $issue['source_type'] ?? null,
                'source_id'                 => $issue['source_id'] ?? null,
                'summary'                   => $issue['summary'],
                'date_created'              => now(),
            ]);
        }

        return $attempt;
    }

    /**
     * Evaluates the period and, if clean, closes it (flips status, opens the
     * next period, logs the activity). If blocked, records the attempt and
     * issues and leaves the period untouched. Used by the daily scheduler.
     */
    public function attemptClose(AccountingPeriod $period, string $trigger, ?int $actorId): PeriodClosingAttempt
    {
        $issues = $this->evaluate($period);
        $attempt = $this->recordAttempt($period, $issues, $trigger, $actorId);

        if (!empty($issues)) {
            return $attempt;
        }

        DB::transaction(function () use ($period, $trigger, $actorId) {
            $period->update([
                'status'               => 'closed',
                'closed_at'            => now(),
                'closed_by_id'         => $actorId,
                'closed_automatically' => $trigger === 'scheduler',
                'updatedby_id'         => $actorId,
                'date_updated'         => now(),
            ]);

            app(AccountingPeriodService::class)->openNext($period);
        });

        $this->logActivity(
            'accounting-period',
            $period->accounting_period_id,
            'closed',
            null,
            ['status' => 'closed', 'trigger' => $trigger],
            null,
            $period->business_id
        );

        return $attempt;
    }

    protected function findUnpostedJournalEntries(AccountingPeriod $period): array
    {
        $entries = JournalEntry::where('business_id', $period->business_id)
            ->where('status', Status::PENDING)
            ->where('is_deleted', 0)
            ->whereDate('entry_date', '>=', $period->start_date)
            ->whereDate('entry_date', '<=', $period->end_date)
            ->get();

        return $entries->map(function ($entry) {
            return [
                'check_key'   => 'unposted_journal_entries',
                'source_type' => 'JournalEntry',
                'source_id'   => $entry->journal_entry_id,
                'summary'     => 'Journal Entry ' . ($entry->entry_no ?: $entry->journal_entry_id) . ' is not posted',
            ];
        })->all();
    }

    protected function findPendingLeaveRequests(AccountingPeriod $period): array
    {
        $requests = LeaveRequest::where('business_id', $period->business_id)
            ->where('status', Status::PENDING)
            ->whereDate('start_date', '<=', $period->end_date)
            ->whereDate('end_date', '>=', $period->start_date)
            ->get();

        return $requests->map(function ($request) {
            return [
                'check_key'   => 'pending_leave_requests',
                'source_type' => 'LeaveRequest',
                'source_id'   => $request->leave_request_id ?? $request->id ?? null,
                'summary'     => 'A leave request is pending approval',
            ];
        })->all();
    }

    /**
     * Generic "pending approval within this period" check, reused for
     * Purchase Return / Employee Advance / Employee Exit - each has its own
     * status column with a Status::PENDING value and its own date column.
     */
    protected function findPendingApprovals(string $modelClass, string $dateColumn, string $noColumn, string $label, string $checkKey, AccountingPeriod $period): array
    {
        $rows = $modelClass::where('business_id', $period->business_id)
            ->where('status', Status::PENDING)
            ->when(in_array('is_deleted', (new $modelClass())->getFillable()), fn ($q) => $q->where('is_deleted', 0))
            ->whereDate($dateColumn, '>=', $period->start_date)
            ->whereDate($dateColumn, '<=', $period->end_date)
            ->get();

        return $rows->map(function ($row) use ($noColumn, $label, $checkKey) {
            $identifier = $row->{$noColumn} ?? $row->getKey();

            return [
                'check_key'   => $checkKey,
                'source_type' => class_basename($row),
                'source_id'   => $row->getKey(),
                'summary'     => $label . ' ' . $identifier . ' is pending approval',
            ];
        })->all();
    }

    /**
     * Skips a check entirely (not just when its rule toggle is off) if the
     * relevant module isn't enabled on the business's package - avoids
     * meaningless checks for businesses without HRM/Inventory.
     */
    protected function hasModule(string $module, string $businessId): bool
    {
        $business = \App\Models\Business::find($businessId);

        if (!$business) {
            return false;
        }

        return app(FeatureLimitService::class)->hasModule($module, $business);
    }
}
