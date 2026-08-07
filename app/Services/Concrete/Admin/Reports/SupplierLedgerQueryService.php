<?php

namespace App\Services\Concrete\Admin\Reports;

use App\Enums\Status;
use App\Models\JournalEntryDetail;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Shared query layer for every report that reads a supplier's payable
 * balance from posted accounting transactions.
 *
 * Suppliers can share one COA control account (see Supplier::save() -
 * account_id defaults to accounting_settings.default_supplier_account_id
 * for every supplier), and every posting tags supplier_id on its
 * journal_entry_details rows. So every query here filters
 * journal_entry_details.supplier_id AND .account_id together - dropping
 * either condition nets every supplier's debits/credits together.
 */
class SupplierLedgerQueryService
{
    public function resolveSupplierAccount(string $supplier_id): ?Supplier
    {
        return Supplier::with('account')->find($supplier_id);
    }

    public function baseQuery(?string $business_id, ?string $branch_id, string $supplier_id, string $account_id, array $allow_roles = []): Builder
    {
        $query = JournalEntryDetail::query()
            ->join('journal_entries', 'journal_entries.journal_entry_id', '=', 'journal_entry_details.journal_entry_id')
            ->where('journal_entry_details.supplier_id', $supplier_id)
            ->where('journal_entry_details.account_id', $account_id)
            ->where('journal_entries.is_deleted', 0)
            ->where('journal_entries.status', Status::POSTED);

        if (!empty($business_id)) {
            $query->where('journal_entries.business_id', $business_id);
        }

        if (!empty($branch_id)) {
            $query->where('journal_entries.branch_id', $branch_id);
        }

        return applyRoleScope($query, $allow_roles, 'journal_entries.business_id', 'journal_entries.branch_id');
    }

    /**
     * Balance of all posted transactions strictly before $asOfDate. With no
     * $asOfDate, the report period starts from the beginning of all
     * recorded history, so there is definitionally nothing preceding it -
     * returns zero rather than summing every transaction (which would
     * double-count them once the report period's own rows are added on top).
     */
    public function openingBalance(?string $business_id, ?string $branch_id, string $supplier_id, string $account_id, ?Carbon $asOfDate = null, array $allow_roles = []): array
    {
        if (!$asOfDate) {
            return $this->toBalance(0, 0);
        }

        $query = $this->baseQuery($business_id, $branch_id, $supplier_id, $account_id, $allow_roles)
            ->where('journal_entries.entry_date', '<', $asOfDate);

        $totals = $query->selectRaw('COALESCE(SUM(journal_entry_details.debit),0) as total_debit, COALESCE(SUM(journal_entry_details.credit),0) as total_credit')->first();

        return $this->toBalance((float) ($totals->total_debit ?? 0), (float) ($totals->total_credit ?? 0));
    }

    /**
     * Unbounded balance of every posted transaction to date (no period
     * boundary) - the same "live payable balance" semantics as the
     * existing SupplierPaymentService::getSupplierLedger().
     */
    public function totalBalance(?string $business_id, ?string $branch_id, string $supplier_id, string $account_id, array $allow_roles = []): array
    {
        $query = $this->baseQuery($business_id, $branch_id, $supplier_id, $account_id, $allow_roles);

        $totals = $query->selectRaw('COALESCE(SUM(journal_entry_details.debit),0) as total_debit, COALESCE(SUM(journal_entry_details.credit),0) as total_credit')->first();

        return $this->toBalance((float) ($totals->total_debit ?? 0), (float) ($totals->total_credit ?? 0));
    }

    public function supplierTransactions(?string $business_id, ?string $branch_id, string $supplier_id, string $account_id, ?Carbon $from = null, ?Carbon $to = null, array $allow_roles = []): Collection
    {
        $query = $this->baseQuery($business_id, $branch_id, $supplier_id, $account_id, $allow_roles)
            ->leftJoin('journals', 'journals.journal_id', '=', 'journal_entries.journal_id');

        if ($from) {
            $query->where('journal_entries.entry_date', '>=', $from);
        }

        if ($to) {
            $query->where('journal_entries.entry_date', '<=', $to);
        }

        return $query->orderBy('journal_entries.entry_date')
            ->orderBy('journal_entries.entry_no')
            ->get([
                'journal_entry_details.journal_entry_detail_id',
                'journal_entries.journal_entry_id',
                'journal_entries.entry_no',
                'journal_entries.entry_date',
                'journal_entries.reference_no',
                'journal_entries.description as entry_description',
                'journal_entries.source_type',
                'journal_entries.source_id',
                'journals.short as voucher_short',
                'journals.name as voucher_name',
                'journal_entry_details.debit',
                'journal_entry_details.credit',
                'journal_entry_details.description as detail_description',
            ]);
    }

    /**
     * PHP-side running balance accumulation, not a SQL window function -
     * target MySQL version may be < 8.0, and this is always scoped to one
     * supplier's transactions over a date range, so the row count is bounded.
     */
    public function withRunningBalance(Collection $rows, float $openingBalance): Collection
    {
        $running = $openingBalance;

        return $rows->map(function ($row) use (&$running) {
            $running = $running + (float) $row->credit - (float) $row->debit;
            $balance = $this->toBalance(0, 0, $running);
            $row->running_balance = $balance['balance'];
            $row->running_balance_type = $balance['type'];

            return $row;
        });
    }

    public function lastPaymentDate(?string $business_id, ?string $branch_id, string $supplier_id, string $account_id, array $allow_roles = []): ?string
    {
        return $this->baseQuery($business_id, $branch_id, $supplier_id, $account_id, $allow_roles)
            ->where('journal_entry_details.debit', '>', 0)
            ->max('journal_entries.entry_date');
    }

    /**
     * Standard accounting sign convention for a payable (liability) account:
     * Credit increases the balance owed, Debit reduces it.
     */
    protected function toBalance(float $debit, float $credit, ?float $rawBalance = null): array
    {
        $balance = $rawBalance ?? ($credit - $debit);

        return [
            'debit'   => $debit,
            'credit'  => $credit,
            'balance' => round(abs($balance), 2),
            'type'    => $balance > 0 ? 'Cr' : ($balance < 0 ? 'Dr' : ''),
            'raw'     => $balance,
        ];
    }
}
