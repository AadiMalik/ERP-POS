<?php

namespace App\Services\Concrete\Admin;

use App\Enums\Filter;
use App\Enums\JournalSourceTypes;
use App\Enums\RoleNames;
use App\Enums\Status;
use App\Models\AccountingSetting;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\JournalEntryDetail;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Repository\Repository;
use App\Traits\Auditable;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Yajra\DataTables\DataTables;

class SupplierService
{
    use Auditable;

    protected $model_supplier;
    protected $with = [
        'business',
        'branch',
        'account'
    ];

    public function __construct()
    {
        $this->model_supplier = new Repository(new Supplier());
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
        if (isset($obj['brand_id']) && $obj['brand_id'] != 0 && $obj['brand_id'] != "") {
            $wh[] = ['brand_id', $obj['brand_id']];
        }
        if (isset($obj['account_id']) && $obj['account_id'] != 0 && $obj['account_id'] != "") {
            $wh[] = ['account_id', $obj['account_id']];
        }
        if (!empty($obj['start_date'])) {
            $wh[] = ['date_created', '>=', Carbon::parse($obj['start_date'])->startOfDay()];
        }

        if (!empty($obj['end_date'])) {
            $wh[] = ['date_created', '<=', Carbon::parse($obj['end_date'])->endOfDay()];
        }
        $allow_roles = [
            RoleNames::SUPERADMIN,
            RoleNames::BUSINESSADMIN
        ];
        $datatable = $this->model_supplier->getModel()::where($wh)
            ->with($this->with)
            ->orderBy('name', $orderBy);
        $datatable = applyRoleScope($datatable, $allow_roles);
        return DataTables::of($datatable)
            ->addColumn('business', function ($item) {
                return $item->business->name ?? '';
            })
            ->addColumn('branch', function ($item) {
                return $item->branch->name ?? '';
            })
            ->addColumn('account', function ($item) {
                return $item->account->name ?? '';
            })
            ->addColumn('status', function ($item) {

                $checked = $item->status == Status::ACTIVE ? 'checked' : '';

                return '
                <div class="form-check form-switch mb-0">
                    <input
                        class="form-check-input statusSuuplier"
                        type="checkbox"
                        data-id="' . $item->supplier_id . '"
                        ' . $checked . '>
                </div>
            ';
            })
            ->addColumn('action', function ($item) {

                return "
                    <a class='btn btn-icon btn-outline-info mr-2'
                     href='" . route('supplier.show', $item->supplier_id) . "'
                    id='viewSupplier'>

                    <i class='fa fa-eye'></i>
                    </a>

                    <a class='btn btn-icon btn-outline-primary mr-2'
                     href='" . route('supplier.edit', $item->supplier_id) . "'
                    id='editSupplier'>

                    <i class='fa fa-pencil'></i>
                    </a>

                    <a class='btn btn-icon btn-outline-danger'
                    id='deleteSupplier'
                    data-id='{$item->supplier_id}'>

                    <i class='fa fa-trash'></i>
                    </a>
                ";
            })
            ->rawColumns(['business', 'branch', 'account', 'status', 'action'])
            ->make(true);
    }

    public function save($obj)
    {
        DB::beginTransaction();

        try {

            // =========================
            // UPDATE
            // =========================
            if (!empty($obj['supplier_id'])) {

                $supplier = $this->model_supplier->find($obj['supplier_id']);

                if (!$supplier) {
                    throw new Exception('supplier not found');
                }

                $old_values = $supplier->only(['name', 'credit_limit', 'credit_days']);

                // Always refresh payable COA from Accounting Settings on update.
                $obj['account_id'] = $this->resolveDefaultSupplierAccountId(
                    $supplier->business_id ?? ($obj['business_id'] ?? Auth::user()->business_id)
                );
                $obj['updatedby_id'] = Auth::user()->id;
                $obj['date_updated'] = now();

                $this->model_supplier->update($obj, $obj['supplier_id']);

                DB::commit();

                $updated = $this->model_supplier->find($supplier->supplier_id);

                $this->logActivity('supplier', $supplier->supplier_id, 'updated', $old_values, $updated->only(['name', 'credit_limit', 'credit_days']));

                return $updated;
            }

            //check limit
            $limit = checkPackageLimit('suppliers');

            if (!$limit['status']) {
                throw new Exception($limit['message']);
            }
            // =========================
            // CREATE
            // =========================

            $obj['supplier_id'] = generateUuid();
            $obj['code'] = $obj['code'] ?? generateSupplierCode();
            $obj['account_id'] = $this->resolveDefaultSupplierAccountId($obj['business_id'] ?? Auth::user()->business_id);
            $obj['createdby_id'] = Auth::user()->id;
            $obj['date_created'] = now();

            $opening_balance = (float) ($obj['opening_balance'] ?? 0);
            $opening_balance_type = $obj['opening_balance_type'] ?? null;

            $supplier = $this->model_supplier->create($obj);

            DB::commit();

            $this->logActivity('supplier', $supplier->supplier_id, 'created', null, $supplier->only(['name', 'credit_limit', 'credit_days']));

            if ($opening_balance > 0 && in_array($opening_balance_type, ['Dr', 'Cr'], true)) {
                $this->postOpeningBalance($supplier, $opening_balance, $opening_balance_type);
            }

            return $supplier;
        } catch (Exception $e) {

            DB::rollBack();

            throw $e;
        }
    }

    public function getById($supplier_id)
    {
        return $this->model_supplier->getModel()::with($this->with)->find($supplier_id);
    }

    /**
     * Default payable COA for a business — always from accounting_settings.
     */
    public function resolveDefaultSupplierAccountId(?string $business_id): ?string
    {
        if (empty($business_id)) {
            return null;
        }

        return AccountingSetting::where('business_id', $business_id)
            ->value('default_supplier_account_id');
    }

    /**
     * Point every active supplier for the business at the current default
     * supplier COA when Accounting Settings → Supplier Account changes.
     */
    public function syncDefaultAccount(string $business_id, ?string $account_id): int
    {
        return $this->model_supplier->getModel()::where('business_id', $business_id)
            ->where('is_deleted', 0)
            ->update([
                'account_id'   => $account_id,
                'updatedby_id' => Auth::id(),
                'date_updated' => now(),
            ]);
    }

    public function status($supplier_id)
    {
        $result = $this->model_supplier->update([
            'status' => ($this->model_supplier->find($supplier_id)->status == Status::ACTIVE ? Status::INACTIVE : Status::ACTIVE),
            'updatedby_id' => Auth::id(),
            'date_updated' => now()
        ], $supplier_id);

        $this->logActivity('supplier', $supplier_id, 'status_changed');

        return $result;
    }

    public function delete($supplier_id)
    {
        $result = $this->model_supplier->update([
            'is_deleted' => 1,
            'deletedby_id' => Auth::id(),
            'date_deleted' => now()
        ], $supplier_id);

        $this->logActivity('supplier', $supplier_id, 'deleted');

        return $result;
    }

    public function getAll()
    {
        return $this->model_supplier->getModel()::with($this->with)
            ->where('business_id', Auth::user()->business_id)
            ->where('is_deleted', 0)
            ->get();
    }
    public function getAllActive()
    {
        return $this->model_supplier->getModel()::with($this->with)
            ->where('business_id', Auth::user()->business_id)
            ->where('status', Status::ACTIVE)
            ->where('is_deleted', 0)
            ->get();
    }

    public function getByBusiness($business_id)
    {
        return $this->model_supplier->getModel()::with($this->with)
            ->where('business_id', $business_id)
            ->where('is_deleted', 0)
            ->get();
    }

    public function getByBranch($branch_id)
    {
        return $this->model_supplier->getModel()::with($this->with)
            ->where('branch_id', $branch_id)
            ->where('is_deleted', 0)
            ->get();
    }

    /**
     * Auto-post an OBV Journal Voucher for a supplier's opening balance -
     * mirrors CustomerService::postOpeningBalance(). Idempotent by
     * source_id (the supplier_id).
     */
    protected function postOpeningBalance(Supplier $supplier, float $amount, string $type)
    {
        $existing = JournalEntry::where('source_type', JournalSourceTypes::OPENING_BALANCE)
            ->where('source_id', $supplier->supplier_id)
            ->where('is_deleted', 0)
            ->exists();

        if ($existing) {
            return;
        }

        $accounting_setting = AccountingSetting::where('business_id', $supplier->business_id)->first();

        if (!$accounting_setting || !$accounting_setting->enable_accounting) {
            return;
        }

        try {
            app(AccountingPeriodService::class)->assertPostable($supplier->business_id, now());
        } catch (Exception $e) {
            return;
        }

        $ap_account_id = $supplier->account_id ?? ($accounting_setting->default_supplier_account_id ?? null);
        $contra_account_id = $accounting_setting->default_supplier_account_id ?? null;

        if (empty($ap_account_id)) {
            return;
        }

        $journal = Journal::where('short', 'OBV')->where('is_deleted', 0)->first();

        if (!$journal) {
            return;
        }

        $journal_entry = JournalEntry::create([
            'journal_entry_id' => generateUuid(),
            'journal_id'       => $journal->journal_id,
            'business_id'      => $supplier->business_id,
            'branch_id'        => $supplier->branch_id,
            'entry_no'         => generateJVNum($journal->journal_id),
            'reference_no'     => $supplier->code,
            'entry_date'       => now(),
            'description'      => 'Opening balance for supplier ' . $supplier->code,
            'source_type'      => JournalSourceTypes::OPENING_BALANCE,
            'source_id'        => $supplier->supplier_id,
            'status'           => Status::POSTED,
            'postedby_id'      => Auth::id(),
            'date_posted'      => now(),
            'createdby_id'     => Auth::id(),
            'date_created'     => now(),
        ]);

        // 'Cr' opening balance = business already owes the supplier more
        // (credit AP), 'Dr' = supplier owes the business (debit AP, e.g. a
        // prepayment/advance).
        $ap_credit = $type === 'Cr' ? $amount : 0;
        $ap_debit = $type === 'Cr' ? 0 : $amount;

        JournalEntryDetail::create([
            'journal_entry_detail_id' => generateUuid(),
            'journal_entry_id'        => $journal_entry->journal_entry_id,
            'account_id'              => $ap_account_id,
            'debit'                   => $ap_debit,
            'credit'                  => $ap_credit,
            'supplier_id'             => $supplier->supplier_id,
            'description'             => 'Opening Balance - ' . $supplier->code,
        ]);

        if (!empty($contra_account_id) && $contra_account_id !== $ap_account_id) {
            JournalEntryDetail::create([
                'journal_entry_detail_id' => generateUuid(),
                'journal_entry_id'        => $journal_entry->journal_entry_id,
                'account_id'              => $contra_account_id,
                'debit'                   => $ap_credit,
                'credit'                  => $ap_debit,
                'supplier_id'             => $supplier->supplier_id,
                'description'             => 'Opening Balance - ' . $supplier->code,
            ]);
        }
    }

    /**
     * All purchases for this supplier plus their payment allocation, reusing
     * AccountsPayableInvoiceService so the numbers always agree with the
     * Accounts Payable / Supplier Aging reports.
     */
    public function getSupplierHistory($supplier_id, $business_id = null)
    {
        $business_id = $business_id ?? Auth::user()->business_id;

        $invoices = app(\App\Services\Concrete\Admin\Reports\AccountsPayableInvoiceService::class)
            ->getInvoices(['business_id' => $business_id, 'supplier_id' => $supplier_id]);

        $payments = \App\Models\SupplierPayment::where('supplier_id', $supplier_id)
            ->where('business_id', $business_id)
            ->where('is_deleted', 0)
            ->orderByDesc('payment_date')
            ->get();

        return [
            'invoices' => $invoices,
            'payments' => $payments,
        ];
    }

    /**
     * Chronological merge of purchase invoices + supplier payments for the
     * Timeline tab.
     */
    public function getSupplierTimeline($supplier_id, $business_id = null)
    {
        $history = $this->getSupplierHistory($supplier_id, $business_id);

        $events = collect();

        foreach ($history['invoices'] as $invoice) {
            $events->push([
                'type' => 'purchase',
                'date' => $invoice->invoice_date,
                'reference' => $invoice->invoice_number,
                'amount' => $invoice->invoiced_amount,
                'description' => 'Purchase Invoice' . ($invoice->outstanding_amount > 0 ? ' (Credit)' : ''),
                'status' => $invoice->status,
            ]);
        }

        foreach ($history['payments'] as $payment) {
            $events->push([
                'type' => 'payment',
                'date' => $payment->payment_date,
                'reference' => $payment->payment_no,
                'amount' => $payment->amount,
                'description' => 'Payment Made' . ($payment->purchase_id ? ' (against purchase)' : ' (on account)'),
                'status' => $payment->status,
            ]);
        }

        return $events->sortByDesc('date')->values();
    }
}
