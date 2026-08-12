<?php

namespace App\Services\Concrete\Admin\Reports;

use App\Enums\ReferenceType;
use App\Enums\RoleNames;
use App\Enums\TransactionType;
use App\Services\Concrete\Admin\ReferenceResolverService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;

class StockLedgerReportService
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

    public function __construct(
        protected StockLedgerQueryService $ledger_query_service,
        protected ReferenceResolverService $reference_resolver
    ) {
    }

    protected function filters(array $obj): array
    {
        return [
            'business_id'          => $obj['business_id'] ?? Auth::user()->business_id,
            'branch_id'            => $obj['branch_id'] ?? null,
            'warehouse_id'         => $obj['warehouse_id'] ?? null,
            'product_id'           => $obj['product_id'] ?? null,
            'product_variation_id' => $obj['product_variation_id'] ?? null,
            'category_id'          => $obj['category_id'] ?? null,
            'brand_id'             => $obj['brand_id'] ?? null,
            'transaction_type'     => $obj['transaction_type'] ?? null,
            'reference_type'       => $obj['reference_type'] ?? null,
            'start_date'           => $obj['start_date'] ?? null,
            'end_date'             => $obj['end_date'] ?? null,
            'allow_roles'          => $this->allow_roles,
        ];
    }

    /**
     * Filtered, resolved stock movement rows. Shared by getData(), print,
     * PDF and export so every output channel stays in sync. Also serves the
     * GRN Stock-In, Purchase Return Stock-Out and Transfer Movement report
     * requirements purely via the transaction_type/reference_type filters -
     * no separate report pages needed for those.
     */
    public function build(array $obj): Collection
    {
        $filters = $this->filters($obj);
        $transaction_types = TransactionType::getOptions();
        $source_modules = ReferenceType::getOptions();

        $rows = $this->ledger_query_service->transactions($filters);

        return $rows->map(function ($row) use ($transaction_types, $source_modules) {
            $is_inbound = TransactionType::isInbound($row->transaction_type);
            $qty = (float) $row->base_quantity;

            $row->direction = $is_inbound ? 'in' : 'out';
            $row->quantity_in = $is_inbound ? $qty : 0;
            $row->quantity_out = $is_inbound ? 0 : $qty;
            $row->value = round($qty * (float) $row->unit_price, 2);
            $row->transaction_type_label = $transaction_types[$row->transaction_type] ?? ucfirst($row->transaction_type);
            $row->source_module = $source_modules[$row->reference_type] ?? ucfirst($row->reference_type ?? '-');
            $row->reference_no = $this->reference_resolver->resolveDocNo($row->reference_type, $row->reference_id);

            return $row;
        });
    }

    public function getData(array $obj)
    {
        $filters = $this->filters($obj);
        $rows = $this->build($obj);

        $totals = [
            'total_qty_in'  => decimal(round($rows->sum('quantity_in'), 3)),
            'total_qty_out' => decimal(round($rows->sum('quantity_out'), 3)),
            'total_value'   => currency(round($rows->sum('value'), 2)),
        ];

        $balance = $this->ledger_query_service->singleItemBalance($filters);

        if ($balance) {
            $totals['opening_balance'] = decimal(round($balance['opening_balance'], 3));
            $totals['closing_balance'] = decimal(round($balance['closing_balance'], 3));
            $totals['current_avg_price'] = currency(round($balance['current_avg_price'], 2));
        }

        return DataTables::of($rows)
            ->addColumn('transaction_date', fn ($row) => localDate($row->transaction_date))
            ->addColumn('reference_type', fn ($row) => $row->source_module)
            ->addColumn('reference_no', fn ($row) => $row->reference_no)
            ->addColumn('warehouse_name', fn ($row) => $row->warehouse_name)
            ->addColumn('product_name', fn ($row) => $row->product_name)
            ->addColumn('variation_name', fn ($row) => $row->variation_name)
            ->addColumn('transaction_type_label', fn ($row) => $row->transaction_type_label)
            ->addColumn('quantity_in', fn ($row) => $row->quantity_in > 0 ? decimal($row->quantity_in) : '')
            ->addColumn('quantity_out', fn ($row) => $row->quantity_out > 0 ? decimal($row->quantity_out) : '')
            ->addColumn('unit_price', fn ($row) => currency($row->unit_price))
            ->addColumn('value', fn ($row) => currency($row->value))
            ->addColumn('quantity_after', fn ($row) => decimal($row->quantity_after))
            ->with($totals)
            ->make(true);
    }
}
