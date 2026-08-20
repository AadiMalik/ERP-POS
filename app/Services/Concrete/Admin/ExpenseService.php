<?php

namespace App\Services\Concrete\Admin;

use App\Enums\Filter;
use App\Enums\JournalSourceTypes;
use App\Enums\PaymentMethod;
use App\Enums\RoleNames;
use App\Enums\Status;
use App\Models\AccountingSetting;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\JournalEntryDetail;
use App\Models\PosRegisterSession;
use App\Repository\Repository;
use App\Traits\Auditable;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

class ExpenseService
{
    use Auditable;

    protected $model_expense;
    protected $with = [
        'business',
        'branch',
        'category',
        'posSession',
        'user',
        'paymentAccount',
        'expenseAccount',
        'postedby',
        'createdby',
    ];

    public function __construct()
    {
        $this->model_expense = new Repository(new Expense());
    }

    public function getData($obj)
    {
        $wh = [];
        $orderBy = Filter::ORDERBY;

        if (isset($obj['orderBy']) && $obj['orderBy'] != 0 && $obj['orderBy'] != "") {
            $orderBy = $obj['orderBy'];
        }
        if (isset($obj['business_id']) && $obj['business_id'] != 0 && $obj['business_id'] != "") {
            $wh[] = ['business_id', $obj['business_id']];
        }
        if (isset($obj['branch_id']) && $obj['branch_id'] != 0 && $obj['branch_id'] != "") {
            $wh[] = ['branch_id', $obj['branch_id']];
        }
        if (isset($obj['expense_category_id']) && $obj['expense_category_id'] != 0 && $obj['expense_category_id'] != "") {
            $wh[] = ['expense_category_id', $obj['expense_category_id']];
        }
        if (isset($obj['user_id']) && $obj['user_id'] != 0 && $obj['user_id'] != "") {
            $wh[] = ['user_id', $obj['user_id']];
        }
        if (isset($obj['createdby_id']) && $obj['createdby_id'] != 0 && $obj['createdby_id'] != "") {
            $wh[] = ['createdby_id', $obj['createdby_id']];
        }
        if (isset($obj['pos_register_session_id']) && $obj['pos_register_session_id'] != 0 && $obj['pos_register_session_id'] != "") {
            $wh[] = ['pos_register_session_id', $obj['pos_register_session_id']];
        }
        if (isset($obj['source']) && $obj['source'] != 0 && $obj['source'] != "") {
            $wh[] = ['source', $obj['source']];
        }
        if (isset($obj['status']) && $obj['status'] != 0 && $obj['status'] != "") {
            $wh[] = ['status', $obj['status']];
        }
        if (!empty($obj['no_session'])) {
            $wh[] = ['pos_register_session_id', null];
        }
        if (!empty($obj['start_date'])) {
            $wh[] = ['expense_date', '>=', Carbon::parse($obj['start_date'])->startOfDay()];
        }
        if (!empty($obj['end_date'])) {
            $wh[] = ['expense_date', '<=', Carbon::parse($obj['end_date'])->endOfDay()];
        }

        $allow_roles = [
            RoleNames::SUPERADMIN,
            RoleNames::BUSINESSADMIN,
            RoleNames::FINANCEMANAGER,
            RoleNames::ACCOUNTANT,
            RoleNames::BRANCHADMIN,
            RoleNames::POSMANAGER,
            RoleNames::ORDERTAKER,
        ];

        $datatable = $this->model_expense->getModel()::with($this->with)
            ->where($wh)
            ->where('is_deleted', 0)
            ->orderBy('expense_date', $orderBy);
        $datatable = applyRoleScope($datatable, $allow_roles);

        return DataTables::of($datatable)
            ->addColumn('expense_date', function ($item) {
                return !empty($item->expense_date) ? localDate($item->expense_date) : 'N/A';
            })
            ->addColumn('category', function ($item) {
                return $item->category->name ?? '';
            })
            ->addColumn('user', function ($item) {
                return $item->user->name ?? '';
            })
            ->addColumn('session', function ($item) {
                return $item->posSession ? ($item->posSession->register->name ?? 'Session') . ' - ' . localDate($item->posSession->opening_datetime) : 'N/A';
            })
            ->addColumn('branch', function ($item) {
                return $item->branch->name ?? '';
            })
            ->addColumn('business', function ($item) {
                return $item->business->name ?? '';
            })
            ->addColumn('source', function ($item) {
                return ucfirst($item->source ?? '');
            })
            ->addColumn('amount', function ($item) {
                return currency($item->amount ?? 0);
            })
            ->addColumn('status', function ($item) {
                if ($item->status === Status::CANCELLED) {
                    return '<span class="badge bg-danger">Cancelled</span>';
                }

                $statuses = [
                    Status::PENDING => ucfirst(Status::PENDING),
                    Status::POSTED  => ucfirst(Status::POSTED),
                ];

                $html = "<select class='form-select form-select-sm change-status'
                data-id='{$item->expense_id}'>";

                foreach ($statuses as $value => $label) {
                    $selected = $item->status == $value ? 'selected' : '';
                    $html .= "<option value='{$value}' {$selected}>{$label}</option>";
                }

                $html .= "</select>";

                return $html;
            })
            ->addColumn('action', function ($item) use ($obj) {
                $edit_route = !empty($obj['no_session']) ? 'admin-expense.edit' : 'expense.edit';

                $editButton = $item->status === Status::PENDING
                    ? "<a class='btn btn-icon btn-outline-primary mr-2'
                        href='" . route($edit_route, $item->expense_id) . "'
                        id='editExpense'>
                        <i class='fa fa-pencil'></i>
                        </a>"
                    : "<button type='button' class='btn btn-icon btn-outline-primary mr-2' disabled
                        title='Unpost before editing'>
                        <i class='fa fa-pencil'></i>
                        </button>";

                $deleteButton = $item->status !== Status::CANCELLED
                    ? "<a class='btn btn-icon btn-outline-danger'
                    id='deleteExpense'
                    data-id='{$item->expense_id}'>
                    <i class='fa fa-trash'></i>
                    </a>"
                    : '';

                return $editButton . $deleteButton;
            })
            ->rawColumns(['category', 'user', 'session', 'branch', 'business', 'source', 'amount', 'status', 'action'])
            ->make(true);
    }

    public function save($obj)
    {
        DB::beginTransaction();

        try {
            $session = null;

            if (!empty($obj['pos_register_session_id'])) {
                $session = PosRegisterSession::where('pos_register_session_id', $obj['pos_register_session_id'])
                    ->where('is_deleted', 0)
                    ->first();

                if (!$session) {
                    throw new Exception('The selected POS register session was not found.');
                }
            }

            $business_id = $session->business_id ?? ($obj['business_id'] ?? Auth::user()->business_id);
            $branch_id = $session->branch_id ?? ($obj['branch_id'] ?? Auth::user()->branch_id ?? null);

            $category = ExpenseCategory::where('expense_category_id', $obj['expense_category_id'])
                ->where('is_deleted', 0)
                ->first();

            if (!$category) {
                throw new Exception('Selected expense category not found.');
            }

            $accounting_setting = AccountingSetting::where('business_id', $business_id)->first();

            if (!empty($accounting_setting) && $accounting_setting->manual_payment_account_selection) {
                $payment_account_id = $obj['payment_account_id'] ?? null;
            } else {
                $payment_account_id = ($obj['payment_method'] ?? PaymentMethod::CASH) === PaymentMethod::CASH
                    ? ($accounting_setting->default_cash_account_id ?? null)
                    : ($accounting_setting->default_bank_account_id ?? null);
            }

            $expense_account_id = $category->account_id ?? ($accounting_setting->default_expense_account_id ?? null);

            $amount = (float) ($obj['amount'] ?? 0);

            if ($amount <= 0) {
                throw new Exception('Expense amount must be greater than zero.');
            }

            $data = [
                'business_id'              => $business_id,
                'branch_id'                => $branch_id,
                'expense_category_id'      => $category->expense_category_id,
                'pos_register_session_id'  => $session->pos_register_session_id ?? null,
                'user_id'                  => $obj['user_id'] ?? ($session->cashier_id ?? null),
                'expense_date'             => $obj['expense_date'] ?? now(),
                'amount'                   => $amount,
                'payment_method'           => $obj['payment_method'] ?? PaymentMethod::CASH,
                'payment_account_id'       => $payment_account_id,
                'expense_account_id'       => $expense_account_id,
                'reference_no'             => $obj['reference_no'] ?? null,
                'description'              => $obj['description'] ?? null,
                'source'                   => $obj['source'] ?? (!empty($session) ? 'pos' : 'admin'),
            ];

            if (!empty($obj['attachment'])) {
                $data['attachment'] = $obj['attachment'];
            }

            //====================================
            // Update
            //====================================

            if (!empty($obj['expense_id'])) {
                $expense = $this->model_expense->getModel()::findOrFail($obj['expense_id']);

                if ($expense->status !== Status::PENDING) {
                    throw new Exception('Only pending expenses can be updated.');
                }

                $old_values = $expense->only(['amount', 'expense_category_id', 'payment_method', 'description']);

                $data['updatedby_id'] = Auth::id();
                $data['date_updated'] = now();

                $expense->update($data);

                $this->logActivity('expense', $expense->expense_id, 'updated', $old_values, $expense->only(['amount', 'expense_category_id', 'payment_method', 'description']));
            }

            //====================================
            // Create
            //====================================

            else {
                $data['expense_id'] = generateUuid();
                $data['expense_no'] = generateExpenseNo($business_id);
                $data['status'] = Status::PENDING;
                $data['is_deleted'] = 0;
                $data['createdby_id'] = Auth::id();
                $data['date_created'] = now();

                $expense = $this->model_expense->create($data);

                $this->logActivity('expense', $expense->expense_id, 'created', null, $expense->only(['amount', 'expense_category_id', 'payment_method', 'description']));
            }

            // Quick actions (e.g. the POS "Add Expense" modal) save and post
            // in one step, since there is no separate pending-review screen
            // there - the till cash changes the moment the OT logs it.
            if (!empty($obj['auto_post'])) {
                $expense->update([
                    'status'       => Status::POSTED,
                    'postedby_id'  => Auth::id(),
                    'date_posted'  => now(),
                ]);
                $this->applyPosting($expense->fresh());
            }

            DB::commit();

            return $expense->fresh($this->with);
        } catch (Exception $e) {
            DB::rollBack();

            throw $e;
        }
    }

    public function getById($expense_id)
    {
        return $this->model_expense->getModel()::with($this->with)->find($expense_id);
    }

    public function getDetails($expense_id)
    {
        $expense = $this->model_expense->getModel()::with($this->with)->findOrFail($expense_id);

        return [
            'expense_id'              => $expense->expense_id,
            'business_id'             => $expense->business_id,
            'branch_id'               => $expense->branch_id,
            'expense_category_id'     => $expense->expense_category_id,
            'pos_register_session_id' => $expense->pos_register_session_id,
            'user_id'                 => $expense->user_id,
            'expense_no'              => $expense->expense_no,
            'expense_date'            => $expense->expense_date,
            'amount'                  => $expense->amount,
            'payment_method'          => $expense->payment_method,
            'payment_account_id'      => $expense->payment_account_id,
            'expense_account_id'      => $expense->expense_account_id,
            'reference_no'            => $expense->reference_no,
            'description'             => $expense->description,
            'attachment'              => $expense->attachment,
            'source'                  => $expense->source,
            'status'                  => $expense->status,
        ];
    }

    public function status($obj)
    {
        DB::beginTransaction();

        try {
            $expense = $this->model_expense->getModel()::with($this->with)->findOrFail($obj['expense_id']);
            $old_status = $expense->status;
            $new_status = $obj['status'];

            $update = [
                'status'       => $new_status,
                'updatedby_id' => Auth::id(),
                'date_updated' => now(),
            ];

            if ($new_status === Status::POSTED && $old_status !== Status::POSTED) {
                $update['postedby_id'] = Auth::id();
                $update['date_posted'] = now();
            }

            $expense->update($update);

            if ($new_status === Status::POSTED && $old_status !== Status::POSTED) {
                $this->applyPosting($expense);
            } elseif ($old_status === Status::POSTED && $new_status !== Status::POSTED) {
                $this->reversePosting($expense);
            }

            DB::commit();

            $this->logActivity(
                'expense',
                $expense->expense_id,
                $new_status === Status::POSTED ? 'posted' : 'unposted',
                ['status' => $old_status],
                ['status' => $new_status]
            );
        } catch (Exception $e) {
            DB::rollBack();

            throw $e;
        }

        return $expense;
    }

    public function delete($expense_id)
    {
        DB::beginTransaction();

        try {
            $expense = $this->model_expense->getModel()::with($this->with)->findOrFail($expense_id);

            if ($expense->status === Status::POSTED) {
                $this->reversePosting($expense);
            }

            $expense->update([
                'is_deleted'   => 1,
                'status'       => Status::CANCELLED,
                'deletedby_id' => Auth::id(),
                'date_deleted' => now(),
            ]);

            DB::commit();

            $this->logActivity('expense', $expense->expense_id, 'deleted', ['amount' => $expense->amount], null, 'Expense deleted/cancelled');

            return true;
        } catch (Exception $e) {
            DB::rollBack();

            throw $e;
        }
    }

    /**
     * Auto-post a CPV/BPV Journal Voucher (Dr Expense Account / Cr Cash-Bank
     * Account) when an Expense is posted. Idempotent: a no-op if an active
     * voucher already exists for this expense.
     */
    protected function applyPosting(Expense $expense)
    {
        $existing = JournalEntry::where('source_type', JournalSourceTypes::EXPENSE)
            ->where('source_id', $expense->expense_id)
            ->where('is_deleted', 0)
            ->exists();

        if ($existing) {
            return;
        }

        $accounting_setting = AccountingSetting::where('business_id', $expense->business_id)->first();

        if (!$accounting_setting || !$accounting_setting->enable_accounting) {
            throw new Exception('Accounting is not enabled for this business. Please configure Accounting Settings before posting expenses.');
        }

        app(\App\Services\Concrete\Admin\AccountingPeriodService::class)->assertPostable($expense->business_id, now());

        if (empty($expense->expense_account_id)) {
            throw new Exception('No Expense Account is configured. Please link an account to this expense category, or set a default expense account in Accounting Settings.');
        }

        if (empty($expense->payment_account_id)) {
            throw new Exception('No Cash/Bank payment account is configured for this expense. Please configure default Cash/Bank accounts in Accounting Settings.');
        }

        $short = $expense->payment_method === PaymentMethod::CASH ? 'CPV' : 'BPV';

        $journal = Journal::where('short', $short)->where('is_deleted', 0)->first();

        if (!$journal) {
            throw new Exception('No "' . $short . '" journal category found. Please configure it before posting expenses.');
        }

        $entry_no = generateJVNum($journal->journal_id);

        $journal_entry = JournalEntry::create([
            'journal_entry_id' => generateUuid(),
            'journal_id'       => $journal->journal_id,
            'business_id'      => $expense->business_id,
            'branch_id'        => $expense->branch_id,
            'entry_no'         => $entry_no,
            'reference_no'     => $expense->expense_no,
            'entry_date'       => now(),
            'description'      => 'Auto-generated ' . $short . ' for expense ' . $expense->expense_no,
            'source_type'      => JournalSourceTypes::EXPENSE,
            'source_id'        => $expense->expense_id,
            'status'           => Status::POSTED,
            'postedby_id'      => Auth::id(),
            'date_posted'      => now(),
            'createdby_id'     => Auth::id(),
            'date_created'     => now(),
        ]);

        JournalEntryDetail::create([
            'journal_entry_detail_id' => generateUuid(),
            'journal_entry_id'        => $journal_entry->journal_entry_id,
            'account_id'              => $expense->expense_account_id,
            'debit'                   => $expense->amount,
            'credit'                  => 0,
            'user_id'                 => $expense->user_id,
            'description'             => 'Expense - ' . $expense->expense_no,
        ]);

        JournalEntryDetail::create([
            'journal_entry_detail_id' => generateUuid(),
            'journal_entry_id'        => $journal_entry->journal_entry_id,
            'account_id'              => $expense->payment_account_id,
            'debit'                   => 0,
            'credit'                  => $expense->amount,
            'user_id'                 => $expense->user_id,
            'description'             => 'Expense - ' . $expense->expense_no,
        ]);
    }

    /**
     * Reverse the CPV/BPV Journal Voucher created when an Expense was posted.
     * Idempotent: a no-op if nothing active remains to reverse.
     */
    protected function reversePosting(Expense $expense)
    {
        $journal_entry = JournalEntry::where('source_type', JournalSourceTypes::EXPENSE)
            ->where('source_id', $expense->expense_id)
            ->where('is_deleted', 0)
            ->first();

        if ($journal_entry) {
            $journal_entry->update([
                'is_deleted'   => 1,
                'deletedby_id' => Auth::id(),
                'date_deleted' => now(),
            ]);
        }
    }

    public function getByBusiness($business_id)
    {
        return $this->model_expense->getModel()::with($this->with)
            ->where('business_id', $business_id)
            ->where('is_deleted', 0)
            ->get();
    }

    /**
     * Posted expense totals for a POS register session - used by
     * PosRegisterSessionService::getSummary() for the Session Summary's
     * expense breakdown and till cash reconciliation.
     */
    public function getSessionTotals($pos_register_session_id)
    {
        $expenses = $this->model_expense->getModel()::where('pos_register_session_id', $pos_register_session_id)
            ->where('status', Status::POSTED)
            ->where('is_deleted', 0)
            ->get(['amount', 'payment_method']);

        return [
            'total_expenses' => (float) $expenses->sum('amount'),
            'cash_expenses'  => (float) $expenses->where('payment_method', PaymentMethod::CASH)->sum('amount'),
            'expense_count'  => $expenses->count(),
        ];
    }
}
