<?php

namespace App\Services\Concrete\Admin\Reports;

use App\Enums\JournalSourceTypes;
use App\Enums\RoleNames;
use App\Enums\Status;
use App\Models\JournalEntry;
use App\Models\SupplierPayment;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

class SupplierPaymentHistoryReportService
{
    protected array $allow_roles = [
        RoleNames::SUPERADMIN,
        RoleNames::BUSINESSADMIN,
        RoleNames::FINANCEMANAGER,
        RoleNames::ACCOUNTANT,
        RoleNames::REPORTINGANALYST,
        RoleNames::PURCHASEMANAGER,
    ];

    protected array $with = [
        'supplier',
        'purchase',
        'paymentAccount',
        'postedby',
    ];

    /**
     * Filtered, scoped query with no eager loads/selects - reused by both
     * the row listing and the totals aggregate.
     *
     * A supplier_payments.status = 'posted' row is only trusted if the
     * JournalEntry that should back it (source_type = SUPPLIER_PAYMENT) is
     * itself posted and not deleted - enforced via whereExists rather than
     * trusting the stored status column alone.
     */
    protected function scopedQuery(array $obj): Builder
    {
        $query = SupplierPayment::query()
            ->where('supplier_payments.is_deleted', 0)
            ->where('supplier_payments.status', Status::POSTED)
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('journal_entries')
                    ->whereColumn('journal_entries.source_id', 'supplier_payments.supplier_payment_id')
                    ->where('journal_entries.source_type', JournalSourceTypes::SUPPLIER_PAYMENT)
                    ->where('journal_entries.status', Status::POSTED)
                    ->where('journal_entries.is_deleted', 0);
            });

        if (!empty($obj['business_id'])) {
            $query->where('supplier_payments.business_id', $obj['business_id']);
        }

        if (!empty($obj['branch_id'])) {
            $query->where('supplier_payments.branch_id', $obj['branch_id']);
        }

        if (!empty($obj['supplier_id'])) {
            $query->where('supplier_payments.supplier_id', $obj['supplier_id']);
        }

        if (!empty($obj['payment_method'])) {
            $query->where('supplier_payments.payment_method', $obj['payment_method']);
        }

        if (!empty($obj['payment_account_id'])) {
            $query->where('supplier_payments.payment_account_id', $obj['payment_account_id']);
        }

        if (!empty($obj['start_date'])) {
            $query->where('supplier_payments.payment_date', '>=', Carbon::parse($obj['start_date'])->startOfDay());
        }

        if (!empty($obj['end_date'])) {
            $query->where('supplier_payments.payment_date', '<=', Carbon::parse($obj['end_date'])->endOfDay());
        }

        return applyRoleScope($query, $this->allow_roles, 'supplier_payments.business_id', 'supplier_payments.branch_id');
    }

    /**
     * Listing query with eager loads and the related, posted Journal Entry
     * id attached (for the drill-down link to its CPV/BPV print view).
     */
    public function baseQuery(array $obj): Builder
    {
        return $this->scopedQuery($obj)
            ->with($this->with)
            ->addSelect('supplier_payments.*')
            ->addSelect(['journal_entry_id' => JournalEntry::query()
                ->select('journal_entry_id')
                ->whereColumn('journal_entries.source_id', 'supplier_payments.supplier_payment_id')
                ->where('journal_entries.source_type', JournalSourceTypes::SUPPLIER_PAYMENT)
                ->where('journal_entries.status', Status::POSTED)
                ->where('journal_entries.is_deleted', 0)
                ->limit(1),
            ]);
    }

    public function getRows(array $obj)
    {
        return $this->baseQuery($obj)->orderByDesc('supplier_payments.payment_date')->get();
    }

    public function exportQuery(array $obj): Builder
    {
        return $this->baseQuery($obj)->orderByDesc('supplier_payments.payment_date');
    }

    protected function totals(array $obj)
    {
        return $this->scopedQuery($obj)->selectRaw('
                COALESCE(SUM(supplier_payments.net_amount),0) as total_net,
                COALESCE(SUM(supplier_payments.tax_amount),0) as total_tax,
                COALESCE(SUM(supplier_payments.discount_amount),0) as total_discount
            ')->first();
    }

    public function getData(array $obj)
    {
        $query = $this->baseQuery($obj)->orderByDesc('supplier_payments.payment_date');
        $totals = $this->totals($obj);

        return DataTables::of($query)
            ->addColumn('payment_date', fn ($row) => localDate($row->payment_date))
            ->addColumn('supplier', fn ($row) => $row->supplier->name ?? '')
            ->addColumn('payment_method', fn ($row) => ucwords(str_replace('_', ' ', $row->payment_method)))
            ->addColumn('purchase_no', fn ($row) => $row->purchase->purchase_no ?? '')
            ->addColumn('payment_account', fn ($row) => $row->paymentAccount->name ?? '')
            ->addColumn('tax_amount', fn ($row) => currency($row->tax_amount))
            ->addColumn('discount_amount', fn ($row) => currency($row->discount_amount))
            ->addColumn('net_amount', fn ($row) => currency($row->net_amount))
            ->addColumn('postedby', fn ($row) => $row->postedby->name ?? '')
            ->addColumn('status', fn ($row) => ucfirst($row->status))
            ->addColumn('action', function ($row) {
                $html = '<a class="btn btn-icon btn-outline-primary" target="_blank" href="' . url('admin/supplier-payment/' . $row->supplier_payment_id . '/print') . '" title="View Payment"><i class="fa fa-eye"></i></a>';

                if (!empty($row->journal_entry_id)) {
                    $html .= ' <a class="btn btn-icon btn-outline-secondary" target="_blank" href="' . url('admin/journal-entry/' . $row->journal_entry_id . '/print') . '" title="View Journal Voucher"><i class="fa fa-book"></i></a>';
                }

                return $html;
            })
            ->rawColumns(['action'])
            ->with([
                'total_net'      => currency($totals->total_net ?? 0),
                'total_tax'      => currency($totals->total_tax ?? 0),
                'total_discount' => currency($totals->total_discount ?? 0),
            ])
            ->make(true);
    }
}
