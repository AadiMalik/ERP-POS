<?php

namespace App\Http\Controllers\Api\Offline;

use App\Http\Controllers\Controller;
use App\Models\ProductVariationStock;
use App\Services\Concrete\Admin\ProductVariationStockService;
use App\Traits\ResponseAPI;
use Illuminate\Http\Request;

class StockController extends Controller
{
    use ResponseAPI;

    protected $stock_service;

    public function __construct(ProductVariationStockService $stock_service)
    {
        $this->stock_service = $stock_service;
    }

    /**
     * One row per (variation, warehouse) - an explicit ?warehouse_id still
     * scopes to just that warehouse (legacy/manual override); otherwise this
     * returns every warehouse linked to the device's branch (branch_warehouses
     * pivot), not just the register's single warehouse, since a branch's
     * sellable stock is now the combination of all its linked warehouses.
     * The desktop app sums rows per variation_id itself when combining them
     * (see ProductVariationStockService::getAvailableStockForBranch() for
     * the same combine done server-side elsewhere).
     */
    public function levels(Request $request)
    {
        $device = $request->attributes->get('pos_device');
        $warehouse_id = $request->query('warehouse_id');

        $warehouse_ids = $warehouse_id
            ? [$warehouse_id]
            : $this->stock_service->getLinkedWarehouseIds($device->business_id, $device->branch_id);

        if (empty($warehouse_ids)) {
            return $this->error('No warehouse is linked to this device\'s branch.');
        }

        $levels = ProductVariationStock::where('business_id', $device->business_id)
            ->whereIn('warehouse_id', $warehouse_ids)
            ->where('is_deleted', 0)
            ->get()
            ->map(function ($row) {
                return [
                    'product_variation_stock_id' => $row->product_variation_stock_id,
                    'product_variation_id' => $row->product_variation_id,
                    'warehouse_id' => $row->warehouse_id,
                    'quantity' => (float) $row->quantity,
                    'date_updated' => $row->date_updated,
                ];
            });

        return $this->success('Stock levels.', $levels);
    }

    public function pushMovements(Request $request)
    {
        return $this->success('Stock movements are applied via order posting.', [
            'note' => 'Desktop POS stock deductions are reconciled when orders sync through OrderService::post().',
        ]);
    }
}
