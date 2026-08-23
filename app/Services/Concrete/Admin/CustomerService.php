<?php

namespace App\Services\Concrete\Admin;

use App\Enums\Filter;
use App\Enums\JournalSourceTypes;
use App\Enums\RoleNames;
use App\Enums\Status;
use App\Models\AccountingSetting;
use App\Models\CustomerProfile;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\JournalEntryDetail;
use App\Models\Order;
use App\Repository\Repository;
use App\Traits\Auditable;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;

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
            'shipping_address' => $obj['shipping_address'] ?? null,
            'shipping_city' => $obj['shipping_city'] ?? null,
            'shipping_state' => $obj['shipping_state'] ?? null,
            'shipping_country' => $obj['shipping_country'] ?? null,
            'credit_limit' => $obj['credit_limit'] ?? null,
            'credit_days' => $obj['credit_days'] ?? null,
            'payment_terms' => $obj['payment_terms'] ?? null,
            'notes' => $obj['notes'] ?? null,
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

        // Opening balance only applies the first time a profile is created
        // for this business - never re-applied on update.
        $opening_balance = (float) ($obj['opening_balance'] ?? 0);
        $opening_balance_type = $obj['opening_balance_type'] ?? null;

        $fields['customer_profile_id'] = generateUuid();
        $fields['user_id'] = $user_id;
        $fields['business_id'] = $business_id;
        $fields['branch_id'] = $obj['branch_id'] ?? null;
        $fields['code'] = $fields['code'] ?? generateCustomerCode($business_id);
        $fields['status'] = Status::ACTIVE;
        $fields['opening_balance'] = $opening_balance;
        $fields['opening_balance_type'] = $opening_balance_type;
        $fields['createdby_id'] = Auth::id();
        $fields['date_created'] = now();

        $profile = CustomerProfile::create($fields);

        $this->logActivity('customer', $profile->customer_profile_id, 'created', null, $profile->only(['credit_limit', 'credit_days']));

        if ($opening_balance > 0 && in_array($opening_balance_type, ['Dr', 'Cr'], true)) {
            $this->postOpeningBalance($profile, $opening_balance, $opening_balance_type);
        }

        return $profile;
    }

    /**
     * Auto-post an OBV Journal Voucher for a customer's opening balance, the
     * same pattern SupplierPaymentService::applyPosting() uses for its own
     * postings. Idempotent by source_id (the customer_profile_id) so it can
     * never double-post even if called again.
     */
    protected function postOpeningBalance(CustomerProfile $profile, float $amount, string $type)
    {
        $existing = JournalEntry::where('source_type', JournalSourceTypes::OPENING_BALANCE)
            ->where('source_id', $profile->customer_profile_id)
            ->where('is_deleted', 0)
            ->exists();

        if ($existing) {
            return;
        }

        $accounting_setting = AccountingSetting::where('business_id', $profile->business_id)->first();

        if (!$accounting_setting || !$accounting_setting->enable_accounting) {
            return;
        }

        try {
            app(AccountingPeriodService::class)->assertPostable($profile->business_id, now());
        } catch (Exception $e) {
            return;
        }

        $ar_account_id = $profile->account_id ?? ($accounting_setting->default_customer_account_id ?? null);
        $contra_account_id = $accounting_setting->default_customer_account_id ?? null;

        if (empty($ar_account_id)) {
            return;
        }

        $journal = Journal::where('short', 'OBV')->where('is_deleted', 0)->first();

        if (!$journal) {
            return;
        }

        $journal_entry = JournalEntry::create([
            'journal_entry_id' => generateUuid(),
            'journal_id'       => $journal->journal_id,
            'business_id'      => $profile->business_id,
            'branch_id'        => $profile->branch_id,
            'entry_no'         => generateJVNum($journal->journal_id),
            'reference_no'     => $profile->code,
            'entry_date'       => now(),
            'description'      => 'Opening balance for customer ' . $profile->code,
            'source_type'      => JournalSourceTypes::OPENING_BALANCE,
            'source_id'        => $profile->customer_profile_id,
            'status'           => Status::POSTED,
            'postedby_id'      => Auth::id(),
            'date_posted'      => now(),
            'createdby_id'     => Auth::id(),
            'date_created'     => now(),
        ]);

        // 'Dr' opening balance = customer already owes more (debit AR),
        // 'Cr' opening balance = customer has a credit/advance (credit AR).
        $ar_debit = $type === 'Dr' ? $amount : 0;
        $ar_credit = $type === 'Dr' ? 0 : $amount;

        JournalEntryDetail::create([
            'journal_entry_detail_id' => generateUuid(),
            'journal_entry_id'        => $journal_entry->journal_entry_id,
            'account_id'              => $ar_account_id,
            'debit'                   => $ar_debit,
            'credit'                  => $ar_credit,
            'user_id'                 => $profile->user_id,
            'description'             => 'Opening Balance - ' . $profile->code,
        ]);

        if (!empty($contra_account_id) && $contra_account_id !== $ar_account_id) {
            JournalEntryDetail::create([
                'journal_entry_detail_id' => generateUuid(),
                'journal_entry_id'        => $journal_entry->journal_entry_id,
                'account_id'              => $contra_account_id,
                'debit'                   => $ar_credit,
                'credit'                  => $ar_debit,
                'user_id'                 => $profile->user_id,
                'description'             => 'Opening Balance - ' . $profile->code,
            ]);
        }
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
        if (!empty($obj['start_date'])) {
            $wh[] = ['date_created', '>=', Carbon::parse($obj['start_date'])->startOfDay()];
        }
        if (!empty($obj['end_date'])) {
            $wh[] = ['date_created', '<=', Carbon::parse($obj['end_date'])->endOfDay()];
        }

        $allow_roles = [
            RoleNames::SUPERADMIN,
            RoleNames::BUSINESSADMIN,
        ];

        // Walk-in is a business's auto-created system default customer, not a
        // real record this screen manages.
        $datatable = CustomerProfile::with($this->with)
            ->where($wh)
            ->where('is_deleted', 0)
            ->where('is_walkin', 0)
            ->orderBy('date_created', $orderBy);
        $datatable = applyRoleScope($datatable, $allow_roles);

        return DataTables::of($datatable)
            ->addColumn('name', function ($item) {
                return $item->user->name ?? '';
            })
            ->addColumn('email', function ($item) {
                return $item->user->email ?? '';
            })
            ->addColumn('phone', function ($item) {
                return $item->user->phone ?? '';
            })
            ->addColumn('business', function ($item) {
                return $item->business->name ?? '';
            })
            ->addColumn('credit_limit', function ($item) {
                return currency($item->credit_limit ?? 0);
            })
            ->addColumn('status', function ($item) {

                $checked = $item->status == Status::ACTIVE ? 'checked' : '';

                return '
                <div class="form-check form-switch mb-0">
                    <input
                        class="form-check-input statusCustomer"
                        type="checkbox"
                        data-id="' . $item->user_id . '"
                        ' . $checked . '>
                </div>
            ';
            })
            ->addColumn('action', function ($item) {

                return "
                    <a class='btn btn-icon btn-outline-info mr-2'
                     href='" . route('customer.show', $item->user_id) . "'
                    id='viewCustomer'>
                    <i class='fa fa-eye'></i>
                    </a>

                    <a class='btn btn-icon btn-outline-primary mr-2'
                     href='" . route('customer.edit', $item->user_id) . "'
                    id='editCustomer'>
                    <i class='fa fa-pencil'></i>
                    </a>

                    <a class='btn btn-icon btn-outline-danger'
                    id='deleteCustomer'
                    data-id='{$item->user_id}'>
                    <i class='fa fa-trash'></i>
                    </a>
                ";
            })
            ->rawColumns(['status', 'action'])
            ->make(true);
    }

    public function status($user_id, $business_id = null)
    {
        $business_id = $business_id ?: Auth::user()->business_id;

        $profile = CustomerProfile::where('user_id', $user_id)->where('business_id', $business_id)->firstOrFail();

        $profile->update([
            'status' => $profile->status == Status::ACTIVE ? Status::INACTIVE : Status::ACTIVE,
            'updatedby_id' => Auth::id(),
            'date_updated' => now(),
        ]);

        $this->logActivity('customer', $profile->customer_profile_id, 'status_changed');

        return $profile;
    }

    public function delete($user_id, $business_id = null)
    {
        $business_id = $business_id ?: Auth::user()->business_id;

        $profile = CustomerProfile::where('user_id', $user_id)->where('business_id', $business_id)->firstOrFail();

        $profile->update([
            'is_deleted' => 1,
            'deletedby_id' => Auth::id(),
            'date_deleted' => now(),
        ]);

        $this->logActivity('customer', $profile->customer_profile_id, 'deleted');

        return true;
    }

    /**
     * All orders for this customer within the business, each annotated with
     * its own due amount (Order has no stored due_amount/payment_status
     * column - both are always derived live as total - paid_amount, the
     * same convention OrderService::getData() already uses).
     */
    public function getCustomerHistory($user_id, $business_id = null)
    {
        $business_id = $business_id ?? Auth::user()->business_id;

        $orders = Order::where('user_id', $user_id)
            ->where('business_id', $business_id)
            ->where('is_deleted', 0)
            ->orderByDesc('order_date')
            ->get([
                'order_id', 'order_date', 'sale_date', 'total', 'paid_amount', 'status',
            ])
            ->map(function ($order) {
                $order->due_amount = round((float) $order->total - (float) $order->paid_amount, 3);
                $order->is_credit = $order->due_amount > 0;
                return $order;
            });

        $payments = \App\Models\CustomerPayment::where('user_id', $user_id)
            ->where('business_id', $business_id)
            ->where('is_deleted', 0)
            ->orderByDesc('payment_date')
            ->get();

        return [
            'orders' => $orders,
            'payments' => $payments,
        ];
    }

    /**
     * Chronological merge of orders + customer payments for the Timeline tab.
     */
    public function getCustomerTimeline($user_id, $business_id = null)
    {
        $business_id = $business_id ?? Auth::user()->business_id;

        $history = $this->getCustomerHistory($user_id, $business_id);

        $events = collect();

        foreach ($history['orders'] as $order) {
            $events->push([
                'type' => 'order',
                'date' => $order->order_date,
                'reference' => $order->order_id,
                'amount' => $order->total,
                'description' => 'Order' . ($order->is_credit ? ' (Credit)' : ''),
                'status' => $order->status,
            ]);
        }

        foreach ($history['payments'] as $payment) {
            $events->push([
                'type' => 'payment',
                'date' => $payment->payment_date,
                'reference' => $payment->payment_no,
                'amount' => $payment->amount,
                'description' => 'Payment Received' . ($payment->order_id ? ' (against order)' : ' (on account)'),
                'status' => $payment->status,
            ]);
        }

        return $events->sortByDesc('date')->values();
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
                'balance'             => 0,
                'type'                => '',
                'raw_balance'         => 0,
                'store_credit_balance' => 0,
            ];
        }

        $account_id = $profile->account_id;

        if (empty($account_id)) {
            $accounting_setting = AccountingSetting::where('business_id', $business_id)->first();
            $account_id = $accounting_setting->default_customer_account_id ?? null;
        }

        if (empty($account_id)) {
            return [
                'balance'             => 0,
                'type'                => '',
                'raw_balance'         => 0,
                'store_credit_balance' => (float) $profile->store_credit_balance,
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
            'balance'             => round(abs($balance), 3),
            'type'                => $balance > 0 ? 'Cr' : ($balance < 0 ? 'Dr' : ''),
            'raw_balance'         => $balance,
            // Distinct from the AR balance above by design - store credit is
            // a separately-tracked, dedicated liability (see
            // CustomerStoreCreditService), never netted against what this
            // customer owes on account.
            'store_credit_balance' => (float) $profile->store_credit_balance,
        ];
    }
}
