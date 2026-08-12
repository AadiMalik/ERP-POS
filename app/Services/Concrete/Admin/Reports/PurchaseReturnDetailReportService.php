<?php

namespace App\Services\Concrete\Admin\Reports;

use App\Enums\RoleNames;
use App\Enums\Status;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;

class PurchaseReturnDetailReportService
{
    protected array $allow_roles = [
        RoleNames::SUPERADMIN,
        RoleNames::BUSINESSADMIN,
        RoleNames::FINANCEMANAGER,
        RoleNames::ACCOUNTANT,
        RoleNames::REPORTINGANALYST,
        RoleNames::PURCHASEMANAGER,
        RoleNames::INVENTORYMANAGER,
    ];

    public const STATUS_LABELS = [
        Status::PENDING   => 'Pending',
        Status::APPROVED  => 'Approved',
        Status::CANCELLED => 'Cancelled',
    ];

    public function __construct(protected PurchaseReturnQueryService $query_service)
    {
    }

    protected function filters(array $obj): array
    {
        return [
            'business_id'          => $obj['business_id'] ?? Auth::user()->business_id,
            'branch_id'            => $obj['branch_id'] ?? null,
            'supplier_id'          => $obj['supplier_id'] ?? null,
            'warehouse_id'         => $obj['warehouse_id'] ?? null,
            'product_id'           => $obj['product_id'] ?? null,
            'product_variation_id' => $obj['product_variation_id'] ?? null,
            'return_type'          => $obj['return_type'] ?? null,
            'status'               => $obj['status'] ?? null,
            'start_date'           => $obj['start_date'] ?? null,
            'end_date'             => $obj['end_date'] ?? null,
            'allow_roles'          => $this->allow_roles,
        ];
    }

    /**
     * Line-level Purchase Return rows with the source Purchase/GRN
     * reference, quantities, costs, tax/discount, status, posted state and
     * audit users. Shared by getData(), print, PDF and export so every
     * output stays in sync.
     */
    public function build(array $obj): Collection
    {
        $filters = $this->filters($obj);

        $rows = $this->query_service->baseQuery($filters)
            ->leftJoin('purchases', 'purchases.purchase_id', '=', 'purchase_returns.purchase_id')
            ->leftJoin('good_receipt_notes', 'good_receipt_notes.good_receipt_note_id', '=', 'purchase_returns.good_receipt_note_id')
            ->leftJoin('units', 'units.unit_id', '=', 'purchase_return_details.unit_id')
            ->leftJoin('users as created_users', 'created_users.id', '=', 'purchase_returns.createdby_id')
            ->leftJoin('users as updated_users', 'updated_users.id', '=', 'purchase_returns.updatedby_id')
            ->orderBy('purchase_returns.purchase_return_date')
            ->get([
                'purchase_returns.purchase_return_id',
                'purchase_returns.purchase_return_no',
                'purchase_returns.purchase_return_date',
                'purchase_returns.return_type',
                'purchase_returns.status',
                'purchase_returns.business_id',
                'purchase_returns.branch_id',
                'purchase_returns.warehouse_id',
                'purchase_returns.purchase_id',
                'purchase_returns.good_receipt_note_id',
                'purchases.purchase_no',
                'good_receipt_notes.good_receipt_note_no',
                'suppliers.name as supplier_name',
                'branches.name as branch_name',
                'warehouses.name as warehouse_name',
                'purchase_return_details.product_id',
                'products.name as product_name',
                'purchase_return_details.product_variation_id',
                'product_variations.name as variation_name',
                'purchase_return_details.received_quantity',
                'purchase_return_details.already_returned_quantity',
                'purchase_return_details.return_quantity',
                'purchase_return_details.conversion_factor',
                'units.name as unit_name',
                'purchase_return_details.unit_price',
                'purchase_return_details.discount',
                'purchase_return_details.discount_amount',
                'purchase_return_details.tax',
                'purchase_return_details.tax_amount',
                'purchase_return_details.subtotal',
                'purchase_return_details.total',
                'created_users.name as created_by_name',
                'updated_users.name as updated_by_name',
            ]);

        $postedIds = $this->query_service->postedMap($filters);

        return $rows->map(function ($row) use ($postedIds) {
            $row->source_no = $row->return_type === 'grn' ? $row->good_receipt_note_no : $row->purchase_no;
            $row->status_label = self::STATUS_LABELS[$row->status] ?? ucfirst($row->status);
            $row->posted = $row->status === Status::APPROVED
                ? ($postedIds->contains($row->purchase_return_id) ? 'Posted' : 'Unposted')
                : 'N/A';

            return $row;
        });
    }

    public function getData(array $obj)
    {
        $rows = $this->build($obj);

        $totals = [
            'grand_qty'      => decimal(round($rows->sum('return_quantity'), 3)),
            'grand_subtotal' => currency(round($rows->sum('subtotal'), 2)),
            'grand_discount' => currency(round($rows->sum('discount_amount'), 2)),
            'grand_tax'      => currency(round($rows->sum('tax_amount'), 2)),
            'grand_total'    => currency(round($rows->sum('total'), 2)),
        ];

        return DataTables::of($rows)
            ->addColumn('purchase_return_date', fn ($row) => localDate($row->purchase_return_date))
            ->addColumn('purchase_return_no', fn ($row) => $row->purchase_return_no)
            ->addColumn('source_no', fn ($row) => ($row->return_type === 'grn' ? 'GRN: ' : 'Purchase: ') . ($row->source_no ?? ''))
            ->addColumn('supplier_name', fn ($row) => $row->supplier_name)
            ->addColumn('branch_name', fn ($row) => $row->branch_name)
            ->addColumn('warehouse_name', fn ($row) => $row->warehouse_name)
            ->addColumn('product_name', fn ($row) => $row->product_name)
            ->addColumn('variation_name', fn ($row) => $row->variation_name)
            ->addColumn('return_quantity', fn ($row) => decimal($row->return_quantity) . ' ' . $row->unit_name)
            ->addColumn('unit_price', fn ($row) => currency($row->unit_price))
            ->addColumn('discount_amount', fn ($row) => currency($row->discount_amount))
            ->addColumn('tax_amount', fn ($row) => currency($row->tax_amount))
            ->addColumn('subtotal', fn ($row) => currency($row->subtotal))
            ->addColumn('total', fn ($row) => currency($row->total))
            ->addColumn('status_label', fn ($row) => $row->status_label)
            ->addColumn('posted', fn ($row) => $row->posted)
            ->addColumn('created_by_name', fn ($row) => $row->created_by_name ?? '')
            ->addColumn('action', function ($row) {
                $links = '';

                if ($row->return_type === 'grn' && $row->good_receipt_note_id) {
                    $links .= "<a class='btn btn-icon btn-outline-secondary mr-2' target='_blank' title='View GRN' href='" . route('good-receipt-note.print', $row->good_receipt_note_id) . "'><i class='fa fa-file-text'></i></a>";
                } elseif ($row->purchase_id) {
                    $links .= "<a class='btn btn-icon btn-outline-secondary mr-2' target='_blank' title='View Purchase' href='" . route('purchase.print', $row->purchase_id) . "'><i class='fa fa-file-text'></i></a>";
                }

                $stockLedgerUrl = url('admin/reports/stock-ledger') . '?' . http_build_query([
                    'business_id'          => $row->business_id,
                    'warehouse_id'         => $row->warehouse_id,
                    'product_id'           => $row->product_id,
                    'product_variation_id' => $row->product_variation_id,
                    'reference_type'       => 'purchase_return',
                ]);

                $links .= "<a class='btn btn-icon btn-outline-info' target='_blank' title='View in Stock Ledger' href='{$stockLedgerUrl}'><i class='fa fa-history'></i></a>";

                return $links;
            })
            ->rawColumns(['action'])
            ->with($totals)
            ->make(true);
    }
}
