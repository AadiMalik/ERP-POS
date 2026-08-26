<?php

namespace App\Services\Concrete\Admin\Reports;

use App\Enums\Status;
use App\Models\AccountingSetting;
use App\Models\CustomerProfile;
use App\Models\JournalEntryDetail;
use App\Services\Concrete\Admin\CustomerService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Shared query layer for every report that reads a customer's receivable
 * balance from posted accounting transactions. Mirrors
 * SupplierLedgerQueryService, adapted for the customer side's account
 * resolution: a customer's own CustomerProfile.account_id may be null (a
 * supplier always has one - see Supplier::save()), falling back to
 * accounting_settings.default_customer_account_id, exactly as
 * CustomerService::getCustomerLedger() already resolves it. The customer
 * leg of a journal entry is tagged via journal_entry_details.user_id (there
 * is no customer_id column), so every query here filters .user_id AND
 * .account_id together - dropping either condition nets every customer's
 * debits/credits together.
 */
class CustomerLedgerQueryService
{
    protected $customer_service;

    public function __construct(CustomerService $customer_service)
    {
        $this->customer_service = $customer_service;
    }

    public function resolveCustomerProfile(string $user_id, ?string $business_id = null): ?CustomerProfile
    {
        $query = CustomerProfile::with(['account', 'user']);

        $query->where('user_id', $user_id);

        if (!empty($business_id)) {
            $query->where('business_id', $business_id);
        }

        return $query->first();
    }

    public function resolveAccountId(?CustomerProfile $profile, ?string $business_id): ?string
    {
        if (!$profile || empty($business_id)) {
            return null;
        }

        return $this->customer_service->tryResolveCustomerReceivableAccountId(
            $profile,
            AccountingSetting::where('business_id', $business_id)->first()
        );
    }

    public function baseQuery(?string $business_id, ?string $branch_id, string $user_id, string $account_id, array $allow_roles = []): Builder
    {
        $query = JournalEntryDetail::query()
            ->join('journal_entries', 'journal_entries.journal_entry_id', '=', 'journal_entry_details.journal_entry_id')
            ->where('journal_entry_details.user_id', $user_id)
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
    public function openingBalance(?string $business_id, ?string $branch_id, string $user_id, string $account_id, ?Carbon $asOfDate = null, array $allow_roles = []): array
    {
        if (!$asOfDate) {
            return $this->toBalance(0, 0);
        }

        $query = $this->baseQuery($business_id, $branch_id, $user_id, $account_id, $allow_roles)
            ->where('journal_entries.entry_date', '<', $asOfDate);

        $totals = $query->selectRaw('COALESCE(SUM(journal_entry_details.debit),0) as total_debit, COALESCE(SUM(journal_entry_details.credit),0) as total_credit')->first();

        return $this->toBalance((float) ($totals->total_debit ?? 0), (float) ($totals->total_credit ?? 0));
    }

    /**
     * Unbounded balance of every posted transaction to date (no period
     * boundary) - the same "live receivable balance" semantics as the
     * existing CustomerService::getCustomerLedger(). Do not invert the sign
     * convention - it must stay consistent with that method (credit - debit),
     * even though "Cr"/"Dr" read differently for a receivable than for a
     * supplier's payable; the formula is the codebase's existing convention.
     */
    public function totalBalance(?string $business_id, ?string $branch_id, string $user_id, string $account_id, array $allow_roles = []): array
    {
        $query = $this->baseQuery($business_id, $branch_id, $user_id, $account_id, $allow_roles);

        $totals = $query->selectRaw('COALESCE(SUM(journal_entry_details.debit),0) as total_debit, COALESCE(SUM(journal_entry_details.credit),0) as total_credit')->first();

        return $this->toBalance((float) ($totals->total_debit ?? 0), (float) ($totals->total_credit ?? 0));
    }

    public function customerTransactions(?string $business_id, ?string $branch_id, string $user_id, string $account_id, ?Carbon $from = null, ?Carbon $to = null, array $allow_roles = []): Collection
    {
        $query = $this->baseQuery($business_id, $branch_id, $user_id, $account_id, $allow_roles)
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
     * customer's transactions over a date range, so the row count is bounded.
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

    public function lastPaymentDate(?string $business_id, ?string $branch_id, string $user_id, string $account_id, array $allow_roles = []): ?string
    {
        return $this->baseQuery($business_id, $branch_id, $user_id, $account_id, $allow_roles)
            ->where('journal_entry_details.debit', '>', 0)
            ->max('journal_entries.entry_date');
    }

    /**
     * Standard accounting sign convention for a receivable (asset) account,
     * kept identical to SupplierLedgerQueryService's credit-minus-debit
     * formula (see the note on totalBalance() above) - Credit reduces the
     * balance owed to the business, Debit increases it.
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
