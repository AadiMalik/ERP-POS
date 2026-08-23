<?php

namespace App\Services\Concrete\Admin;

use App\Enums\Filter;
use App\Enums\JournalSourceTypes;
use App\Enums\PaymentMethod;
use App\Enums\RoleNames;
use App\Enums\Status;
use App\Models\AccountingSetting;
use App\Models\CustomerPayment;
use App\Models\CustomerProfile;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\JournalEntryDetail;
use App\Models\Order;
use App\Models\ServiceSale;
use App\Repository\Repository;
use App\Traits\Auditable;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

class CustomerPaymentService
{
    use Auditable;

    protected $model_customer_payment;
    protected $with = [
        'business',
        'branch',
        'user',
        'order',
        'serviceSale',
        'paymentAccount',
    ];

    public function __construct()
    {
        $this->model_customer_payment = new Repository(new CustomerPayment());
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
        if (isset($obj['user_id']) && $obj['user_id'] != 0 && $obj['user_id'] != "") {
            $wh[] = ['user_id', $obj['user_id']];
        }
        if (isset($obj['payment_method']) && $obj['payment_method'] != 0 && $obj['payment_method'] != "") {
            $wh[] = ['payment_method', $obj['payment_method']];
        }
        if (isset($obj['status']) && $obj['status'] != 0 && $obj['status'] != "") {
            $wh[] = ['status', $obj['status']];
        }
        if (!empty($obj['start_date'])) {
            $wh[] = ['payment_date', '>=', Carbon::parse($obj['start_date'])->startOfDay()];
        }
        if (!empty($obj['end_date'])) {
            $wh[] = ['payment_date', '<=', Carbon::parse($obj['end_date'])->endOfDay()];
        }

        $allow_roles = [
            RoleNames::SUPERADMIN,
            RoleNames::BUSINESSADMIN,
        ];

        $datatable = $this->model_customer_payment->getModel()::with($this->with)
            ->where($wh)
            ->where('is_deleted', 0)
            ->orderBy('payment_date', $orderBy);
        $datatable = applyRoleScope($datatable, $allow_roles);

        return DataTables::of($datatable)
            ->addColumn('payment_date', function ($item) {
                return !empty($item->payment_date) ? localDate($item->payment_date) : 'N/A';
            })
            ->addColumn('customer', function ($item) {
                return $item->user->name ?? '';
            })
            ->addColumn('order_no', function ($item) {
                if ($item->order_id) {
                    return $item->order->daily_order_id ?? $item->order_id;
                }
                if ($item->service_sale_id) {
                    return $item->serviceSale->service_sale_no ?? $item->service_sale_id;
                }
                return 'On Account';
            })
            ->addColumn('business', function ($item) {
                return $item->business->name ?? '';
            })
            ->addColumn('branch', function ($item) {
                return $item->branch->name ?? '';
            })
            ->addColumn('payment_method', function ($item) {
                return ucwords(str_replace('_', ' ', $item->payment_method ?? ''));
            })
            ->addColumn('amount', function ($item) {
                return currency($item->amount ?? 0);
            })
            ->addColumn('net_amount', function ($item) {
                return currency($item->net_amount ?? 0);
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
                data-id='{$item->customer_payment_id}'>";

                foreach ($statuses as $value => $label) {
                    $selected = $item->status == $value ? 'selected' : '';
                    $html .= "<option value='{$value}' {$selected}>{$label}</option>";
                }

                $html .= "</select>";

                return $html;
            })
            ->addColumn('action', function ($item) {

                $editButton = $item->status === Status::PENDING
                    ? "<a class='btn btn-icon btn-outline-primary mr-2'
                        href='" . route('customer-payment.edit', $item->customer_payment_id) . "'
                        id='editCustomerPayment'>
                        <i class='fa fa-pencil'></i>
                        </a>"
                    : "<button type='button' class='btn btn-icon btn-outline-primary mr-2' disabled
                        title='Unpost before editing'>
                        <i class='fa fa-pencil'></i>
                        </button>";

                $viewJvButton = $item->status === Status::POSTED
                    ? "<button type='button' class='btn btn-icon btn-outline-secondary mr-2 view-jv-btn'
                        data-source-type='" . JournalSourceTypes::CUSTOMER_PAYMENT . "' data-source-id='{$item->customer_payment_id}' title='View JV'>
                        <i class='fa fa-book'></i>
                        </button>"
                    : '';

                $printButton = "<a class='btn btn-icon btn-outline-secondary mr-2' target='_blank'
                    href='" . route('customer-payment.print', $item->customer_payment_id) . "' title='Print'>
                    <i class='fa fa-print'></i>
                    </a>";

                $deleteButton = $item->status !== Status::CANCELLED
                    ? "<a class='btn btn-icon btn-outline-danger'
                    id='deleteCustomerPayment'
                    data-id='{$item->customer_payment_id}'>
                    <i class='fa fa-trash'></i>
                    </a>"
                    : '';

                return $editButton . $viewJvButton . $printButton . $deleteButton;
            })
            ->rawColumns(['business', 'branch', 'customer', 'order_no', 'payment_method', 'amount', 'net_amount', 'status', 'action'])
            ->make(true);
    }

    public function save($obj)
    {
        DB::beginTransaction();

        try {
            $customer_user_id = $obj['user_id'] ?? null;

            if (empty($customer_user_id)) {
                throw new Exception('Selected customer not found.');
            }

            $profile = CustomerProfile::where('user_id', $customer_user_id)
                ->where('business_id', $obj['business_id'])
                ->where('is_deleted', 0)
                ->first();

            if (!$profile) {
                throw new Exception('Selected customer does not have a profile for this business.');
            }

            $order = null;

            if (!empty($obj['order_id'])) {
                $order = Order::where('order_id', $obj['order_id'])
                    ->where('user_id', $customer_user_id)
                    ->where('is_deleted', 0)
                    ->first();

                if (!$order) {
                    throw new Exception('The selected order does not belong to the selected customer.');
                }
            }

            $service_sale = null;

            if (!empty($obj['service_sale_id'])) {
                $service_sale = ServiceSale::where('service_sale_id', $obj['service_sale_id'])
                    ->where('customer_id', $customer_user_id)
                    ->where('is_deleted', 0)
                    ->first();

                if (!$service_sale) {
                    throw new Exception('The selected service sale does not belong to the selected customer.');
                }
            }

            $accounting_setting = AccountingSetting::where('business_id', $obj['business_id'])->first();

            if (!empty($accounting_setting) && $accounting_setting->manual_payment_account_selection) {
                $payment_account_id = $obj['payment_account_id'] ?? null;
            } else {
                $payment_account_id = $obj['payment_method'] === PaymentMethod::CASH
                    ? ($accounting_setting->default_cash_account_id ?? null)
                    : ($accounting_setting->default_bank_account_id ?? null);
            }

            $amount = (float) ($obj['amount'] ?? 0);
            $tax_amount = (float) ($obj['tax_amount'] ?? 0);
            $discount_amount = (float) ($obj['discount_amount'] ?? 0);
            $net_amount = $amount - $tax_amount - $discount_amount;

            if ($net_amount < 0) {
                throw new Exception('Tax and discount amount cannot exceed the payment amount.');
            }

            // Order-targeted payments may never exceed that order's
            // remaining due - excess is rejected outright (no partial
            // on-account carry-over for a single submission).
            if ($order) {
                $remaining_due = round((float) $order->total - (float) $order->paid_amount, 3);

                // On update, exclude this payment's own previously-posted
                // amount from "already applied" so re-saving the same
                // payment isn't rejected against its own prior contribution.
                if (!empty($obj['customer_payment_id'])) {
                    $existing = $this->model_customer_payment->getModel()::find($obj['customer_payment_id']);
                    if ($existing && $existing->status === Status::POSTED && $existing->order_id === $order->order_id) {
                        $remaining_due += (float) $existing->amount;
                    }
                }

                if ($amount > $remaining_due + 0.001) {
                    throw new Exception('Payment amount (' . currency($amount) . ') exceeds the order\'s remaining due (' . currency(max($remaining_due, 0)) . ').');
                }
            }

            // Service-Sale-targeted payments use the same derived-due guard
            // as SupplierPaymentService's purchase_id branch - a Service Sale
            // has no stored paid_amount column, unlike Order.
            if ($service_sale) {
                $paid_so_far = (float) CustomerPayment::where('service_sale_id', $service_sale->service_sale_id)
                    ->where('status', Status::POSTED)
                    ->where('is_deleted', 0)
                    ->sum('amount');

                if (!empty($obj['customer_payment_id'])) {
                    $existing = $this->model_customer_payment->getModel()::find($obj['customer_payment_id']);
                    if ($existing && $existing->status === Status::POSTED && $existing->service_sale_id === $service_sale->service_sale_id) {
                        $paid_so_far -= (float) $existing->amount;
                    }
                }

                $remaining_due = round((float) $service_sale->total - $paid_so_far, 3);

                if ($amount > $remaining_due + 0.001) {
                    throw new Exception('Payment amount (' . currency($amount) . ') exceeds the service sale\'s remaining due (' . currency(max($remaining_due, 0)) . ').');
                }
            }

            $data = [
                'business_id'          => $obj['business_id'],
                'branch_id'            => $obj['branch_id'] ?? null,
                'user_id'              => $customer_user_id,
                'order_id'             => $order->order_id ?? null,
                'service_sale_id'      => $service_sale->service_sale_id ?? null,
                'payment_date'         => $obj['payment_date'],
                'payment_method'       => $obj['payment_method'],
                'payment_account_id'   => $payment_account_id,
                'customer_account_id'  => $profile->account_id,
                'reference_no'         => $obj['reference_no'] ?? null,
                'cheque_date'          => $obj['cheque_date'] ?? null,
                'amount'               => $amount,
                'tax_amount'           => $tax_amount,
                'discount_amount'      => $discount_amount,
                'net_amount'           => $net_amount,
                'remarks'              => $obj['remarks'] ?? null,
            ];

            if (!empty($obj['attachment'])) {
                $data['attachment'] = $obj['attachment'];
            }

            //====================================
            // Update
            //====================================

            if (!empty($obj['customer_payment_id'])) {

                $payment = $this->model_customer_payment->getModel()::findOrFail($obj['customer_payment_id']);

                if ($payment->status !== Status::PENDING) {
                    throw new Exception('Only pending customer payments can be updated.');
                }

                $data['updatedby_id'] = Auth::id();
                $data['date_updated'] = now();

                $payment->update($data);

                $action = 'updated';
            }

            //====================================
            // Create
            //====================================

            else {

                $data['customer_payment_id'] = generateUuid();
                $data['payment_no'] = $obj['payment_no'] ?? generateCustomerPaymentNo($obj['business_id']);
                $data['status'] = Status::PENDING;
                $data['createdby_id'] = Auth::id();
                $data['date_created'] = now();

                $payment = $this->model_customer_payment->create($data);

                $action = 'created';
            }

            DB::commit();

            $this->logActivity('customer_payment', $payment->customer_payment_id, $action, null, ['amount' => $payment->amount, 'net_amount' => $payment->net_amount]);

            return $payment;
        } catch (Exception $e) {

            DB::rollBack();

            throw $e;
        }
    }

    public function getById($customer_payment_id)
    {
        return $this->model_customer_payment->getModel()::with($this->with)->find($customer_payment_id);
    }

    public function getDetails($customer_payment_id)
    {
        $payment = $this->model_customer_payment->getModel()::with($this->with)->findOrFail($customer_payment_id);

        return [
            'customer_payment_id' => $payment->customer_payment_id,
            'business_id'         => $payment->business_id,
            'branch_id'           => $payment->branch_id,
            'user_id'             => $payment->user_id,
            'order_id'            => $payment->order_id,
            'service_sale_id'     => $payment->service_sale_id,
            'payment_no'          => $payment->payment_no,
            'payment_date'        => $payment->payment_date,
            'payment_method'      => $payment->payment_method,
            'payment_account_id'  => $payment->payment_account_id,
            'customer_account_id' => $payment->customer_account_id,
            'reference_no'        => $payment->reference_no,
            'cheque_date'         => $payment->cheque_date,
            'amount'              => $payment->amount,
            'tax_amount'          => $payment->tax_amount,
            'discount_amount'     => $payment->discount_amount,
            'net_amount'          => $payment->net_amount,
            'remarks'             => $payment->remarks,
            'attachment'          => $payment->attachment,
            'status'              => $payment->status,
        ];
    }

    public function status($obj)
    {
        DB::beginTransaction();

        try {
            $payment = $this->model_customer_payment->getModel()::with($this->with)->findOrFail($obj['customer_payment_id']);
            $old_status = $payment->status;
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

            $payment->update($update);

            if ($new_status === Status::POSTED && $old_status !== Status::POSTED) {
                $this->applyPosting($payment);
            } elseif ($old_status === Status::POSTED && $new_status !== Status::POSTED) {
                $this->reversePosting($payment);
            }

            DB::commit();

            $this->logActivity(
                'customer_payment',
                $payment->customer_payment_id,
                $new_status === Status::POSTED ? 'posted' : 'unposted',
                ['status' => $old_status],
                ['status' => $new_status]
            );
        } catch (Exception $e) {
            DB::rollBack();

            throw $e;
        }

        return $payment;
    }

    public function delete($customer_payment_id)
    {
        DB::beginTransaction();

        try {
            $payment = $this->model_customer_payment->getModel()::with($this->with)->findOrFail($customer_payment_id);

            if ($payment->status === Status::POSTED) {
                $this->reversePosting($payment);
            }

            $payment->update([
                'is_deleted'   => 1,
                'status'       => Status::CANCELLED,
                'deletedby_id' => Auth::id(),
                'date_deleted' => now(),
            ]);

            DB::commit();

            $this->logActivity('customer_payment', $payment->customer_payment_id, 'deleted');

            return true;
        } catch (Exception $e) {
            DB::rollBack();

            throw $e;
        }
    }

    /**
     * Auto-post a CRV/BRV Journal Voucher when a Customer Payment is posted,
     * mirroring SupplierPaymentService::applyPosting(). If the payment
     * targets a specific order, that order's paid_amount is incremented in
     * the same transaction (capped at the order total) so every existing
     * "due = total - paid_amount" call site (OrderService::getData(), the
     * order show view, validateCreditLimit(), etc.) stays correct without
     * any changes there.
     */
    protected function applyPosting(CustomerPayment $payment)
    {
        $existing = JournalEntry::where('source_type', JournalSourceTypes::CUSTOMER_PAYMENT)
            ->where('source_id', $payment->customer_payment_id)
            ->where('is_deleted', 0)
            ->exists();

        if ($existing) {
            return;
        }

        $accounting_setting = AccountingSetting::where('business_id', $payment->business_id)->first();

        if (!$accounting_setting || !$accounting_setting->enable_accounting) {
            throw new Exception('Accounting is not enabled for this business. Please configure Accounting Settings before posting customer payments.');
        }

        app(AccountingPeriodService::class)->assertPostable($payment->business_id, now());

        if (empty($payment->customer_account_id)) {
            throw new Exception('The selected customer does not have a linked Chart of Account. Please configure it before posting this payment.');
        }

        if (empty($payment->payment_account_id)) {
            throw new Exception('No Cash/Bank payment account is configured for this payment. Please configure default Cash/Bank accounts in Accounting Settings.');
        }

        if ($payment->discount_amount > 0 && empty($accounting_setting->default_discount_account_id)) {
            throw new Exception('Discount Account is not configured in Accounting Settings.');
        }

        if ($payment->tax_amount > 0 && empty($accounting_setting->default_withholding_tax_account_id)) {
            throw new Exception('Withholding Tax Account is not configured in Accounting Settings.');
        }

        $short = $payment->payment_method === PaymentMethod::CASH ? 'CRV' : 'BRV';

        $journal = Journal::where('short', $short)->where('is_deleted', 0)->first();

        if (!$journal) {
            throw new Exception('No "' . $short . '" journal category found. Please configure it before posting customer payments.');
        }

        $entry_no = generateJVNum($journal->journal_id);

        $journal_entry = JournalEntry::create([
            'journal_entry_id' => generateUuid(),
            'journal_id'       => $journal->journal_id,
            'business_id'      => $payment->business_id,
            'branch_id'        => $payment->branch_id,
            'entry_no'         => $entry_no,
            'reference_no'     => $payment->payment_no,
            'entry_date'       => now(),
            'description'      => 'Auto-generated ' . $short . ' for customer payment ' . $payment->payment_no,
            'source_type'      => JournalSourceTypes::CUSTOMER_PAYMENT,
            'source_id'        => $payment->customer_payment_id,
            'status'           => Status::POSTED,
            'postedby_id'      => Auth::id(),
            'date_posted'      => now(),
            'createdby_id'     => Auth::id(),
            'date_created'     => now(),
        ]);

        JournalEntryDetail::create([
            'journal_entry_detail_id' => generateUuid(),
            'journal_entry_id'        => $journal_entry->journal_entry_id,
            'account_id'              => $payment->payment_account_id,
            'debit'                   => $payment->net_amount,
            'credit'                  => 0,
            'user_id'                 => $payment->user_id,
            'description'             => 'Customer Payment - ' . $payment->payment_no,
        ]);

        JournalEntryDetail::create([
            'journal_entry_detail_id' => generateUuid(),
            'journal_entry_id'        => $journal_entry->journal_entry_id,
            'account_id'              => $payment->customer_account_id,
            'debit'                   => 0,
            'credit'                  => $payment->amount,
            'user_id'                 => $payment->user_id,
            'description'             => 'Customer Payment - ' . $payment->payment_no,
        ]);

        if ($payment->discount_amount > 0) {
            JournalEntryDetail::create([
                'journal_entry_detail_id' => generateUuid(),
                'journal_entry_id'        => $journal_entry->journal_entry_id,
                'account_id'              => $accounting_setting->default_discount_account_id,
                'debit'                   => $payment->discount_amount,
                'credit'                  => 0,
                'user_id'                 => $payment->user_id,
                'description'             => 'Discount Given - ' . $payment->payment_no,
            ]);
        }

        if ($payment->tax_amount > 0) {
            JournalEntryDetail::create([
                'journal_entry_detail_id' => generateUuid(),
                'journal_entry_id'        => $journal_entry->journal_entry_id,
                'account_id'              => $accounting_setting->default_withholding_tax_account_id,
                'debit'                   => $payment->tax_amount,
                'credit'                  => 0,
                'user_id'                 => $payment->user_id,
                'description'             => 'Withholding Tax - ' . $payment->payment_no,
            ]);
        }

        \App\Services\Concrete\Admin\JournalEntryService::assertBalanced($journal_entry->journal_entry_id);

        if (!empty($payment->order_id)) {
            $order = Order::where('order_id', $payment->order_id)->first();

            if ($order) {
                $applied = min((float) $payment->amount, max((float) $order->total - (float) $order->paid_amount, 0));
                $order->update(['paid_amount' => (float) $order->paid_amount + $applied]);
            }
        }
    }

    /**
     * Reverse the CRV/BRV Journal Voucher created when a Customer Payment
     * was posted, and the order.paid_amount increment applied alongside it.
     */
    protected function reversePosting(CustomerPayment $payment)
    {
        $journal_entry = JournalEntry::where('source_type', JournalSourceTypes::CUSTOMER_PAYMENT)
            ->where('source_id', $payment->customer_payment_id)
            ->where('is_deleted', 0)
            ->first();

        if ($journal_entry) {
            app(AccountingPeriodService::class)->assertPostable($journal_entry->business_id, $journal_entry->entry_date);

            $journal_entry->update([
                'is_deleted'   => 1,
                'deletedby_id' => Auth::id(),
                'date_deleted' => now(),
            ]);
        }

        if (!empty($payment->order_id)) {
            $order = Order::where('order_id', $payment->order_id)->first();

            if ($order) {
                $applied = min((float) $payment->amount, (float) $order->paid_amount);
                $order->update(['paid_amount' => max((float) $order->paid_amount - $applied, 0)]);
            }
        }
    }

    public function getByBusiness($business_id)
    {
        return $this->model_customer_payment->getModel()::with($this->with)
            ->where('business_id', $business_id)
            ->where('is_deleted', 0)
            ->get();
    }
}
