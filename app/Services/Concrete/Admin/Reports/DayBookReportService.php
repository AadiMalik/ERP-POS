<?php

namespace App\Services\Concrete\Admin\Reports;

use App\Enums\RoleNames;
use App\Models\Account;
use App\Services\Concrete\Admin\Reports\Accounting\AccountingLedgerQueryService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;

/**
 * Chronological view of every posted accounting transaction across all
 * accounts for a date (or date range) - the rawest possible read of the
 * centralized journal. No running balance is shown since rows mix unrelated
 * accounts, which a single running balance can't meaningfully represent;
 * Account Ledger / General Ledger are where the per-account running balance
 * lives.
 */
class DayBookReportService
{
    protected array $allow_roles = [
        RoleNames::SUPERADMIN,
        RoleNames::BUSINESSADMIN,
        RoleNames::FINANCEMANAGER,
        RoleNames::ACCOUNTANT,
        RoleNames::REPORTINGANALYST,
    ];

    public function __construct(protected AccountingLedgerQueryService $ledger_query_service)
    {
    }

    public function build(array $obj): array
    {
        $business_id = $obj['business_id'] ?? Auth::user()->business_id;
        $branch_id = $obj['branch_id'] ?? null;

        $filters = [
            'business_id' => $business_id,
            'branch_id'   => $branch_id,
            'allow_roles' => $this->allow_roles,
        ];

        if (!empty($obj['source_type'])) {
            $filters['source_type'] = $obj['source_type'];
        }

        $from = !empty($obj['start_date']) ? Carbon::parse($obj['start_date'])->startOfDay() : Carbon::today()->startOfDay();
        $to = !empty($obj['end_date']) ? Carbon::parse($obj['end_date'])->endOfDay() : Carbon::today()->endOfDay();

        $rows = $this->ledger_query_service->transactions($filters, $from, $to);

        $accounts = Account::whereIn('account_id', $rows->pluck('account_id')->unique())->get()->keyBy('account_id');

        foreach ($rows as $row) {
            $account = $accounts->get($row->account_id);
            $row->account_code = optional($account)->code;
            $row->account_name = optional($account)->name;
        }

        return [
            'total_debit'  => round((float) $rows->sum('debit'), 2),
            'total_credit' => round((float) $rows->sum('credit'), 2),
            'start_date'   => $from,
            'end_date'     => $to,
            'rows'         => $rows,
        ];
    }

    public function getData(array $obj)
    {
        $result = $this->build($obj);

        return DataTables::of($result['rows'])
            ->addColumn('voucher_date', fn ($row) => localDate($row->entry_date))
            ->addColumn('voucher_type', fn ($row) => $row->voucher_name ?? $row->source_type ?? '')
            ->addColumn('voucher_number', fn ($row) => $row->entry_no)
            ->addColumn('account', fn ($row) => trim(($row->account_code ?? '') . ' ' . ($row->account_name ?? '')))
            ->addColumn('reference_number', fn ($row) => $row->reference_no)
            ->addColumn('description', fn ($row) => $row->detail_description ?: $row->entry_description)
            ->addColumn('debit', fn ($row) => $row->debit > 0 ? currency($row->debit) : '')
            ->addColumn('credit', fn ($row) => $row->credit > 0 ? currency($row->credit) : '')
            ->with([
                'total_debit'  => currency($result['total_debit']),
                'total_credit' => currency($result['total_credit']),
            ])
            ->make(true);
    }
}
