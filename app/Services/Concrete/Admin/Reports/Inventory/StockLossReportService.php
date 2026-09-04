<?php

namespace App\Services\Concrete\Admin\Reports\Inventory;

use App\Enums\ReferenceType;
use App\Enums\TransactionType;
use App\Services\Concrete\Admin\ReferenceResolverService;
use App\Services\Concrete\Admin\Reports\Inventory\Concerns\AppliesInventoryReportScope;
use App\Services\Concrete\Admin\Reports\StockLedgerQueryService;
use Illuminate\Support\Collection;
use Yajra\DataTables\DataTables;

/**
 * Stock Loss / Wastage / Damage — ledger movements of damage, wastage, expired.
 * Note: dedicated damage/wastage/expiry note CRUD does not exist; data only
 * appears when such transaction_types were posted to the stock ledger.
 */
class StockLossReportService
{
    use AppliesInventoryReportScope;

    public function __construct(
        protected StockLedgerQueryService $ledger_query_service,
        protected ReferenceResolverService $reference_resolver
    ) {
    }

    public function build(array $obj): Collection
    {
        $filters = $this->baseFilters($obj);
        $type = $obj['transaction_type'] ?? null;
        $filters['transaction_types'] = $type
            ? [$type]
            : [TransactionType::DAMAGE, TransactionType::WASTAGE, TransactionType::EXPIRED];

        $types = TransactionType::getOptions();
        $sources = ReferenceType::getOptions();

        return $this->ledger_query_service->transactions($filters)->map(function ($row) use ($types, $sources) {
            $qty = (float) $row->base_quantity;
            $url = $this->reference_resolver->resolveUrl($row->reference_type, $row->reference_id);

            return (object) [
                'transaction_date' => $row->transaction_date,
                'transaction_type_label' => $types[$row->transaction_type] ?? $row->transaction_type,
                'source_module' => $sources[$row->reference_type] ?? ($row->reference_type ?? '-'),
                'reference_no' => $this->reference_resolver->resolveDocNo($row->reference_type, $row->reference_id),
                'reference_url' => $url,
                'warehouse_name' => $row->warehouse_name,
                'product_name' => $row->product_name,
                'variation_name' => $row->variation_name,
                'quantity' => $qty,
                'unit_price' => (float) $row->unit_price,
                'value' => round($qty * (float) $row->unit_price, 2),
                'ledger_url' => url('/admin/reports/stock-ledger') . '?' . http_build_query([
                    'product_id' => $row->product_id,
                    'product_variation_id' => $row->product_variation_id,
                    'warehouse_id' => $row->warehouse_id,
                    'business_id' => $row->business_id,
                    'transaction_type' => $row->transaction_type,
                ]),
            ];
        });
    }

    public function getData(array $obj)
    {
        $rows = $this->build($obj);
        $totals = [
            'total_qty' => decimal(round($rows->sum('quantity'), 3)),
            'total_value' => currency(round($rows->sum('value'), 2)),
        ];

        return DataTables::of($rows)
            ->addColumn('transaction_date', fn ($row) => localDate($row->transaction_date))
            ->addColumn('transaction_type_label', fn ($row) => e($row->transaction_type_label))
            ->addColumn('source_module', fn ($row) => e($row->source_module))
            ->addColumn('reference_no', function ($row) {
                if ($row->reference_url) {
                    return '<a href="' . e($row->reference_url) . '">' . e($row->reference_no) . '</a>';
                }
                return e($row->reference_no);
            })
            ->addColumn('warehouse_name', fn ($row) => e($row->warehouse_name))
            ->addColumn('product_name', fn ($row) => '<a href="' . e($row->ledger_url) . '">' . e($row->product_name) . '</a>')
            ->addColumn('variation_name', fn ($row) => e($row->variation_name))
            ->addColumn('quantity', fn ($row) => decimal($row->quantity))
            ->addColumn('unit_price', fn ($row) => currency($row->unit_price))
            ->addColumn('value', fn ($row) => currency($row->value))
            ->rawColumns(['reference_no', 'product_name'])
            ->with($totals)
            ->make(true);
    }
}
