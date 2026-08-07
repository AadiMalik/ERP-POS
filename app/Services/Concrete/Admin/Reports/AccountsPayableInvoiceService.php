<?php

namespace App\Services\Concrete\Admin\Reports;

use App\Enums\JournalSourceTypes;
use App\Enums\Status;
use App\Models\GoodReceiptNote;
use App\Models\JournalEntryDetail;
use App\Models\Purchase;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Shared engine for the Accounts Payable and Supplier Aging reports.
 *
 * A supplier's payable is created by one of two mutually exclusive posted
 * events, depending on Purchase::purchase_type (see PurchaseService and
 * GrnService):
 *  - purchase_type = 'purchase_request': the Purchase itself never posts;
 *    only its GRN does, once approved (source_type = Goods Receipt).
 *  - purchase_type = 'direct': GRNs are never created for it (enforced in
 *    GrnService::save()); the Purchase posts directly on approval
 *    (source_type = Purchase).
 * Both are treated as the supplier "invoice" here. Outstanding/paid
 * amounts are derived entirely from posted journal_entry_details, never
 * from stored purchases.payment_status.
 */
class AccountsPayableInvoiceService
{
    /**
     * $filters: business_id, branch_id, supplier_id, allow_roles (array)
     */
    public function getInvoices(array $filters = []): Collection
    {
        $invoices = $this->fetchGrnInvoices($filters)->concat($this->fetchDirectPurchaseInvoices($filters));
        $payments = $this->fetchPayments($filters);

        return $this->allocatePayments($invoices, $payments);
    }

    protected function fetchGrnInvoices(array $filters): Collection
    {
        $query = GoodReceiptNote::query()
            ->join('journal_entries', function ($join) {
                $join->on('journal_entries.source_id', '=', 'good_receipt_notes.good_receipt_note_id')
                    ->where('journal_entries.source_type', JournalSourceTypes::GOODS_RECEIPT)
                    ->where('journal_entries.is_deleted', 0)
                    ->where('journal_entries.status', Status::POSTED);
            })
            ->join('suppliers', 'suppliers.supplier_id', '=', 'good_receipt_notes.supplier_id')
            ->join('journal_entry_details', function ($join) {
                $join->on('journal_entry_details.journal_entry_id', '=', 'journal_entries.journal_entry_id')
                    ->whereColumn('journal_entry_details.supplier_id', 'good_receipt_notes.supplier_id')
                    ->whereColumn('journal_entry_details.account_id', 'suppliers.account_id')
                    ->where('journal_entry_details.credit', '>', 0);
            })
            ->leftJoin('purchases', 'purchases.purchase_id', '=', 'good_receipt_notes.purchase_id')
            ->where('good_receipt_notes.is_deleted', 0);

        $this->applyScope($query, $filters, 'good_receipt_notes.business_id', 'good_receipt_notes.branch_id', 'good_receipt_notes.supplier_id');

        return $query->orderBy('good_receipt_notes.good_receipt_note_date')
            ->get([
                'good_receipt_notes.good_receipt_note_id',
                'good_receipt_notes.good_receipt_note_no as invoice_number',
                'good_receipt_notes.good_receipt_note_date as invoice_date',
                'good_receipt_notes.purchase_id',
                'good_receipt_notes.business_id',
                'good_receipt_notes.branch_id',
                'good_receipt_notes.supplier_id',
                'purchases.purchase_no',
                'suppliers.name as supplier_name',
                'suppliers.credit_days',
                'journal_entry_details.credit as invoiced_amount',
            ]);
    }

    protected function fetchDirectPurchaseInvoices(array $filters): Collection
    {
        $query = Purchase::query()
            ->join('journal_entries', function ($join) {
                $join->on('journal_entries.source_id', '=', 'purchases.purchase_id')
                    ->where('journal_entries.source_type', JournalSourceTypes::PURCHASE)
                    ->where('journal_entries.is_deleted', 0)
                    ->where('journal_entries.status', Status::POSTED);
            })
            ->join('suppliers', 'suppliers.supplier_id', '=', 'purchases.supplier_id')
            ->join('journal_entry_details', function ($join) {
                $join->on('journal_entry_details.journal_entry_id', '=', 'journal_entries.journal_entry_id')
                    ->whereColumn('journal_entry_details.supplier_id', 'purchases.supplier_id')
                    ->whereColumn('journal_entry_details.account_id', 'suppliers.account_id')
                    ->where('journal_entry_details.credit', '>', 0);
            })
            ->where('purchases.is_deleted', 0)
            ->where('purchases.purchase_type', 'direct');

        $this->applyScope($query, $filters, 'purchases.business_id', 'purchases.branch_id', 'purchases.supplier_id');

        return $query->orderBy('purchases.purchase_date')
            ->get([
                'purchases.purchase_id as good_receipt_note_id',
                'purchases.purchase_no as invoice_number',
                'purchases.purchase_date as invoice_date',
                'purchases.purchase_id',
                'purchases.business_id',
                'purchases.branch_id',
                'purchases.supplier_id',
                'purchases.purchase_no',
                'suppliers.name as supplier_name',
                'suppliers.credit_days',
                'journal_entry_details.credit as invoiced_amount',
            ]);
    }

    protected function applyScope(Builder $query, array $filters, string $businessColumn, string $branchColumn, string $supplierColumn): void
    {
        if (!empty($filters['business_id'])) {
            $query->where($businessColumn, $filters['business_id']);
        }

        if (!empty($filters['branch_id'])) {
            $query->where($branchColumn, $filters['branch_id']);
        }

        if (!empty($filters['supplier_id'])) {
            $query->where($supplierColumn, $filters['supplier_id']);
        }

        applyRoleScope($query, $filters['allow_roles'] ?? [], $businessColumn, $branchColumn);
    }

    protected function fetchPayments(array $filters): Collection
    {
        $query = JournalEntryDetail::query()
            ->join('journal_entries', 'journal_entries.journal_entry_id', '=', 'journal_entry_details.journal_entry_id')
            ->join('supplier_payments', 'supplier_payments.supplier_payment_id', '=', 'journal_entries.source_id')
            ->where('journal_entries.source_type', JournalSourceTypes::SUPPLIER_PAYMENT)
            ->where('journal_entries.status', Status::POSTED)
            ->where('journal_entries.is_deleted', 0)
            ->where('journal_entry_details.debit', '>', 0)
            ->whereColumn('journal_entry_details.supplier_id', 'supplier_payments.supplier_id');

        if (!empty($filters['business_id'])) {
            $query->where('journal_entries.business_id', $filters['business_id']);
        }

        if (!empty($filters['branch_id'])) {
            $query->where('journal_entries.branch_id', $filters['branch_id']);
        }

        if (!empty($filters['supplier_id'])) {
            $query->where('supplier_payments.supplier_id', $filters['supplier_id']);
        }

        $query = applyRoleScope($query, $filters['allow_roles'] ?? [], 'journal_entries.business_id', 'journal_entries.branch_id');

        return $query->orderBy('supplier_payments.payment_date')
            ->get([
                'supplier_payments.purchase_id',
                'supplier_payments.supplier_id',
                'supplier_payments.payment_no',
                'supplier_payments.payment_date',
                'journal_entry_details.debit as paid_amount',
            ]);
    }

    /**
     * Allocate each purchase's posted payments against that purchase's
     * posted GRNs oldest-first. This is an explicit modeling assumption:
     * supplier_payments.purchase_id links to a Purchase, not a specific
     * GRN, so when one Purchase has multiple staged GRNs there is no
     * stored 1:1 link telling us which invoice a payment was "for". If a
     * good_receipt_note_id is ever added to supplier_payments, this becomes
     * a direct lookup instead of FIFO.
     */
    protected function allocatePayments(Collection $invoices, Collection $payments): Collection
    {
        $paymentsByPurchase = $payments->groupBy('purchase_id');

        return $invoices
            ->groupBy('purchase_id')
            ->flatMap(function (Collection $grnRows, $purchase_id) use ($paymentsByPurchase) {
                $grnRows = $grnRows->sortBy('invoice_date')->values();

                $purchasePayments = $paymentsByPurchase->get($purchase_id, collect());
                $paymentPool = (float) $purchasePayments->sum('paid_amount');
                $paymentRefs = $purchasePayments->pluck('payment_no')->unique()->values();

                return $grnRows->map(function ($grn) use (&$paymentPool, $paymentRefs) {
                    $invoiced = (float) $grn->invoiced_amount;
                    $applied = min($invoiced, max($paymentPool, 0));

                    $grn->due_date = Carbon::parse($grn->invoice_date)->addDays((int) ($grn->credit_days ?? 0));
                    $grn->paid_amount = round($applied, 2);
                    $grn->outstanding_amount = round($invoiced - $applied, 2);
                    $grn->remaining_balance = $grn->outstanding_amount;
                    $grn->payment_references = $applied > 0 ? $paymentRefs->implode(', ') : '';
                    $grn->status = $this->deriveStatus($grn->paid_amount, $invoiced);

                    $paymentPool -= $applied;

                    return $grn;
                });
            })
            ->values();
    }

    protected function deriveStatus(float $paid, float $invoiced): string
    {
        if ($paid <= 0) {
            return 'unpaid';
        }

        if ($paid >= round($invoiced, 2)) {
            return 'paid';
        }

        return 'partial';
    }
}
