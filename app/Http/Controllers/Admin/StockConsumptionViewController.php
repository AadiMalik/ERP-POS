<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Message;
use App\Enums\RoleNames;
use App\Http\Controllers\Controller;
use App\Models\ProductVariationStockTransaction;
use App\Traits\ResponseAPI;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Single reusable "Stock Consumption Details" endpoint - shows exactly
 * which warehouse/product/variation/batch a transaction (Order, Purchase,
 * ...) consumed or received stock against, by its reference_type +
 * reference_id (App\Enums\ReferenceType), the same linkage every stock
 * movement already carries.
 */
class StockConsumptionViewController extends Controller
{
    use ResponseAPI;

    public function show(Request $request)
    {
        $request->validate([
            'reference_type' => 'required|string',
            'reference_id' => 'required|string',
        ]);

        $query = ProductVariationStockTransaction::with(['product', 'productVariation', 'warehouse', 'unit', 'productVariationBatch'])
            ->where('reference_type', $request->reference_type)
            ->where('reference_id', $request->reference_id)
            ->where('is_deleted', 0);

        if (getRoleName() !== RoleNames::SUPERADMIN) {
            $query->where('business_id', Auth::user()->business_id);
        }

        $rows = $query->orderBy('transaction_date')->get()->map(function ($row) {
            return [
                'warehouse'         => $row->warehouse->name ?? '',
                'product'           => $row->product->name ?? '',
                'variation'         => $row->productVariation->name ?? '',
                'batch'             => $row->productVariationBatch->batch_no ?? '',
                'unit'              => $row->unit->name ?? '',
                'quantity'          => $row->quantity,
                'base_quantity'     => $row->base_quantity,
                'conversion_factor' => $row->conversion_factor,
                'unit_price'        => $row->unit_price,
                'total_price'       => $row->total_price,
                'transaction_type'  => $row->transaction_type,
                'transaction_date'  => localDate($row->transaction_date),
                'reference'         => $row->product_variation_stock_transaction_id,
            ];
        });

        if ($rows->isEmpty()) {
            return $this->error('No stock consumption found for this record.');
        }

        return $this->success(Message::SUCCESS, $rows);
    }
}
