<?php

namespace App\Support\Recurring\Generators;

use App\Enums\RecurringRunStatus;
use App\Enums\RecurringTransactionType;
use App\Enums\Status;
use App\Models\Account;
use App\Models\ExpenseCategory;
use App\Models\RecurringTransaction;
use App\Models\RecurringTransactionRun;
use App\Services\Concrete\Admin\ExpenseService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Generates an Expense from a recurring template. Reuses ExpenseService::save()
 * (account resolution, expense-number generation via generateExpenseNo(),
 * audit stamping) and ExpenseService::status() (CPV/BPV posting via
 * applyPosting()) exactly as the manual Expense screen does - nothing about
 * numbering, account resolution, or posting is reimplemented here.
 */
class ExpenseRecurringGenerator extends AbstractRecurringGenerator
{
    public function __construct(protected ExpenseService $expense_service)
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
            $this->assertReferencesActive($template);

            $expense = DB::transaction(function () use ($rt, $template, $runDate, $run_id) {
                $expense = $this->expense_service->save([
                    'expense_category_id'     => $template['expense_category_id'],
                    'payment_method'          => $template['payment_method'] ?? null,
                    'payment_account_id'      => $template['payment_account_id'] ?? null,
                    'reference_no'            => $template['reference_no'] ?? null,
                    'amount'                  => $template['amount'],
                    'description'             => $template['description'] ?? null,
                    'user_id'                 => $template['user_id'] ?? null,
                    'expense_date'            => $runDate->copy(),
                    'business_id'             => $rt->business_id,
                    'branch_id'               => $rt->branch_id,
                    'source'                  => 'admin',
                ]);

                $expense->update([
                    'recurring_transaction_id'     => $rt->recurring_transaction_id,
                    'recurring_transaction_run_id' => $run_id,
                ]);

                if ($rt->auto_post) {
                    $this->expense_service->status([
                        'expense_id' => $expense->expense_id,
                        'status'     => Status::POSTED,
                    ]);
                }

                return $expense->fresh();
            });

            return $this->recordRun(
                $run_id,
                $rt,
                $runDate,
                RecurringRunStatus::SUCCESS,
                RecurringTransactionType::EXPENSE,
                $expense->expense_id,
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

    /**
     * Live reference check beyond basic existence (the template could have
     * been valid when saved, but the category/account it points to may have
     * since been deactivated or deleted) - per the requirement to handle
     * invalid/missing references safely rather than generating a bad expense.
     */
    protected function assertReferencesActive(array $template): void
    {
        $category = ExpenseCategory::where('expense_category_id', $template['expense_category_id'] ?? null)
            ->where('is_deleted', 0)
            ->first();

        if (!$category) {
            throw new Exception('The expense category configured for this recurring schedule no longer exists.');
        }

        if (($category->status ?? Status::ACTIVE) !== Status::ACTIVE) {
            throw new Exception('The expense category "' . $category->name . '" configured for this recurring schedule is inactive.');
        }

        if (!empty($template['payment_account_id'])) {
            $account = Account::where('account_id', $template['payment_account_id'])
                ->where('is_deleted', 0)
                ->first();

            if (!$account) {
                throw new Exception('The payment account configured for this recurring schedule no longer exists.');
            }

            if (($account->status ?? Status::ACTIVE) !== Status::ACTIVE) {
                throw new Exception('The payment account "' . $account->name . '" configured for this recurring schedule is inactive.');
            }
        }
    }
}
