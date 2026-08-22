<?php

namespace App\Services\Concrete\Admin;

use App\Enums\Filter;
use App\Enums\JournalSourceTypes;
use App\Enums\PaymentMethod;
use App\Enums\RoleNames;
use App\Enums\Status;
use App\Models\AccountingSetting;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\JournalEntryDetail;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Repository\Repository;
use App\Traits\Auditable;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

class SupplierPaymentService
{
    use Auditable;

    protected $model_supplier_payment;
    protected $with = [
        'business',
        'branch',
        'supplier',
        'supplier.account',
        'purchase',
        'paymentAccount',
    ];

    public function __construct()
    {
        $this->model_supplier_payment = new Repository(new SupplierPayment());
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
        if (isset($obj['supplier_id']) && $obj['supplier_id'] != 0 && $obj['supplier_id'] != "") {
            $wh[] = ['supplier_id', $obj['supplier_id']];
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
            RoleNames::BUSINESSADMIN
        ];

        $datatable = $this->model_supplier_payment->getModel()::with($this->with)
            ->where($wh)
            ->where('is_deleted', 0)
            ->orderBy('payment_date', $orderBy);
        $datatable = applyRoleScope($datatable, $allow_roles);

        return DataTables::of($datatable)
            ->addColumn('payment_date', function ($item) {
                return !empty($item->payment_date)
                    ? localDate($item->payment_date)
                    : 'N/A';
            })
            ->addColumn('supplier', function ($item) {
                return ($item->supplier->code ?? '') . ' ' . ($item->supplier->name ?? '');
            })
            ->addColumn('purchase_no', function ($item) {
                return $item->purchase->purchase_no ?? '';
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
                data-id='{$item->supplier_payment_id}'>";

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
                        href='" . route('supplier-payment.edit', $item->supplier_payment_id) . "'
                        id='editSupplierPayment'>
                        <i class='fa fa-pencil'></i>
                        </a>"
                    : "<button type='button' class='btn btn-icon btn-outline-primary mr-2' disabled
                        title='Unpost before editing'>
                        <i class='fa fa-pencil'></i>
                        </button>";

                $viewJvButton = $item->status === Status::POSTED
                    ? "<button type='button' class='btn btn-icon btn-outline-dark mr-2 view-jv-btn'
                        data-source-type='" . JournalSourceTypes::SUPPLIER_PAYMENT . "' data-source-id='{$item->supplier_payment_id}' title='View JV'>
                        <i class='fa fa-book'></i>
                        </button>"
                    : '';

                $printButton = "<a class='btn btn-icon btn-outline-secondary mr-2' target='_blank'
                    href='" . route('supplier-payment.print', $item->supplier_payment_id) . "' title='Print'>
                    <i class='fa fa-print'></i>
                    </a>";

                $deleteButton = $item->status !== Status::CANCELLED
                    ? "<a class='btn btn-icon btn-outline-danger'
                    id='deleteSupplierPayment'
                    data-id='{$item->supplier_payment_id}'>
                    <i class='fa fa-trash'></i>
                    </a>"
                    : '';

                return $editButton . $viewJvButton . $printButton . $deleteButton;
            })
            ->rawColumns(['business', 'branch', 'supplier', 'purchase_no', 'payment_method', 'amount', 'net_amount', 'status', 'action'])
            ->make(true);
    }

    public function save($obj)
    {
        DB::beginTransaction();

        try {
            $supplier = Supplier::where('supplier_id', $obj['supplier_id'])
                ->where('is_deleted', 0)
                ->first();

            if (!$supplier) {
                throw new Exception('Selected supplier not found.');
            }

            if (!empty($obj['purchase_id'])) {
                $purchase = Purchase::where('purchase_id', $obj['purchase_id'])
                    ->where('supplier_id', $obj['supplier_id'])
                    ->where('is_deleted', 0)
                    ->first();

                if (!$purchase) {
                    throw new Exception('The selected purchase does not belong to the selected supplier.');
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

            // Purchase-targeted payments may never exceed that purchase's
            // remaining due - mirrors CustomerPaymentService::save()'s
            // overpayment guard against Order.paid_amount. Purchase has no
            // stored paid_amount column, so the remaining due is derived
            // from the sum of this purchase's own posted SupplierPayments.
            if (!empty($purchase)) {
                $paid_so_far = (float) SupplierPayment::where('purchase_id', $purchase->purchase_id)
                    ->where('status', Status::POSTED)
                    ->where('is_deleted', 0)
                    ->sum('amount');

                // On update, exclude this payment's own previously-posted
                // amount from "already applied" so re-saving the same
                // payment isn't rejected against its own prior contribution.
                if (!empty($obj['supplier_payment_id'])) {
                    $existing = $this->model_supplier_payment->getModel()::find($obj['supplier_payment_id']);
                    if ($existing && $existing->status === Status::POSTED && $existing->purchase_id === $purchase->purchase_id) {
                        $paid_so_far -= (float) $existing->amount;
                    }
                }

                $remaining_due = round((float) $purchase->total - $paid_so_far, 3);

                if ($amount > $remaining_due + 0.001) {
                    throw new Exception('Payment amount (' . currency($amount) . ') exceeds the purchase\'s remaining due (' . currency(max($remaining_due, 0)) . ').');
                }
            }

            $data = [
                'business_id'         => $obj['business_id'],
                'branch_id'           => $obj['branch_id'] ?? null,
                'supplier_id'         => $obj['supplier_id'],
                'purchase_id'         => $obj['purchase_id'] ?? null,
                'payment_date'        => $obj['payment_date'],
                'payment_method'      => $obj['payment_method'],
                'payment_account_id'  => $payment_account_id,
                'supplier_account_id' => $supplier->account_id,
                'reference_no'        => $obj['reference_no'] ?? null,
                'cheque_date'         => $obj['cheque_date'] ?? null,
                'amount'              => $amount,
                'tax_amount'          => $tax_amount,
                'discount_amount'     => $discount_amount,
                'net_amount'          => $net_amount,
                'remarks'             => $obj['remarks'] ?? null,
            ];

            if (!empty($obj['attachment'])) {
                $data['attachment'] = $obj['attachment'];
            }

            //====================================
            // Update
            //====================================

            if (!empty($obj['supplier_payment_id'])) {

                $payment = $this->model_supplier_payment->getModel()::findOrFail($obj['supplier_payment_id']);

                if ($payment->status !== Status::PENDING) {
                    throw new Exception('Only pending supplier payments can be updated.');
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

                $data['supplier_payment_id'] = generateUuid();
                $data['payment_no'] = $obj['payment_no'] ?? generateSupplierPaymentNo($obj['business_id']);
                $data['status'] = Status::PENDING;
                $data['createdby_id'] = Auth::id();
                $data['date_created'] = now();

                $payment = $this->model_supplier_payment->create($data);

                $action = 'created';
            }

            DB::commit();

            $this->logActivity('supplier_payment', $payment->supplier_payment_id, $action, null, ['amount' => $payment->amount, 'net_amount' => $payment->net_amount]);

            return $payment;
        } catch (Exception $e) {

            DB::rollBack();

            throw $e;
        }
    }

    public function getById($supplier_payment_id)
    {
        return $this->model_supplier_payment->getModel()::with($this->with)->find($supplier_payment_id);
    }

    public function getDetails($supplier_payment_id)
    {
        $payment = $this->model_supplier_payment->getModel()::with($this->with)->findOrFail($supplier_payment_id);

        return [
            'supplier_payment_id' => $payment->supplier_payment_id,
            'business_id'         => $payment->business_id,
            'branch_id'           => $payment->branch_id,
            'supplier_id'         => $payment->supplier_id,
            'purchase_id'         => $payment->purchase_id,
            'payment_no'          => $payment->payment_no,
            'payment_date'        => $payment->payment_date,
            'payment_method'      => $payment->payment_method,
            'payment_account_id'  => $payment->payment_account_id,
            'supplier_account_id' => $payment->supplier_account_id,
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

    /**
     * Live supplier payable balance, computed from posted JournalEntryDetail
     * rows (never a stored column). Every supplier currently shares the same
     * COA account_id (see Supplier model note), and every posting here also
     * tags supplier_id on its offsetting cash/discount/tax lines for
     * reporting traceability - so both account_id (to isolate the shared
     * payable account from unrelated lines) and supplier_id (to isolate this
     * supplier from every other supplier posted to that same account) must
     * be filtered together, or the debit/credit legs of every transaction
     * cancel each other out to zero.
     */
    public function getSupplierLedger($supplier_id, $business_id = null)
    {
        $business_id = $business_id ?? Auth::user()->business_id;

        $supplier = Supplier::find($supplier_id);

        if (!$supplier || empty($supplier->account_id)) {
            return [
                'balance'     => 0,
                'type'        => '',
                'raw_balance' => 0,
            ];
        }

        $totals = JournalEntryDetail::join('journal_entries', 'journal_entries.journal_entry_id', '=', 'journal_entry_details.journal_entry_id')
            ->where('journal_entry_details.supplier_id', $supplier_id)
            ->where('journal_entry_details.account_id', $supplier->account_id)
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

    public function status($obj)
    {
        DB::beginTransaction();

        try {
            $payment = $this->model_supplier_payment->getModel()::with($this->with)->findOrFail($obj['supplier_payment_id']);
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
                'supplier_payment',
                $payment->supplier_payment_id,
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

    public function delete($supplier_payment_id)
    {
        DB::beginTransaction();

        try {
            $payment = $this->model_supplier_payment->getModel()::with($this->with)->findOrFail($supplier_payment_id);

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

            $this->logActivity('supplier_payment', $payment->supplier_payment_id, 'deleted');

            return true;
        } catch (Exception $e) {
            DB::rollBack();

            throw $e;
        }
    }

    /**
     * Auto-post a CPV/BPV Journal Voucher when a Supplier Payment is posted.
     * Idempotent: a no-op if an active voucher already exists for this payment.
     */
    protected function applyPosting(SupplierPayment $payment)
    {
        $existing = JournalEntry::where('source_type', JournalSourceTypes::SUPPLIER_PAYMENT)
            ->where('source_id', $payment->supplier_payment_id)
            ->where('is_deleted', 0)
            ->exists();

        if ($existing) {
            return;
        }

        $accounting_setting = AccountingSetting::where('business_id', $payment->business_id)->first();

        if (!$accounting_setting || !$accounting_setting->enable_accounting) {
            throw new Exception('Accounting is not enabled for this business. Please configure Accounting Settings before posting supplier payments.');
        }

        app(\App\Services\Concrete\Admin\AccountingPeriodService::class)->assertPostable($payment->business_id, now());

        $supplier = Supplier::find($payment->supplier_id);

        if (empty($supplier) || empty($supplier->account_id)) {
            throw new Exception('The selected supplier does not have a linked Chart of Account. Please configure it before posting this payment.');
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

        $short = $payment->payment_method === PaymentMethod::CASH ? 'CPV' : 'BPV';

        $journal = Journal::where('short', $short)->where('is_deleted', 0)->first();

        if (!$journal) {
            throw new Exception('No "' . $short . '" journal category found. Please configure it before posting supplier payments.');
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
            'description'      => 'Auto-generated ' . $short . ' for supplier payment ' . $payment->payment_no,
            'source_type'      => JournalSourceTypes::SUPPLIER_PAYMENT,
            'source_id'        => $payment->supplier_payment_id,
            'status'           => Status::POSTED,
            'postedby_id'      => Auth::id(),
            'date_posted'      => now(),
            'createdby_id'     => Auth::id(),
            'date_created'     => now(),
        ]);

        JournalEntryDetail::create([
            'journal_entry_detail_id' => generateUuid(),
            'journal_entry_id'        => $journal_entry->journal_entry_id,
            'account_id'              => $supplier->account_id,
            'debit'                   => $payment->amount,
            'credit'                  => 0,
            'supplier_id'             => $payment->supplier_id,
            'description'             => 'Supplier Payment - ' . $payment->payment_no,
        ]);

        JournalEntryDetail::create([
            'journal_entry_detail_id' => generateUuid(),
            'journal_entry_id'        => $journal_entry->journal_entry_id,
            'account_id'              => $payment->payment_account_id,
            'debit'                   => 0,
            'credit'                  => $payment->net_amount,
            'supplier_id'             => $payment->supplier_id,
            'description'             => 'Supplier Payment - ' . $payment->payment_no,
        ]);

        if ($payment->discount_amount > 0) {
            JournalEntryDetail::create([
                'journal_entry_detail_id' => generateUuid(),
                'journal_entry_id'        => $journal_entry->journal_entry_id,
                'account_id'              => $accounting_setting->default_discount_account_id,
                'debit'                   => 0,
                'credit'                  => $payment->discount_amount,
                'supplier_id'             => $payment->supplier_id,
                'description'             => 'Discount Received - ' . $payment->payment_no,
            ]);
        }

        if ($payment->tax_amount > 0) {
            JournalEntryDetail::create([
                'journal_entry_detail_id' => generateUuid(),
                'journal_entry_id'        => $journal_entry->journal_entry_id,
                'account_id'              => $accounting_setting->default_withholding_tax_account_id,
                'debit'                   => 0,
                'credit'                  => $payment->tax_amount,
                'supplier_id'             => $payment->supplier_id,
                'description'             => 'Withholding Tax - ' . $payment->payment_no,
            ]);
        }
    }

    /**
     * Reverse the CPV/BPV Journal Voucher created when a Supplier Payment was
     * posted. Idempotent: a no-op if nothing active remains to reverse.
     */
    protected function reversePosting(SupplierPayment $payment)
    {
        $journal_entry = JournalEntry::where('source_type', JournalSourceTypes::SUPPLIER_PAYMENT)
            ->where('source_id', $payment->supplier_payment_id)
            ->where('is_deleted', 0)
            ->first();

        if ($journal_entry) {
            $journal_entry->update([
                'is_deleted'   => 1,
                'deletedby_id' => Auth::id(),
                'date_deleted' => now(),
            ]);
        }
    }

    public function getByBusiness($business_id)
    {
        return $this->model_supplier_payment->getModel()::with($this->with)
            ->where('business_id', $business_id)
            ->where('is_deleted', 0)
            ->get();
    }
}
