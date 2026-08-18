<?php

namespace App\Services\Concrete\Admin;

use App\Enums\Status;
use App\Models\AccountingSetting;
use App\Models\CustomerProfile;
use App\Models\JournalEntryDetail;
use App\Repository\Repository;
use App\Traits\Auditable;
use Illuminate\Support\Facades\Auth;

/**
 * Backend-only service for the business-scoped commercial "customer" profile
 * (credit terms, AR account, address, loyalty) that extends a `users` row
 * with the "User" role. There is no standalone Customer admin screen -
 * customers are created/edited from the Admin Users screen (UserController/
 * UserService), which calls upsertProfile()/getProfile() here. This service
 * is only consumed by other services/controllers (Order, POS, Voucher,
 * Setting, User) for dropdowns, ledger balances, and profile persistence.
 */
class CustomerService
{
    use Auditable;

    protected $model_customer;
    protected $with = [
        'user',
        'business',
        'branch',
        'account'
    ];

    public function __construct()
    {
        $this->model_customer = new Repository(new CustomerProfile());
    }

    public function getProfile($user_id, $business_id)
    {
        return CustomerProfile::with($this->with)
            ->where('user_id', $user_id)
            ->where('business_id', $business_id)
            ->where('is_deleted', 0)
            ->first();
    }

    /**
     * Creates or updates the CustomerProfile for the given user + business.
     * Called by UserService::save() whenever the user being saved holds the
     * customer ("User") role - the User row itself (name/email/phone/
     * password) is already handled by UserService, this only persists the
     * business-scoped commercial fields.
     */
    public function upsertProfile($user_id, $business_id, array $obj)
    {
        $profile = CustomerProfile::where('user_id', $user_id)
            ->where('business_id', $business_id)
            ->first();

        $fields = array_filter([
            'code' => $obj['code'] ?? ($profile->code ?? null),
            'company_name' => $obj['company_name'] ?? null,
            'contact_person' => $obj['contact_person'] ?? null,
            'address' => $obj['address'] ?? null,
            'city' => $obj['city'] ?? null,
            'state' => $obj['state'] ?? null,
            'country' => $obj['country'] ?? null,
            'credit_limit' => $obj['credit_limit'] ?? null,
            'credit_days' => $obj['credit_days'] ?? null,
        ], fn ($value) => !is_null($value));

        if ($profile) {
            $old_values = $profile->only(['credit_limit', 'credit_days']);

            $fields['updatedby_id'] = Auth::id();
            $fields['date_updated'] = now();

            $profile->update($fields);

            if (($old_values['credit_limit'] ?? null) != ($fields['credit_limit'] ?? $old_values['credit_limit'] ?? null)) {
                $this->logActivity('customer', $profile->customer_profile_id, 'updated', $old_values, $profile->only(['credit_limit', 'credit_days']), 'Customer credit terms updated');
            }

            return $profile;
        }

        $fields['customer_profile_id'] = generateUuid();
        $fields['user_id'] = $user_id;
        $fields['business_id'] = $business_id;
        $fields['branch_id'] = $obj['branch_id'] ?? null;
        $fields['code'] = $fields['code'] ?? generateCustomerCode($business_id);
        $fields['status'] = Status::ACTIVE;
        $fields['createdby_id'] = Auth::id();
        $fields['date_created'] = now();

        $profile = CustomerProfile::create($fields);

        $this->logActivity('customer', $profile->customer_profile_id, 'created', null, $profile->only(['credit_limit', 'credit_days']));

        return $profile;
    }

    public function getAllActive($business_id = null)
    {
        $business_id = $business_id ?? Auth::user()->business_id;

        return $this->model_customer->getModel()::with($this->with)
            ->where('business_id', $business_id)
            ->where('status', Status::ACTIVE)
            ->where('is_deleted', 0)
            ->get();
    }

    /**
     * Live customer receivable balance, computed from posted JournalEntryDetail
     * rows (never a stored column) - mirrors SupplierPaymentService::getSupplierLedger().
     * Both account_id (to isolate the receivable account) and user_id (to
     * isolate this customer from every other customer posted to that same
     * account) must be filtered together, or the debit/credit legs of every
     * transaction cancel each other out to zero.
     */
    public function getCustomerLedger($user_id, $business_id = null)
    {
        $business_id = $business_id ?? Auth::user()->business_id;

        $profile = CustomerProfile::where('user_id', $user_id)
            ->where('business_id', $business_id)
            ->first();

        if (!$profile) {
            return [
                'balance'     => 0,
                'type'        => '',
                'raw_balance' => 0,
            ];
        }

        $account_id = $profile->account_id;

        if (empty($account_id)) {
            $accounting_setting = AccountingSetting::where('business_id', $business_id)->first();
            $account_id = $accounting_setting->default_customer_account_id ?? null;
        }

        if (empty($account_id)) {
            return [
                'balance'     => 0,
                'type'        => '',
                'raw_balance' => 0,
            ];
        }

        $totals = JournalEntryDetail::join('journal_entries', 'journal_entries.journal_entry_id', '=', 'journal_entry_details.journal_entry_id')
            ->where('journal_entry_details.user_id', $user_id)
            ->where('journal_entry_details.account_id', $account_id)
            ->where('journal_entries.business_id', $business_id)
            ->where('journal_entries.is_deleted', 0)
            ->where('journal_entries.status', Status::POSTED)
            ->selectRaw('COALESCE(SUM(journal_entry_details.credit),0) as total_credit, COALESCE(SUM(journal_entry_details.debit),0) as total_debit')
            ->first();

        $balance = (float) ($totals->total_credit ?? 0) - (float) ($totals->total_debit ?? 0);

        return [
            'balance'     => round(abs($balance), 3),
            'type'        => $balance > 0 ? 'Cr' : ($balance < 0 ? 'Dr' : ''),
            'raw_balance' => $balance,
        ];
    }
}
