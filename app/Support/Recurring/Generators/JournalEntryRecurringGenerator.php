<?php

namespace App\Support\Recurring\Generators;

use App\Enums\RecurringRunStatus;
use App\Enums\RecurringTransactionType;
use App\Enums\Status;
use App\Models\Account;
use App\Models\RecurringTransaction;
use App\Models\RecurringTransactionRun;
use App\Services\Concrete\Admin\JournalEntryService;
use App\Support\Recurring\TemplateData\JournalEntryTemplateValidator;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Generates a JournalEntry from a recurring template. Reuses
 * JournalEntryService::save() (entry-number generation via generateJVNum(),
 * detail-row persistence, audit stamping) exactly as the manual Journal
 * Entry screen does. Adds two safety checks that don't exist for the manual
 * screen because a human isn't reviewing this one: the debit=credit balance
 * check (shared with JournalEntryTemplateValidator, used again here since an
 * account referenced by the template may have gone inactive since the
 * template was saved) and live active/not-deleted checks per account line.
 */
class JournalEntryRecurringGenerator extends AbstractRecurringGenerator
{
    public function __construct(protected JournalEntryService $journal_entry_service)
    {
    }

    public function generate(
        RecurringTransaction $rt,
        Carbon $runDate,
        string $triggeredBy,
        ?int $triggeredByUserId
    ): RecurringTransactionRun {
        $started_at = now();
        $run_id = generateUuid();
        $template = $rt->template_data ?? [];

        try {
            $lines = $template['lines'] ?? [];

            JournalEntryTemplateValidator::assertBalanced($lines);
            $this->assertLineAccountsActive($lines);

            $status = $rt->auto_post ? Status::POSTED : Status::PENDING;

            $journal_entry = DB::transaction(function () use ($rt, $template, $lines, $runDate, $status, $run_id) {
                return $this->journal_entry_service->save([
                    'journal_entry_id'             => null,
                    'recurring_transaction_id'     => $rt->recurring_transaction_id,
                    'recurring_transaction_run_id' => $run_id,
                    'journal_id'                   => $template['journal_id'],
                    'entry_no'                     => generateJVNum($template['journal_id']),
                    'entry_date'                   => $runDate->copy(),
                    'reference_no'                 => $template['reference_no'] ?? null,
                    'description'                  => $template['description'] ?? ('Auto-generated from recurring schedule "' . $rt->name . '"'),
                    'business_id'                  => $rt->business_id,
                    'branch_id'                    => $rt->branch_id,
                    'details'                      => $lines,
                ], $status);
            });

            return $this->recordRun(
                $run_id,
                $rt,
                $runDate,
                RecurringRunStatus::SUCCESS,
                RecurringTransactionType::JOURNAL_ENTRY,
                $journal_entry->journal_entry_id,
                null,
                $triggeredBy,
                $triggeredByUserId,
                $started_at
            );
        } catch (Throwable $e) {
            return $this->recordRun(
                $run_id,
                $rt,
                $runDate,
                RecurringRunStatus::FAILED,
                null,
                null,
                $e->getMessage(),
                $triggeredBy,
                $triggeredByUserId,
                $started_at
            );
        }
    }

    protected function assertLineAccountsActive(array $lines): void
    {
        if (count($lines) < 2) {
            throw new Exception('A journal entry template must have at least 2 lines.');
        }

        foreach ($lines as $index => $line) {
            $account = Account::where('account_id', $line['account_id'] ?? null)
                ->where('is_deleted', 0)
                ->first();

            if (!$account) {
                throw new Exception('The account referenced on row ' . ($index + 1) . ' no longer exists.');
            }

            if (($account->status ?? Status::ACTIVE) !== Status::ACTIVE) {
                throw new Exception('The account "' . $account->name . '" referenced on row ' . ($index + 1) . ' is inactive.');
            }
        }
    }
}
