<?php

namespace App\Http\Controllers\Api\Offline;

use App\Http\Controllers\Controller;
use App\Models\ProductVariationStock;
use App\Traits\ResponseAPI;
use Illuminate\Http\Request;

class StockController extends Controller
{
    use ResponseAPI;

    public function levels(Request $request)
    {
        $device = $request->attributes->get('pos_device');
        $warehouse_id = $request->query('warehouse_id') ?: optional($device->register)->warehouse_id;

        if (empty($warehouse_id)) {
            return $this->error('warehouse_id is required.');
        }

        $levels = ProductVariationStock::where('business_id', $device->business_id)
            ->where('warehouse_id', $warehouse_id)
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
