<?php

namespace App\Services\Concrete\Admin\Reports\Accounting;

use App\Enums\Status;
use App\Models\JournalEntryDetail;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Shared low-level query engine for every accounting report that reads
 * posted balances/transactions straight from journal_entry_details. This
 * generalizes SupplierLedgerQueryService (hard-coded to one supplier's
 * credit-normal control account) to any account or set of accounts, in
 * either normal-balance direction, so every report in the module reads the
 * same centralized ledger data through one place.
 */
class AccountingLedgerQueryService
{
    public function __construct(protected AccountClassifier $classifier)
    {
    }

    /**
     * Base scoped query: posted, non-deleted journal_entry_details joined to
     * their journal_entries header. Every other method in this class builds
     * on top of this. Supported filter keys: business_id, branch_id,
     * account_id, account_ids, source_type, allow_roles.
     */
    public function baseQuery(array $filters): Builder
    {
        $query = JournalEntryDetail::query()
            ->join('journal_entries', 'journal_entries.journal_entry_id', '=', 'journal_entry_details.journal_entry_id')
            ->where('journal_entries.is_deleted', 0)
            ->where('journal_entries.status', Status::POSTED);

        if (!empty($filters['business_id'])) {
            $query->where('journal_entries.business_id', $filters['business_id']);
        }

        if (!empty($filters['branch_id'])) {
            $query->where('journal_entries.branch_id', $filters['branch_id']);
        }

        if (!empty($filters['account_id'])) {
            $query->where('journal_entry_details.account_id', $filters['account_id']);
        }

        if (!empty($filters['account_ids'])) {
            $query->whereIn('journal_entry_details.account_id', (array) $filters['account_ids']);
        }

        if (!empty($filters['source_type'])) {
            $query->where('journal_entries.source_type', $filters['source_type']);
        }

        return applyRoleScope($query, $filters['allow_roles'] ?? [], 'journal_entries.business_id', 'journal_entries.branch_id');
    }

    /**
     * Grouped opening balance (sum of debit/credit strictly before
     * $asOfDate) per account, in a single query - avoids N+1 across many
     * accounts. With no $asOfDate, returns an empty map: the report period
     * starts from the beginning of history, so there is definitionally
     * nothing preceding it.
     */
    public function openingBalances(array $filters, ?Carbon $asOfDate): array
    {
        if (!$asOfDate) {
            return [];
        }

        return $this->groupedTotals(
            $this->baseQuery($filters)->where('journal_entries.entry_date', '<', $asOfDate)
        );
    }

    /**
     * Grouped debit/credit totals within [from, to] per account, in a single
     * query.
     */
    public function periodMovements(array $filters, ?Carbon $from, ?Carbon $to): array
    {
        $query = $this->baseQuery($filters);

        if ($from) {
            $query->where('journal_entries.entry_date', '>=', $from);
        }

        if ($to) {
            $query->where('journal_entries.entry_date', '<=', $to);
        }

        return $this->groupedTotals($query);
    }

    /**
     * Unbounded per-account totals up to an optional as-of date (no lower
     * bound) - the "live balance since inception" semantics Balance Sheet
     * needs, as opposed to a bounded period.
     */
    public function totalBalances(array $filters, ?Carbon $asOfDate = null): array
    {
        $query = $this->baseQuery($filters);

        if ($asOfDate) {
            $query->where('journal_entries.entry_date', '<=', $asOfDate);
        }

        return $this->groupedTotals($query);
    }

    protected function groupedTotals(Builder $query): array
    {
        $rows = $query
            ->selectRaw('journal_entry_details.account_id, COALESCE(SUM(journal_entry_details.debit),0) as total_debit, COALESCE(SUM(journal_entry_details.credit),0) as total_credit')
            ->groupBy('journal_entry_details.account_id')
            ->get();

        return $rows->keyBy('account_id')->map(fn ($row) => [
            'debit'  => (float) $row->total_debit,
            'credit' => (float) $row->total_credit,
        ])->all();
    }

    /**
     * Ordered detail rows (one per journal_entry_detail) with the columns
     * every ledger-style report needs, joined up to journal_entries/journals.
     */
    public function transactions(array $filters, ?Carbon $from = null, ?Carbon $to = null): Collection
    {
        $query = $this->baseQuery($filters)
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
                'journal_entry_details.account_id',
                'journal_entries.journal_entry_id',
                'journal_entries.entry_no',
                'journal_entries.entry_date',
                'journal_entries.reference_no',
                'journal_entries.description as entry_description',
                'journal_entries.source_type',
                'journal_entries.source_id',
                'journal_entries.branch_id',
                'journals.short as voucher_short',
                'journals.name as voucher_name',
                'journal_entry_details.debit',
                'journal_entry_details.credit',
                'journal_entry_details.description as detail_description',
            ]);
    }

    /**
     * PHP-side running balance accumulation (not a SQL window function -
     * target MySQL version may be < 8.0), aware of the account's normal
     * balance side so it works for both debit-normal (asset/expense) and
     * credit-normal (liability/equity/revenue) accounts.
     */
    public function withRunningBalance(Collection $rows, float $openingRaw, bool $debitNormal): Collection
    {
        $running = $openingRaw;

        return $rows->map(function ($row) use (&$running, $debitNormal) {
            $running = $debitNormal
                ? $running + (float) $row->debit - (float) $row->credit
                : $running + (float) $row->credit - (float) $row->debit;

            $balance = $this->classifier->toBalance(0, 0, $debitNormal, $running);
            $row->running_balance = $balance['balance'];
            $row->running_balance_type = $balance['type'];

            return $row;
        });
    }
}
