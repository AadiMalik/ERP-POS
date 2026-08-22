<?php

namespace App\Services\Concrete\Admin\Reports;

use App\Enums\RoleNames;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;

class CustomerLedgerReportService
{
    protected array $allow_roles = [
        RoleNames::SUPERADMIN,
        RoleNames::BUSINESSADMIN,
        RoleNames::FINANCEMANAGER,
        RoleNames::ACCOUNTANT,
        RoleNames::REPORTINGANALYST,
        RoleNames::SALEMANAGER,
    ];

    public function __construct(protected CustomerLedgerQueryService $ledger_query_service)
    {
    }

    /**
     * Builds the full ledger dataset (opening balance, ordered transactions
     * with a running balance, closing balance) for one customer. Shared by
     * the DataTable, print, PDF and export actions so they never diverge.
     * Mirrors SupplierLedgerReportService::build().
     */
    public function build(array $obj): array
    {
        $user_id = $obj['user_id'] ?? null;

        if (empty($user_id)) {
            return $this->emptyResult();
        }

        $business_id = $obj['business_id'] ?? Auth::user()->business_id;
        $branch_id = $obj['branch_id'] ?? null;

        $customer = $this->ledger_query_service->resolveCustomerProfile($user_id, $business_id);
        $account_id = $this->ledger_query_service->resolveAccountId($customer, $business_id);

        if (empty($account_id)) {
            throw new Exception('The selected customer does not have a linked Chart of Account.');
        }

        $from = !empty($obj['start_date']) ? Carbon::parse($obj['start_date'])->startOfDay() : null;
        $to = !empty($obj['end_date']) ? Carbon::parse($obj['end_date'])->endOfDay() : null;

        $opening = $this->ledger_query_service->openingBalance($business_id, $branch_id, $user_id, $account_id, $from, $this->allow_roles);

        $rows = $this->ledger_query_service->customerTransactions($business_id, $branch_id, $user_id, $account_id, $from, $to, $this->allow_roles);

        $rows = $this->ledger_query_service->withRunningBalance($rows, $opening['raw']);

        $totalDebit = round((float) $rows->sum('debit'), 2);
        $totalCredit = round((float) $rows->sum('credit'), 2);
        $closingRaw = $opening['raw'] + $totalCredit - $totalDebit;

        return [
            'customer'             => $customer,
            'opening_balance'      => $opening['balance'],
            'opening_balance_type' => $opening['type'],
            'closing_balance'      => round(abs($closingRaw), 2),
            'closing_balance_type' => $closingRaw > 0 ? 'Cr' : ($closingRaw < 0 ? 'Dr' : ''),
            'total_debit'          => $totalDebit,
            'total_credit'         => $totalCredit,
            'start_date'           => $from,
            'end_date'             => $to,
            'rows'                 => $rows,
        ];
    }

    public function getData(array $obj)
    {
        $result = $this->build($obj);

        return DataTables::of($result['rows'])
            ->addColumn('document_date', fn ($row) => localDate($row->entry_date))
            ->addColumn('voucher_date', fn ($row) => localDate($row->entry_date))
            ->addColumn('voucher_type', fn ($row) => $row->voucher_name ?? $row->source_type ?? '')
            ->addColumn('voucher_number', fn ($row) => $row->entry_no)
            ->addColumn('reference_number', fn ($row) => $row->reference_no)
            ->addColumn('description', fn ($row) => $row->detail_description ?: $row->entry_description)
            ->addColumn('debit', fn ($row) => $row->debit > 0 ? currency($row->debit) : '')
            ->addColumn('credit', fn ($row) => $row->credit > 0 ? currency($row->credit) : '')
            ->addColumn('running_balance', fn ($row) => currency($row->running_balance) . ' ' . $row->running_balance_type)
            ->with([
                'opening_balance'      => currency($result['opening_balance']) . ' ' . $result['opening_balance_type'],
                'closing_balance'      => currency($result['closing_balance']) . ' ' . $result['closing_balance_type'],
                'total_debit'          => currency($result['total_debit']),
                'total_credit'         => currency($result['total_credit']),
            ])
            ->make(true);
    }

    protected function emptyResult(): array
    {
        return [
            'customer'             => null,
            'opening_balance'      => 0,
            'opening_balance_type' => '',
            'closing_balance'      => 0,
            'closing_balance_type' => '',
            'total_debit'          => 0,
            'total_credit'         => 0,
            'start_date'           => null,
            'end_date'             => null,
            'rows'                 => collect(),
        ];
    }
}
