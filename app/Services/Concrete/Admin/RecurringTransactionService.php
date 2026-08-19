<?php

namespace App\Services\Concrete\Admin;

use App\Enums\Filter;
use App\Enums\RecurringFrequency;
use App\Enums\RecurringRunStatus;
use App\Enums\RecurringTriggeredBy;
use App\Enums\RoleNames;
use App\Enums\Status;
use App\Models\RecurringTransaction;
use App\Models\RecurringTransactionRun;
use App\Repository\Repository;
use App\Support\Recurring\Generators\RecurringGeneratorRegistry;
use App\Support\Recurring\RecurringScheduleCalculator;
use App\Support\Recurring\TemplateData\RecurringTemplateValidatorRegistry;
use App\Traits\Auditable;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

class RecurringTransactionService
{
    use Auditable;

    protected $model;
    protected $with = ['business', 'branch', 'createdby'];

    public function __construct(protected RecurringGeneratorRegistry $generator_registry, protected NotificationDispatchService $dispatcher)
    {
        $this->model = new Repository(new RecurringTransaction());
    }

    public function getData($obj)
    {
        $wh = [];
        $orderBy = Filter::ORDERBY;

        if (isset($obj['orderBy']) && $obj['orderBy'] != 0 && $obj['orderBy'] != "") {
            $orderBy = $obj['orderBy'];
        }
        if (!empty($obj['business_id'])) {
            $wh[] = ['business_id', $obj['business_id']];
        }
        if (!empty($obj['branch_id'])) {
            $wh[] = ['branch_id', $obj['branch_id']];
        }
        if (!empty($obj['transaction_type'])) {
            $wh[] = ['transaction_type', $obj['transaction_type']];
        }
        if (!empty($obj['status'])) {
            $wh[] = ['status', $obj['status']];
        }
        if (!empty($obj['frequency'])) {
            $wh[] = ['frequency', $obj['frequency']];
        }
        if (!empty($obj['next_run_from'])) {
            $wh[] = ['next_run_date', '>=', Carbon::parse($obj['next_run_from'])->startOfDay()];
        }
        if (!empty($obj['next_run_to'])) {
            $wh[] = ['next_run_date', '<=', Carbon::parse($obj['next_run_to'])->endOfDay()];
        }
        if (!empty($obj['start_date'])) {
            $wh[] = ['date_created', '>=', Carbon::parse($obj['start_date'])->startOfDay()];
        }
        if (!empty($obj['end_date'])) {
            $wh[] = ['date_created', '<=', Carbon::parse($obj['end_date'])->endOfDay()];
        }

        $allow_roles = [
            RoleNames::SUPERADMIN,
            RoleNames::BUSINESSADMIN,
            RoleNames::FINANCEMANAGER,
            RoleNames::ACCOUNTANT,
            RoleNames::BRANCHADMIN,
        ];

        $datatable = $this->model->getModel()::with($this->with)
            ->where($wh)
            ->where('is_deleted', 0)
            ->orderBy('date_created', $orderBy);
        $datatable = applyRoleScope($datatable, $allow_roles);

        // Account filter reaches into the JSON template_data payload (expense's
        // payment_account_id or a journal entry's per-line account_id) - no JSON
        // path index exists yet, acceptable at Phase-1 scale.
        if (!empty($obj['account_id'])) {
            $datatable->whereRaw('JSON_SEARCH(template_data, "one", ?) IS NOT NULL', [$obj['account_id']]);
        }

        return DataTables::of($datatable)
            ->addColumn('transaction_type', function ($item) {
                return \App\Enums\RecurringTransactionType::labels()[$item->transaction_type] ?? ucfirst($item->transaction_type);
            })
            ->addColumn('frequency', function ($item) {
                return ucfirst($item->frequency);
            })
            ->addColumn('next_run_date', function ($item) {
                return $item->next_run_date ? localDate($item->next_run_date) : 'N/A';
            })
            ->addColumn('last_run_date', function ($item) {
                return $item->last_run_date ? localDate($item->last_run_date) : 'Never';
            })
            ->addColumn('business', function ($item) {
                return $item->business->name ?? '';
            })
            ->addColumn('branch', function ($item) {
                return $item->branch->name ?? 'All Branches';
            })
            ->addColumn('status', function ($item) {
                $badges = [
                    Status::ACTIVE    => 'success',
                    Status::PAUSED    => 'warning',
                    Status::COMPLETED => 'secondary',
                    Status::CANCELLED => 'danger',
                ];
                $color = $badges[$item->status] ?? 'secondary';
                return "<span class='badge bg-{$color}'>" . ucfirst($item->status) . "</span>";
            })
            ->addColumn('action', function ($item) {
                $user = Auth::user();
                $html = "<a class='btn btn-icon btn-outline-primary mr-2' href='" . route('recurring-transaction.edit', $item->recurring_transaction_id) . "'><i class='fa fa-pencil'></i></a>";
                $html .= "<a class='btn btn-icon btn-outline-info mr-2' href='" . route('recurring-transaction.history', $item->recurring_transaction_id) . "'><i class='fa fa-history'></i></a>";

                if ($user?->can('recurring-transaction.run-now') && $item->status === Status::ACTIVE) {
                    $html .= "<button type='button' class='btn btn-icon btn-outline-success mr-2 runNowRecurring' data-id='{$item->recurring_transaction_id}' title='Run Now'><i class='fa fa-play'></i></button>";
                }
                if ($user?->can('recurring-transaction.pause') && $item->status === Status::ACTIVE) {
                    $html .= "<button type='button' class='btn btn-icon btn-outline-warning mr-2 pauseRecurring' data-id='{$item->recurring_transaction_id}' title='Pause'><i class='fa fa-pause'></i></button>";
                }
                if ($user?->can('recurring-transaction.resume') && $item->status === Status::PAUSED) {
                    $html .= "<button type='button' class='btn btn-icon btn-outline-success mr-2 resumeRecurring' data-id='{$item->recurring_transaction_id}' title='Resume'><i class='fa fa-play-circle'></i></button>";
                }
                if ($user?->can('recurring-transaction.cancel') && in_array($item->status, [Status::ACTIVE, Status::PAUSED])) {
                    $html .= "<button type='button' class='btn btn-icon btn-outline-danger mr-2 cancelRecurring' data-id='{$item->recurring_transaction_id}' title='Cancel'><i class='fa fa-ban'></i></button>";
                }
                if ($user?->can('recurring-transaction.delete') && (int) $item->occurrences_count === 0) {
                    $html .= "<a class='btn btn-icon btn-outline-danger' id='deleteRecurringTransaction' data-id='{$item->recurring_transaction_id}'><i class='fa fa-trash'></i></a>";
                }

                return $html;
            })
            ->rawColumns(['status', 'action'])
            ->make(true);
    }

    public function save($obj)
    {
        DB::beginTransaction();

        try {
            $type = $obj['transaction_type'] ?? null;

            if (!in_array($type, \App\Enums\RecurringTransactionType::all(), true)) {
                throw new Exception('Invalid recurring transaction type.');
            }

            $this->assertScheduleFields($obj);

            $template_data = RecurringTemplateValidatorRegistry::validate($type, $obj['template_data'] ?? []);

            $business_id = $obj['business_id'] ?? Auth::user()->business_id;
            $branch_id = $obj['branch_id'] ?? Auth::user()->branch_id ?? null;

            $data = [
                'business_id'       => $business_id,
                'branch_id'         => $branch_id,
                'transaction_type'  => $type,
                'name'              => $obj['name'],
                'frequency'         => $obj['frequency'],
                'weekday'           => $obj['frequency'] === RecurringFrequency::WEEKLY ? $obj['weekday'] : null,
                'day_of_month'      => in_array($obj['frequency'], [RecurringFrequency::MONTHLY, RecurringFrequency::YEARLY], true) ? $obj['day_of_month'] : null,
                'month_of_year'     => $obj['frequency'] === RecurringFrequency::YEARLY ? $obj['month_of_year'] : null,
                'start_date'        => $obj['start_date'],
                'end_date'          => $obj['end_date'] ?? null,
                'max_occurrences'   => $obj['max_occurrences'] ?? null,
                'auto_post'         => !empty($obj['auto_post']),
                'notes'             => $obj['notes'] ?? null,
                'template_data'     => $template_data,
            ];

            if (!empty($obj['recurring_transaction_id'])) {
                $rt = $this->model->getModel()::findOrFail($obj['recurring_transaction_id']);

                if (in_array($rt->status, [Status::COMPLETED, Status::CANCELLED], true)) {
                    throw new Exception('Completed or cancelled schedules cannot be edited.');
                }

                $data['updatedby_id'] = Auth::id();
                $data['date_updated'] = now();

                // Only reschedule if this template has never fired yet - editing
                // notes/amount on a running schedule shouldn't disturb its cadence.
                if ((int) $rt->occurrences_count === 0) {
                    $rt->fill($data);
                    $data['next_run_date'] = RecurringScheduleCalculator::initialRunDate($rt);
                }

                $rt->update($data);

                $this->logActivity('recurring_transaction', $rt->recurring_transaction_id, 'updated', null, ['name' => $rt->name]);
            } else {
                $data['recurring_transaction_id'] = generateUuid();
                $data['status'] = Status::ACTIVE;
                $data['occurrences_count'] = 0;
                $data['is_deleted'] = 0;
                $data['createdby_id'] = Auth::id();
                $data['date_created'] = now();

                $rt = $this->model->getModel()::create($data);
                $rt->update(['next_run_date' => RecurringScheduleCalculator::initialRunDate($rt)]);

                $this->logActivity('recurring_transaction', $rt->recurring_transaction_id, 'created', null, ['name' => $rt->name]);
            }

            DB::commit();

            return $rt->fresh($this->with);
        } catch (Exception $e) {
            DB::rollBack();

            throw $e;
        }
    }

    protected function assertScheduleFields(array $obj): void
    {
        if (empty($obj['name']) || empty($obj['frequency']) || empty($obj['start_date'])) {
            throw new Exception('Name, frequency and start date are required.');
        }

        if (!in_array($obj['frequency'], RecurringFrequency::all(), true)) {
            throw new Exception('Invalid frequency.');
        }

        if ($obj['frequency'] === RecurringFrequency::WEEKLY && (!isset($obj['weekday']) || $obj['weekday'] === '')) {
            throw new Exception('Please select a day of the week.');
        }

        if (in_array($obj['frequency'], [RecurringFrequency::MONTHLY, RecurringFrequency::YEARLY], true)
            && (empty($obj['day_of_month']) && $obj['day_of_month'] !== '0')) {
            throw new Exception('Please select a day of the month.');
        }

        if ($obj['frequency'] === RecurringFrequency::YEARLY && empty($obj['month_of_year'])) {
            throw new Exception('Please select a month.');
        }
    }

    public function getById($recurring_transaction_id)
    {
        return $this->model->getModel()::with($this->with)->find($recurring_transaction_id);
    }

    public function pause($recurring_transaction_id)
    {
        $rt = $this->model->getModel()::findOrFail($recurring_transaction_id);

        if ($rt->status !== Status::ACTIVE) {
            throw new Exception('Only active schedules can be paused.');
        }

        $rt->update(['status' => Status::PAUSED, 'updatedby_id' => Auth::id(), 'date_updated' => now()]);
        $this->logActivity('recurring_transaction', $rt->recurring_transaction_id, 'paused');

        return $rt;
    }

    public function resume($recurring_transaction_id)
    {
        $rt = $this->model->getModel()::findOrFail($recurring_transaction_id);

        if ($rt->status !== Status::PAUSED) {
            throw new Exception('Only paused schedules can be resumed.');
        }

        $next_run_date = $rt->next_run_date && Carbon::parse($rt->next_run_date)->gte(today())
            ? $rt->next_run_date
            : RecurringScheduleCalculator::nextRunDate($rt, today()->subDay());

        $rt->update([
            'status'        => Status::ACTIVE,
            'next_run_date' => $next_run_date,
            'updatedby_id'  => Auth::id(),
            'date_updated'  => now(),
        ]);
        $this->logActivity('recurring_transaction', $rt->recurring_transaction_id, 'resumed');

        return $rt;
    }

    public function cancel($recurring_transaction_id)
    {
        $rt = $this->model->getModel()::findOrFail($recurring_transaction_id);

        if (!in_array($rt->status, [Status::ACTIVE, Status::PAUSED], true)) {
            throw new Exception('Only active or paused schedules can be cancelled.');
        }

        $rt->update([
            'status'        => Status::CANCELLED,
            'next_run_date' => null,
            'updatedby_id'  => Auth::id(),
            'date_updated'  => now(),
        ]);
        $this->logActivity('recurring_transaction', $rt->recurring_transaction_id, 'cancelled');

        return $rt;
    }

    public function delete($recurring_transaction_id)
    {
        $rt = $this->model->getModel()::findOrFail($recurring_transaction_id);

        if ((int) $rt->occurrences_count > 0) {
            throw new Exception('This schedule has already generated transactions - cancel it instead of deleting.');
        }

        $rt->update([
            'is_deleted'   => 1,
            'status'       => Status::CANCELLED,
            'next_run_date' => null,
            'deletedby_id' => Auth::id(),
            'date_deleted' => now(),
        ]);

        $this->logActivity('recurring_transaction', $rt->recurring_transaction_id, 'deleted');

        return true;
    }

    /**
     * "Run Now" - manual, permissioned, one-off trigger for today's occurrence.
     * Shares the exact same generation/outcome logic as the scheduled command
     * (executeSchedule()) so a manual run and a scheduled run behave
     * identically and are protected by the same idempotency guards.
     */
    public function runNow($recurring_transaction_id, ?int $actingUserId)
    {
        $rt = $this->model->getModel()::findOrFail($recurring_transaction_id);

        if ($rt->status !== Status::ACTIVE) {
            throw new Exception('Only active schedules can be run now.');
        }

        $already_ran_today = RecurringTransactionRun::where('recurring_transaction_id', $rt->recurring_transaction_id)
            ->where('run_date', today()->toDateString())
            ->exists();

        if ($already_ran_today) {
            throw new Exception('This schedule has already generated a transaction today.');
        }

        return $this->executeSchedule($rt, today(), RecurringTriggeredBy::MANUAL, $actingUserId);
    }

    /**
     * Locks the schedule row, generates the transaction via the registered
     * generator for its type, applies the resulting run's outcome (advance
     * next_run_date/occurrences_count/last_run_date, or flip to completed),
     * and dispatches a failure notification if needed. Used by both
     * runNow() above and App\Console\Commands\ProcessRecurringTransactionsCommand.
     */
    public function executeSchedule(RecurringTransaction $rt, Carbon $occurrenceDate, string $triggeredBy, ?int $triggeredByUserId): RecurringTransactionRun
    {
        return DB::transaction(function () use ($rt, $occurrenceDate, $triggeredBy, $triggeredByUserId) {
            $locked = $this->model->getModel()::where('recurring_transaction_id', $rt->recurring_transaction_id)
                ->lockForUpdate()
                ->firstOrFail();

            $run = $this->generator_registry->resolve($locked->transaction_type)
                ->generate($locked, $occurrenceDate, $triggeredBy, $triggeredByUserId);

            $update = ['updatedby_id' => $triggeredByUserId, 'date_updated' => now()];

            if ($run->status === RecurringRunStatus::SUCCESS) {
                $update['occurrences_count'] = $locked->occurrences_count + 1;
                $update['last_run_date'] = $occurrenceDate->toDateString();
            } else {
                $this->dispatcher->dispatch(
                    'recurring_transaction_failed',
                    $locked->business_id,
                    $locked->branch_id,
                    'Recurring Transaction Failed',
                    '"' . $locked->name . '" failed to generate for ' . $occurrenceDate->toDateString() . ': ' . $run->error_message,
                    'recurring_transaction',
                    $locked->recurring_transaction_id,
                    route('recurring-transaction.history', $locked->recurring_transaction_id),
                    ['run_id' => $run->recurring_transaction_run_id],
                    $occurrenceDate->toDateString() . '-' . $locked->recurring_transaction_id . '-failed'
                );
            }

            $computed_next = RecurringScheduleCalculator::nextRunDate($locked, $occurrenceDate);
            $reached_max = $locked->max_occurrences && ($update['occurrences_count'] ?? $locked->occurrences_count) >= $locked->max_occurrences;
            $past_end = $locked->end_date && $computed_next->gt(Carbon::parse($locked->end_date));

            if ($reached_max || $past_end) {
                $update['status'] = Status::COMPLETED;
                $update['next_run_date'] = null;
            } else {
                $update['next_run_date'] = $computed_next;
            }

            $locked->update($update);

            return $run;
        });
    }

    public function previewNextRun(array $fields, int $count = 5): array
    {
        $rt = new RecurringTransaction([
            'frequency'     => $fields['frequency'] ?? null,
            'weekday'       => $fields['weekday'] ?? null,
            'day_of_month'  => $fields['day_of_month'] ?? null,
            'month_of_year' => $fields['month_of_year'] ?? null,
            'start_date'    => $fields['start_date'] ?? now()->toDateString(),
            'end_date'      => $fields['end_date'] ?? null,
        ]);

        return array_map(fn ($date) => $date->toDateString(), RecurringScheduleCalculator::upcoming($rt, $count));
    }

    public function getExecutionHistory($recurring_transaction_id, $obj)
    {
        $orderBy = Filter::ORDERBY;
        if (isset($obj['orderBy']) && $obj['orderBy'] != 0 && $obj['orderBy'] != "") {
            $orderBy = $obj['orderBy'];
        }

        $datatable = RecurringTransactionRun::where('recurring_transaction_id', $recurring_transaction_id)
            ->orderBy('run_date', $orderBy);

        return DataTables::of($datatable)
            ->addColumn('run_date', function ($item) {
                return localDate($item->run_date);
            })
            ->addColumn('status', function ($item) {
                $badges = [
                    RecurringRunStatus::SUCCESS => 'success',
                    RecurringRunStatus::FAILED  => 'danger',
                    RecurringRunStatus::SKIPPED => 'secondary',
                ];
                $color = $badges[$item->status] ?? 'secondary';
                return "<span class='badge bg-{$color}'>" . ucfirst($item->status) . "</span>";
            })
            ->addColumn('generated', function ($item) {
                if (!$item->generated_model_id) {
                    return 'N/A';
                }
                if ($item->generated_model_type === \App\Enums\RecurringTransactionType::EXPENSE) {
                    return "<a href='" . route('expense.edit', $item->generated_model_id) . "'>View Expense</a>";
                }
                if ($item->generated_model_type === \App\Enums\RecurringTransactionType::JOURNAL_ENTRY) {
                    return "<a href='" . route('journal-entry.edit', $item->generated_model_id) . "'>View Journal Entry</a>";
                }
                return $item->generated_model_id;
            })
            ->addColumn('triggered_by', function ($item) {
                return ucfirst($item->triggered_by);
            })
            ->rawColumns(['status', 'generated'])
            ->make(true);
    }
}
