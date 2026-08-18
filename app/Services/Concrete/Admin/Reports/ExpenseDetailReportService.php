<?php

namespace App\Services\Concrete\Admin\Reports;

use App\Enums\RoleNames;
use App\Enums\Status;
use App\Models\Expense;
use Carbon\Carbon;
use Yajra\DataTables\DataTables;

/**
 * Transactional Expense Detail report - every recorded Expense (POS or
 * Admin), filterable by date range, branch, OT/User, Admin User, and POS
 * Session. Distinct from the account-balance ExpenseReportService, which
 * summarizes posted journal_entry_details by Chart of Accounts Expense
 * account instead of listing individual expense records.
 */
class ExpenseDetailReportService
{
    protected $with = [
        'business',
        'branch',
        'category',
        'posSession',
        'posSession.register',
        'user',
        'createdby',
        'postedby',
        'expenseAccount',
    ];

    protected array $allow_roles = [
        RoleNames::SUPERADMIN,
        RoleNames::BUSINESSADMIN,
        RoleNames::FINANCEMANAGER,
        RoleNames::ACCOUNTANT,
        RoleNames::REPORTINGANALYST,
        RoleNames::BRANCHADMIN,
        RoleNames::POSMANAGER,
    ];

    public function build(array $obj)
    {
        $wh = [];

        if (!empty($obj['business_id'])) {
            $wh[] = ['business_id', $obj['business_id']];
        }
        if (!empty($obj['branch_id'])) {
            $wh[] = ['branch_id', $obj['branch_id']];
        }
        if (!empty($obj['user_id'])) {
            $wh[] = ['user_id', $obj['user_id']];
        }
        if (!empty($obj['createdby_id'])) {
            $wh[] = ['createdby_id', $obj['createdby_id']];
        }
        if (!empty($obj['pos_register_session_id'])) {
            $wh[] = ['pos_register_session_id', $obj['pos_register_session_id']];
        }
        if (!empty($obj['expense_category_id'])) {
            $wh[] = ['expense_category_id', $obj['expense_category_id']];
        }
        if (!empty($obj['source'])) {
            $wh[] = ['source', $obj['source']];
        }
        if (!empty($obj['status'])) {
            $wh[] = ['status', $obj['status']];
        }

        $query = Expense::with($this->with)
            ->where($wh)
            ->where('is_deleted', 0);

        if (!empty($obj['start_date'])) {
            $query->where('expense_date', '>=', Carbon::parse($obj['start_date'])->startOfDay());
        }
        if (!empty($obj['end_date'])) {
            $query->where('expense_date', '<=', Carbon::parse($obj['end_date'])->endOfDay());
        }

        $query = applyRoleScope($query, $this->allow_roles);

        return $query->orderByDesc('expense_date');
    }

    public function getData(array $obj)
    {
        $rows = $this->build($obj)->get();

        $totals = [
            'total_amount'  => currency(round($rows->where('status', '!=', Status::CANCELLED)->sum('amount'), 2)),
            'posted_amount' => currency(round($rows->where('status', Status::POSTED)->sum('amount'), 2)),
            'pending_amount' => currency(round($rows->where('status', Status::PENDING)->sum('amount'), 2)),
        ];

        return DataTables::of($rows)
            ->addColumn('expense_date', fn ($item) => !empty($item->expense_date) ? localDate($item->expense_date) : 'N/A')
            ->addColumn('category', fn ($item) => $item->category->name ?? '')
            ->addColumn('amount', fn ($item) => currency($item->amount ?? 0))
            ->addColumn('branch', fn ($item) => $item->branch->name ?? '')
            ->addColumn('business', fn ($item) => $item->business->name ?? '')
            ->addColumn('user', fn ($item) => $item->user->name ?? '')
            ->addColumn('admin_user', fn ($item) => $item->createdby->name ?? '')
            ->addColumn('session', fn ($item) => $item->posSession
                ? ($item->posSession->register->name ?? 'Session') . ' (' . localDate($item->posSession->opening_datetime) . ')'
                : 'N/A')
            ->addColumn('source', fn ($item) => ucfirst($item->source ?? ''))
            ->addColumn('status', fn ($item) => ucfirst($item->status ?? ''))
            ->addColumn('accounting', fn ($item) => $item->status === Status::POSTED
                ? ($item->expenseAccount->code ?? '') . ' ' . ($item->expenseAccount->name ?? '')
                : 'Not Posted')
            ->with($totals)
            ->make(true);
    }
}
