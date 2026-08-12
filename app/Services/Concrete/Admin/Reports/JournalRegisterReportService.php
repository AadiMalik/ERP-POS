<?php

namespace App\Services\Concrete\Admin\Reports;

use App\Enums\RoleNames;
use App\Enums\Status;
use App\Models\JournalEntry;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;

/**
 * Register of Journal Voucher headers (journal_entries), not individual
 * detail lines - JV number, date, journal type, reference, narration, total
 * debit/credit (grouped SUM via join against journal_entry_details, matching
 * this codebase's existing explicit-join query style), status and the
 * posting/creating user. Defaults to posted vouchers only, but - unlike the
 * balance-driven reports in this module - also allows viewing pending
 * vouchers via an explicit status filter, since this is a register of the
 * vouchers themselves rather than a financial-balance report.
 */
class JournalRegisterReportService
{
    protected array $allow_roles = [
        RoleNames::SUPERADMIN,
        RoleNames::BUSINESSADMIN,
        RoleNames::FINANCEMANAGER,
        RoleNames::ACCOUNTANT,
        RoleNames::REPORTINGANALYST,
    ];

    public function build(array $obj)
    {
        $business_id = $obj['business_id'] ?? Auth::user()->business_id;
        $branch_id = $obj['branch_id'] ?? null;

        $query = JournalEntry::query()
            ->leftJoin('journals', 'journals.journal_id', '=', 'journal_entries.journal_id')
            ->leftJoin('journal_entry_details', 'journal_entry_details.journal_entry_id', '=', 'journal_entries.journal_entry_id')
            ->leftJoin('users as posted_users', 'posted_users.id', '=', 'journal_entries.postedby_id')
            ->leftJoin('users as created_users', 'created_users.id', '=', 'journal_entries.createdby_id')
            ->where('journal_entries.is_deleted', 0);

        if (!empty($business_id)) {
            $query->where('journal_entries.business_id', $business_id);
        }
        if (!empty($branch_id)) {
            $query->where('journal_entries.branch_id', $branch_id);
        }
        if (!empty($obj['journal_id'])) {
            $query->where('journal_entries.journal_id', $obj['journal_id']);
        }
        if (!empty($obj['source_type'])) {
            $query->where('journal_entries.source_type', $obj['source_type']);
        }

        $status = $obj['status'] ?? Status::POSTED;
        if ($status !== 'all') {
            $query->where('journal_entries.status', $status);
        }

        applyRoleScope($query, $this->allow_roles, 'journal_entries.business_id', 'journal_entries.branch_id');

        if (!empty($obj['start_date'])) {
            $query->where('journal_entries.entry_date', '>=', Carbon::parse($obj['start_date'])->startOfDay());
        }
        if (!empty($obj['end_date'])) {
            $query->where('journal_entries.entry_date', '<=', Carbon::parse($obj['end_date'])->endOfDay());
        }

        return $query->groupBy(
            'journal_entries.journal_entry_id',
            'journal_entries.entry_no',
            'journal_entries.entry_date',
            'journal_entries.reference_no',
            'journal_entries.description',
            'journal_entries.source_type',
            'journal_entries.status',
            'journals.name',
            'journals.short',
            'posted_users.name',
            'created_users.name'
        )
            ->orderBy('journal_entries.entry_date')
            ->orderBy('journal_entries.entry_no')
            ->selectRaw('
                journal_entries.journal_entry_id,
                journal_entries.entry_no,
                journal_entries.entry_date,
                journal_entries.reference_no,
                journal_entries.description,
                journal_entries.source_type,
                journal_entries.status,
                journals.name as journal_name,
                journals.short as journal_short,
                posted_users.name as posted_by_name,
                created_users.name as created_by_name,
                COALESCE(SUM(journal_entry_details.debit),0) as total_debit,
                COALESCE(SUM(journal_entry_details.credit),0) as total_credit
            ')
            ->get();
    }

    public function getData(array $obj)
    {
        $rows = $this->build($obj);

        $totals = [
            'total_debit'  => currency(round($rows->sum('total_debit'), 2)),
            'total_credit' => currency(round($rows->sum('total_credit'), 2)),
        ];

        return DataTables::of($rows)
            ->addColumn('entry_date', fn ($row) => localDate($row->entry_date))
            ->addColumn('journal_type', fn ($row) => $row->journal_name ?? $row->journal_short)
            ->addColumn('entry_no', fn ($row) => $row->entry_no)
            ->addColumn('source_type', fn ($row) => $row->source_type)
            ->addColumn('reference_no', fn ($row) => $row->reference_no)
            ->addColumn('description', fn ($row) => $row->description)
            ->addColumn('total_debit', fn ($row) => currency($row->total_debit))
            ->addColumn('total_credit', fn ($row) => currency($row->total_credit))
            ->addColumn('status', fn ($row) => ucfirst($row->status))
            ->addColumn('posted_by', fn ($row) => $row->posted_by_name ?? '-')
            ->addColumn('created_by', fn ($row) => $row->created_by_name ?? '-')
            ->with($totals)
            ->make(true);
    }
}
