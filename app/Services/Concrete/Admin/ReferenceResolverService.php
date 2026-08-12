<?php

namespace App\Services\Concrete\Admin;

use App\Enums\ReferenceType;
use App\Models\GoodReceiptNote;
use App\Models\OpeningStock;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\StockTaking;
use App\Models\TransferNote;

/**
 * Best-effort resolution of a human-readable document number for a stock
 * transaction's source (product_variation_stock_transactions.reference_type
 * / reference_id). Shared by ProductVariationStockService's Stock History
 * view and the Reports\StockLedgerReportService listing so both always
 * resolve the same set of reference types the same way.
 */
class ReferenceResolverService
{
    public function resolveDocNo($reference_type, $reference_id)
    {
        if (empty($reference_id)) {
            return '-';
        }

        switch ($reference_type) {
            case ReferenceType::PURCHASE:
                return Purchase::where('purchase_id', $reference_id)->value('purchase_no') ?? $reference_id;

            case ReferenceType::GRN:
                return GoodReceiptNote::where('good_receipt_note_id', $reference_id)->value('good_receipt_note_no') ?? $reference_id;

            case ReferenceType::PURCHASE_RETURN:
                return PurchaseReturn::where('purchase_return_id', $reference_id)->value('purchase_return_no') ?? $reference_id;

            case ReferenceType::OPENING_STOCK:
                return OpeningStock::where('opening_stock_id', $reference_id)->value('opening_stock_no') ?? $reference_id;

            case ReferenceType::STOCK_TAKING:
                return StockTaking::where('stock_taking_id', $reference_id)->value('stock_taking_no') ?? $reference_id;

            case ReferenceType::STOCK_TRANSFER:
                return TransferNote::where('transfer_note_id', $reference_id)->value('transfer_note_no') ?? $reference_id;

            default:
                return $reference_id;
        }
    }
}
