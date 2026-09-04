<?php

namespace App\Services\Concrete\Admin;

use App\Enums\ReferenceType;
use App\Models\GoodReceiptNote;
use App\Models\OpeningStock;
use App\Models\Production;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\StockTaking;
use App\Models\TransferNote;
use App\Models\WasteDamageExpiry;

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

            // All 3 loss note types reference the same WasteDamageExpiry
            // header - see LossType::toReferenceType().
            case ReferenceType::DAMAGE_NOTE:
            case ReferenceType::EXPIRY_NOTE:
            case ReferenceType::WASTAGE_NOTE:
                return WasteDamageExpiry::where('waste_damage_expiry_id', $reference_id)->value('reference_no') ?? $reference_id;

            // Both 'production' (finished-goods receipt) and 'consumption'
            // (raw-material draw-down) reference the same Production row -
            // see ProductionService::complete()/receiveOutput().
            case ReferenceType::PRODUCTION:
            case ReferenceType::CONSUMPTION:
                return Production::where('production_id', $reference_id)->value('production_no') ?? $reference_id;

            default:
                return $reference_id;
        }
    }

    /**
     * Best-effort admin edit URL for a stock ledger reference document.
     * Returns null when the reference type has no dedicated screen.
     */
    public function resolveUrl($reference_type, $reference_id): ?string
    {
        if (empty($reference_id)) {
            return null;
        }

        switch ($reference_type) {
            case ReferenceType::PURCHASE:
                return url('/admin/purchase/' . $reference_id . '/edit');
            case ReferenceType::GRN:
                return url('/admin/good-receipt-note/' . $reference_id . '/edit');
            case ReferenceType::PURCHASE_RETURN:
                return url('/admin/purchase-return/' . $reference_id . '/edit');
            case ReferenceType::OPENING_STOCK:
                return url('/admin/opening-stock/' . $reference_id . '/edit');
            case ReferenceType::STOCK_TAKING:
                return url('/admin/stock-taking/' . $reference_id . '/edit');
            case ReferenceType::STOCK_TRANSFER:
                return url('/admin/transfer-note/' . $reference_id . '/edit');
            case ReferenceType::DAMAGE_NOTE:
            case ReferenceType::EXPIRY_NOTE:
            case ReferenceType::WASTAGE_NOTE:
                return url('/admin/waste-damage-expiry/' . $reference_id . '/edit');
            case ReferenceType::PRODUCTION:
            case ReferenceType::CONSUMPTION:
                return url('/admin/production/edit/' . $reference_id);
            default:
                return null;
        }
    }
}
