<?php

namespace App\Services\Concrete\Admin;

use App\Enums\Filter;
use App\Enums\ReferenceType;
use App\Enums\SerialMovementEventType;
use App\Enums\SerialStatus;
use App\Enums\TransactionType;
use App\Models\ProductVariation;
use App\Models\ProductVariationSerialMovement;
use App\Models\ProductVariationSerialNumber;
use App\Models\ProductVariationStock;
use App\Models\ProductVariationStockTransaction;
use Exception;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;

/**
 * Owns the lifecycle of individual serialized units (product_variation_serial_numbers
 * + their product_variation_serial_movements audit trail) - the per-unit
 * sibling of ProductVariationStockService's batch handling. Every stock-moving
 * module (Purchase/GRN/OpeningStock, Transfer, Order/Order Return, Purchase
 * Return, Waste/Damage/Expiry) calls into this service alongside its existing
 * ProductVariationStockService/ProductVariationStock aggregate-quantity math
 * when the line's variation has track_serial_number = true. The aggregate
 * quantity stays the source of truth for stock levels; these rows are an
 * additional, validated ledger that must reconcile to the same count.
 * See resources/docs/developer/18-serial-number-tracking.md.
 */
class ProductVariationSerialService
{
    /**
     * Create N serial rows for a freshly received line (direct Purchase, GRN,
     * or Opening Stock). Throws if the variation isn't serial-tracked, if the
     * submitted count doesn't match the received quantity, if the payload has
     * internal duplicates, or if any serial number is already used anywhere
     * for this business (serial numbers are never reused).
     *
     * @return ProductVariationSerialNumber[]
     */
    public function receiveSerials(
        $business_id,
        $branch_id,
        $product_id,
        $product_variation_id,
        $warehouse_id,
        array $serial_numbers,
        $expected_count,
        $unit_cost,
        $source_reference_type,
        $source_reference_id,
        $source_detail_id = null
    ) {
        $variation = ProductVariation::find($product_variation_id);

        if (!$variation || !$variation->track_serial_number) {
            throw new Exception('This variation is not serial-number tracked.');
        }

        $serial_numbers = array_values(array_filter(array_map('trim', $serial_numbers), fn($s) => $s !== ''));

        if (count($serial_numbers) != (int) $expected_count) {
            throw new Exception('Enter exactly ' . (int) $expected_count . ' serial number(s) to match the received quantity (got ' . count($serial_numbers) . ').');
        }

        $duplicatesWithinPayload = array_diff_assoc($serial_numbers, array_unique($serial_numbers));
        if (!empty($duplicatesWithinPayload)) {
            throw new Exception('Duplicate serial number(s) in the entry: ' . implode(', ', array_unique($duplicatesWithinPayload)));
        }

        $existing = ProductVariationSerialNumber::where('business_id', $business_id)
            ->whereIn('serial_no', $serial_numbers)
            ->pluck('serial_no');

        if ($existing->isNotEmpty()) {
            throw new Exception('Serial number(s) already in use: ' . $existing->implode(', '));
        }

        $eventType = $source_reference_type === 'opening_stock'
            ? SerialMovementEventType::OPENING_STOCK
            : SerialMovementEventType::PURCHASED;

        $created = [];
        foreach ($serial_numbers as $serial_no) {
            $serial = ProductVariationSerialNumber::create([
                'product_variation_serial_number_id' => generateUuid(),
                'business_id' => $business_id,
                'branch_id' => $branch_id,
                'product_id' => $product_id,
                'product_variation_id' => $product_variation_id,
                'warehouse_id' => $warehouse_id,
                'serial_no' => $serial_no,
                'status' => SerialStatus::AVAILABLE,
                'avg_price' => $unit_cost,
                'source_reference_type' => $source_reference_type,
                'source_reference_id' => $source_reference_id,
                'source_detail_id' => $source_detail_id,
                'createdby_id' => Auth::id(),
                'date_created' => now(),
            ]);

            $this->logMovement($serial, $eventType, null, $warehouse_id, $source_reference_type ? $source_reference_type . '_detail' : null, $source_detail_id);

            $created[] = $serial;
        }

        return $created;
    }

    /**
     * Undo receiveSerials() for a reversed/voided Purchase, GRN, or Opening
     * Stock line - a true undo (hard delete, including the movement rows it
     * created), not a status change, so the serial numbers become reusable
     * again. Only allowed while every serial from that source is still
     * untouched (status = available); throws otherwise so a purchase that has
     * already had units sold/transferred/lost can't be silently unwound.
     */
    public function reverseReceivedSerials($source_reference_type, $source_reference_id, $source_detail_id = null)
    {
        $query = ProductVariationSerialNumber::where('source_reference_type', $source_reference_type)
            ->where('source_reference_id', $source_reference_id);

        if ($source_detail_id) {
            $query->where('source_detail_id', $source_detail_id);
        }

        $serials = $query->lockForUpdate()->get();

        if ($serials->isEmpty()) {
            return;
        }

        $moved = $serials->firstWhere('status', '!=', SerialStatus::AVAILABLE);
        if ($moved) {
            throw new Exception('Cannot reverse: serial number ' . $moved->serial_no . ' has already moved on (status: ' . $moved->status . ').');
        }

        $ids = $serials->pluck('product_variation_serial_number_id');
        ProductVariationSerialMovement::whereIn('product_variation_serial_number_id', $ids)->delete();
        ProductVariationSerialNumber::whereIn('product_variation_serial_number_id', $ids)->delete();
    }

    /**
     * Flip a set of available serials to in_transit for a Transfer Note send
     * step. Warehouse_id is left as the sending warehouse (still their "last
     * known location") until receiveTransfer() moves it - status is what
     * excludes them from availableSerialsFor() while in flight.
     */
    public function sendForTransfer(array $serial_ids, $transfer_note_detail_id, $destination_warehouse_id = null)
    {
        $serials = $this->lockAndAssert($serial_ids, SerialStatus::AVAILABLE);

        foreach ($serials as $serial) {
            $fromWarehouse = $serial->warehouse_id;

            $serial->update([
                'status' => SerialStatus::IN_TRANSIT,
                'current_transfer_note_detail_id' => $transfer_note_detail_id,
                'updatedby_id' => Auth::id(),
                'date_updated' => now(),
            ]);

            $this->logMovement($serial, SerialMovementEventType::TRANSFER_SENT, $fromWarehouse, $destination_warehouse_id, 'transfer_note_detail', $transfer_note_detail_id);
        }

        return $serials;
    }

    /**
     * Flip a set of in_transit serials to available in the destination
     * warehouse for a Transfer Note receive step (supports partial receiving
     * - callers pass only the subset of serials being received this call).
     */
    public function receiveTransfer(array $serial_ids, $destination_warehouse_id, $transfer_note_detail_id)
    {
        $serials = $this->lockAndAssert($serial_ids, SerialStatus::IN_TRANSIT);

        foreach ($serials as $serial) {
            $fromWarehouse = $serial->warehouse_id;

            $serial->update([
                'warehouse_id' => $destination_warehouse_id,
                'status' => SerialStatus::AVAILABLE,
                'current_transfer_note_detail_id' => null,
                'updatedby_id' => Auth::id(),
                'date_updated' => now(),
            ]);

            $this->logMovement($serial, SerialMovementEventType::TRANSFER_RECEIVED, $fromWarehouse, $destination_warehouse_id, 'transfer_note_detail', $transfer_note_detail_id);
        }

        return $serials;
    }

    /**
     * Reverse a send-for-transfer while the transfer is still in transit
     * (cancelled before any receiving) - flips serials back to available in
     * their original warehouse.
     */
    public function cancelTransferSend(array $serial_ids)
    {
        $serials = $this->lockAndAssert($serial_ids, SerialStatus::IN_TRANSIT);

        foreach ($serials as $serial) {
            $serial->update([
                'status' => SerialStatus::AVAILABLE,
                'current_transfer_note_detail_id' => null,
                'updatedby_id' => Auth::id(),
                'date_updated' => now(),
            ]);

            $this->logMovement($serial, SerialMovementEventType::TRANSFER_RECEIVED, $serial->warehouse_id, $serial->warehouse_id, 'transfer_note_detail_cancelled', null);
        }

        return $serials;
    }

    /**
     * Allocate specific available serials to a sale line - validates variation
     * and warehouse match before flipping to sold. Called from
     * OrderService::applyPostedEffects() for POS, admin-posted online/website/
     * mobile orders alike (they all funnel through the same posting code).
     */
    public function allocateForSale(array $serial_ids, $product_variation_id, $warehouse_id, $order_id, $order_detail_id, $customer_id = null, $warranty_months = null)
    {
        $serials = $this->lockAndAssert($serial_ids, SerialStatus::AVAILABLE);

        $warranty_expires_at = $warranty_months ? now()->addMonths((int) $warranty_months)->toDateString() : null;

        foreach ($serials as $serial) {
            if ($serial->product_variation_id != $product_variation_id) {
                throw new Exception('Serial number ' . $serial->serial_no . ' does not belong to the selected product.');
            }
            if ($serial->warehouse_id != $warehouse_id) {
                throw new Exception('Serial number ' . $serial->serial_no . ' is not in the selling warehouse.');
            }

            $serial->update([
                'status' => SerialStatus::SOLD,
                'current_order_id' => $order_id,
                'current_order_detail_id' => $order_detail_id,
                'current_customer_id' => $customer_id,
                'warranty_expires_at' => $warranty_expires_at,
                'updatedby_id' => Auth::id(),
                'date_updated' => now(),
            ]);

            $this->logMovement($serial, SerialMovementEventType::SOLD, $warehouse_id, null, 'order_detail', $order_detail_id);
        }

        return $serials;
    }

    /**
     * Reverse allocateForSale() for an order void/cancel - flips serials back
     * to available and clears the order/customer link.
     */
    public function releaseFromSale(array $serial_ids)
    {
        $serials = $this->lockAndAssert($serial_ids, SerialStatus::SOLD);

        foreach ($serials as $serial) {
            $orderDetailId = $serial->current_order_detail_id;

            $serial->update([
                'status' => SerialStatus::AVAILABLE,
                'current_order_id' => null,
                'current_order_detail_id' => null,
                'current_customer_id' => null,
                'warranty_expires_at' => null,
                'updatedby_id' => Auth::id(),
                'date_updated' => now(),
            ]);

            $this->logMovement($serial, SerialMovementEventType::SALE_RETURNED, null, $serial->warehouse_id, 'order_detail_void', $orderDetailId);
        }

        return $serials;
    }

    /**
     * Restock specific serials returned by a customer via Sales Return.
     */
    public function restockFromReturn(array $serial_ids, $order_return_detail_id)
    {
        $serials = $this->lockAndAssert($serial_ids, SerialStatus::SOLD);

        foreach ($serials as $serial) {
            $serial->update([
                'status' => SerialStatus::AVAILABLE,
                'current_order_id' => null,
                'current_order_detail_id' => null,
                'current_customer_id' => null,
                'warranty_expires_at' => null,
                'updatedby_id' => Auth::id(),
                'date_updated' => now(),
            ]);

            $this->logMovement($serial, SerialMovementEventType::SALE_RETURNED, null, $serial->warehouse_id, 'order_return_detail', $order_return_detail_id);
        }

        return $serials;
    }

    /**
     * Reverse restockFromReturn() when a Sales Return is un-approved - flips
     * serials back from available to sold, re-attached to the original
     * order/customer.
     */
    public function cancelSaleReturn(array $serial_ids, $order_id, $order_detail_id, $customer_id = null)
    {
        $serials = $this->lockAndAssert($serial_ids, SerialStatus::AVAILABLE);

        foreach ($serials as $serial) {
            $serial->update([
                'status' => SerialStatus::SOLD,
                'current_order_id' => $order_id,
                'current_order_detail_id' => $order_detail_id,
                'current_customer_id' => $customer_id,
                'updatedby_id' => Auth::id(),
                'date_updated' => now(),
            ]);

            $this->logMovement($serial, SerialMovementEventType::SOLD, $serial->warehouse_id, null, 'order_return_reversed', null);
        }

        return $serials;
    }

    /**
     * Remove specific unsold serials from sellable stock via Purchase Return.
     */
    public function returnToSupplier(array $serial_ids, $purchase_return_detail_id)
    {
        $serials = $this->lockAndAssert($serial_ids, SerialStatus::AVAILABLE);

        foreach ($serials as $serial) {
            $serial->update([
                'status' => SerialStatus::RETURNED_TO_SUPPLIER,
                'updatedby_id' => Auth::id(),
                'date_updated' => now(),
            ]);

            $this->logMovement($serial, SerialMovementEventType::PURCHASE_RETURNED, $serial->warehouse_id, null, 'purchase_return_detail', $purchase_return_detail_id);
        }

        return $serials;
    }

    /**
     * Reverse returnToSupplier() when a Purchase Return is un-approved -
     * flips serials back from returned_to_supplier to available.
     */
    public function cancelSupplierReturn(array $serial_ids)
    {
        $serials = $this->lockAndAssert($serial_ids, SerialStatus::RETURNED_TO_SUPPLIER);

        foreach ($serials as $serial) {
            $serial->update([
                'status' => SerialStatus::AVAILABLE,
                'updatedby_id' => Auth::id(),
                'date_updated' => now(),
            ]);

            $this->logMovement($serial, SerialMovementEventType::PURCHASE_RETURNED, null, $serial->warehouse_id, 'purchase_return_reversed', null);
        }

        return $serials;
    }

    /**
     * Remove specific serials from sellable stock via Waste/Damage/Expiry.
     * $loss_type is one of App\Enums\LossType's constants.
     */
    public function markLoss(array $serial_ids, $loss_type, $waste_damage_expiry_detail_id, $notes = null)
    {
        $serials = $this->lockAndAssert($serial_ids, SerialStatus::AVAILABLE);

        $statusMap = [
            'damaged' => SerialStatus::DAMAGED,
            'broken' => SerialStatus::DAMAGED,
            'expired' => SerialStatus::EXPIRED,
            'spoiled' => SerialStatus::EXPIRED,
        ];
        $eventMap = [
            'damaged' => SerialMovementEventType::DAMAGED,
            'broken' => SerialMovementEventType::DAMAGED,
            'expired' => SerialMovementEventType::EXPIRED,
            'spoiled' => SerialMovementEventType::EXPIRED,
        ];
        $status = $statusMap[$loss_type] ?? SerialStatus::WASTED;
        $eventType = $eventMap[$loss_type] ?? SerialMovementEventType::WASTED;

        foreach ($serials as $serial) {
            $serial->update([
                'status' => $status,
                'updatedby_id' => Auth::id(),
                'date_updated' => now(),
            ]);

            $this->logMovement($serial, $eventType, $serial->warehouse_id, null, 'waste_damage_expiry_detail', $waste_damage_expiry_detail_id, $notes);
        }

        return $serials;
    }

    /**
     * Reverse markLoss() when a Waste/Damage/Expiry note is un-approved -
     * flips serials back to available regardless of which loss status they
     * ended up in.
     */
    public function cancelLoss(array $serial_ids)
    {
        $serial_ids = array_values(array_unique($serial_ids));
        if (empty($serial_ids)) {
            return collect();
        }

        $serials = ProductVariationSerialNumber::whereIn('product_variation_serial_number_id', $serial_ids)
            ->lockForUpdate()
            ->get();

        $lossStatuses = [SerialStatus::DAMAGED, SerialStatus::WASTED, SerialStatus::EXPIRED];

        foreach ($serials as $serial) {
            if (!in_array($serial->status, $lossStatuses, true)) {
                throw new Exception('Serial number ' . $serial->serial_no . ' is not currently marked as a loss (status: ' . $serial->status . ').');
            }

            $serial->update([
                'status' => SerialStatus::AVAILABLE,
                'updatedby_id' => Auth::id(),
                'date_updated' => now(),
            ]);

            $this->logMovement($serial, SerialMovementEventType::ADDED_MANUALLY, null, $serial->warehouse_id, 'waste_damage_expiry_reversed', null);
        }

        return $serials;
    }

    /**
     * Lightweight repair/warranty log actions surfaced on the Serial Number
     * Details page - status + audit trail only, no separate workflow engine.
     */
    public function sendForRepair($serial_id, $notes = null)
    {
        $serial = $this->lockAndAssert([$serial_id], SerialStatus::AVAILABLE)->first();

        $serial->update(['status' => SerialStatus::UNDER_REPAIR, 'updatedby_id' => Auth::id(), 'date_updated' => now()]);
        $this->logMovement($serial, SerialMovementEventType::SENT_FOR_REPAIR, $serial->warehouse_id, $serial->warehouse_id, null, null, $notes);

        return $serial;
    }

    public function returnFromRepair($serial_id, $notes = null)
    {
        $serial = $this->lockAndAssert([$serial_id], SerialStatus::UNDER_REPAIR)->first();

        $serial->update(['status' => SerialStatus::AVAILABLE, 'updatedby_id' => Auth::id(), 'date_updated' => now()]);
        $this->logMovement($serial, SerialMovementEventType::RETURNED_FROM_REPAIR, $serial->warehouse_id, $serial->warehouse_id, null, null, $notes);

        return $serial;
    }

    /**
     * Retire one serial and, if it was sold, hand the same order the
     * customer's replacement unit - two movement log entries, one per serial,
     * cross-referencing each other via notes.
     */
    public function replaceSerial($old_serial_id, $new_serial_id, $notes = null)
    {
        $old = ProductVariationSerialNumber::lockForUpdate()->findOrFail($old_serial_id);
        $new = ProductVariationSerialNumber::lockForUpdate()->findOrFail($new_serial_id);

        if ($new->status !== SerialStatus::AVAILABLE) {
            throw new Exception('Replacement serial number ' . $new->serial_no . ' is not available.');
        }
        if ($new->product_variation_id != $old->product_variation_id) {
            throw new Exception('Replacement serial number must be the same product/variation.');
        }

        $wasSold = $old->status === SerialStatus::SOLD;

        $old->update([
            'status' => SerialStatus::REPLACED,
            'updatedby_id' => Auth::id(),
            'date_updated' => now(),
        ]);
        $this->logMovement($old, SerialMovementEventType::REPLACED, $old->warehouse_id, null, 'product_variation_serial_number', $new->product_variation_serial_number_id, $notes);

        if ($wasSold) {
            $new->update([
                'status' => SerialStatus::SOLD,
                'warehouse_id' => $old->warehouse_id,
                'current_order_id' => $old->current_order_id,
                'current_order_detail_id' => $old->current_order_detail_id,
                'current_customer_id' => $old->current_customer_id,
                'warranty_expires_at' => $old->warranty_expires_at,
                'updatedby_id' => Auth::id(),
                'date_updated' => now(),
            ]);
            $this->logMovement($new, SerialMovementEventType::REPLACED, null, $new->warehouse_id, 'product_variation_serial_number', $old->product_variation_serial_number_id, $notes);
        }

        return [$old, $new];
    }

    /**
     * Serials a picker UI (Purchase Return, Transfer send, Waste, POS) can
     * choose from right now.
     */
    public function availableSerialsFor($product_variation_id, $warehouse_id, $search = null, $business_id = null, $limit = 50)
    {
        $query = ProductVariationSerialNumber::where('product_variation_id', $product_variation_id)
            ->where('status', SerialStatus::AVAILABLE)
            ->where('is_deleted', 0);

        if ($warehouse_id) {
            $query->where('warehouse_id', $warehouse_id);
        }
        if ($business_id) {
            $query->where('business_id', $business_id);
        }
        if ($search) {
            $query->where('serial_no', 'like', '%' . $search . '%');
        }

        return $query->orderBy('date_created')->limit($limit)->get();
    }

    /**
     * Serials currently sold under a specific order line - feeds the Sales
     * Return picker.
     */
    public function serialsCurrentlySoldUnder($order_detail_id)
    {
        return ProductVariationSerialNumber::where('current_order_detail_id', $order_detail_id)
            ->where('status', SerialStatus::SOLD)
            ->get();
    }

    /**
     * Serials sourced from a specific purchase/GRN line - feeds the Purchase
     * Return picker.
     */
    public function availableSerialsFromSource($source_detail_id)
    {
        return ProductVariationSerialNumber::where('source_detail_id', $source_detail_id)
            ->where('status', SerialStatus::AVAILABLE)
            ->get();
    }

    /**
     * DataTables listing for the Serial Number search screen.
     */
    public function getData($obj)
    {
        $wh = [];
        $orderBy = Filter::ORDERBY;

        if (isset($obj['orderBy']) && $obj['orderBy'] != 0 && $obj['orderBy'] != '') {
            $orderBy = $obj['orderBy'];
        }
        if (!empty($obj['business_id'])) {
            $wh[] = ['business_id', $obj['business_id']];
        }
        if (!empty($obj['product_id'])) {
            $wh[] = ['product_id', $obj['product_id']];
        }
        if (!empty($obj['product_variation_id'])) {
            $wh[] = ['product_variation_id', $obj['product_variation_id']];
        }
        if (!empty($obj['warehouse_id'])) {
            $wh[] = ['warehouse_id', $obj['warehouse_id']];
        }
        if (!empty($obj['status'])) {
            $wh[] = ['status', $obj['status']];
        }
        if (!empty($obj['current_customer_id'])) {
            $wh[] = ['current_customer_id', $obj['current_customer_id']];
        }

        $query = ProductVariationSerialNumber::where($wh)
            ->where('is_deleted', 0)
            ->with(['product', 'productVariation', 'warehouse', 'customer'])
            ->orderBy('date_created', $orderBy);

        return DataTables::of($query)
            ->addColumn('product', fn($item) => $item->product->name ?? '-')
            ->addColumn('variation', fn($item) => $item->productVariation->name ?? '-')
            ->addColumn('warehouse', fn($item) => $item->warehouse->name ?? '-')
            ->addColumn('customer', fn($item) => $item->customer->name ?? '-')
            ->addColumn('status_badge', function ($item) {
                $map = [
                    SerialStatus::AVAILABLE => 'bg-success',
                    SerialStatus::SOLD => 'bg-primary',
                    SerialStatus::IN_TRANSIT => 'bg-info',
                    SerialStatus::UNDER_REPAIR => 'bg-warning',
                ];
                $class = $map[$item->status] ?? 'bg-secondary';
                $label = SerialStatus::getOptions()[$item->status] ?? $item->status;
                return '<span class="badge ' . $class . '">' . $label . '</span>';
            })
            ->addColumn('action', function ($item) {
                return '<a class="btn btn-icon btn-outline-primary" href="' . url('admin/serial-number/' . $item->product_variation_serial_number_id) . '" title="View Details"><i class="fa fa-eye"></i></a>';
            })
            ->rawColumns(['product', 'variation', 'warehouse', 'customer', 'status_badge', 'action'])
            ->make(true);
    }

    /**
     * Everything the Serial Number Details page shows: product/variation,
     * current status/location, purchase info, sale info + customer,
     * warranty, and the full movement timeline.
     */
    public function getFullDetails($product_variation_serial_number_id)
    {
        $serial = ProductVariationSerialNumber::with([
            'product', 'productVariation', 'warehouse', 'branch', 'business',
            'order', 'orderDetail', 'customer',
            'movements.fromWarehouse', 'movements.toWarehouse', 'movements.createdby',
        ])->findOrFail($product_variation_serial_number_id);

        $resolver = app(ReferenceResolverService::class);

        return [
            'product_variation_serial_number_id' => $serial->product_variation_serial_number_id,
            'serial_no' => $serial->serial_no,
            'status' => $serial->status,
            'status_label' => SerialStatus::getOptions()[$serial->status] ?? $serial->status,
            'product_id' => $serial->product_id,
            'product_name' => $serial->product->name ?? '',
            'product_variation_id' => $serial->product_variation_id,
            'variation_name' => $serial->productVariation->name ?? '',
            'warehouse_id' => $serial->warehouse_id,
            'warehouse_name' => $serial->warehouse->name ?? '-',
            'avg_price' => $serial->avg_price,
            'source_reference_type' => $serial->source_reference_type,
            'source_doc_no' => $serial->source_reference_type ? $resolver->resolveDocNo($serial->source_reference_type, $serial->source_reference_id) : '-',
            'current_order_id' => $serial->current_order_id,
            'current_order_daily_id' => $serial->order->daily_order_id ?? null,
            'current_customer_id' => $serial->current_customer_id,
            'current_customer_name' => $serial->customer->name ?? null,
            'warranty_expires_at' => $serial->warranty_expires_at,
            'date_created' => $serial->date_created,
            'movements' => $serial->movements->map(fn($m) => [
                'event_type' => $m->event_type,
                'event_label' => SerialMovementEventType::getOptions()[$m->event_type] ?? $m->event_type,
                'from_warehouse_name' => $m->fromWarehouse->name ?? null,
                'to_warehouse_name' => $m->toWarehouse->name ?? null,
                'reference_type' => $m->reference_type,
                'notes' => $m->notes,
                'createdby_name' => $m->createdby->name ?? '',
                'date_created' => $m->date_created,
            ])->values(),
        ];
    }

    /**
     * Records a physically-found unit that was never in the system (e.g. a
     * Stock Taking discrepancy resolved manually) - creates the serial row
     * AND increments the aggregate stock the same way a receipt would, so
     * the serial ledger and aggregate quantity stay reconciled. Distinct
     * from receiveSerials() (which expects an existing purchase/GRN/opening
     * stock line) since this has no such source document.
     */
    public function addFoundUnit($business_id, $branch_id, $product_id, $product_variation_id, $warehouse_id, $serial_no, $unit_cost = null, $notes = null)
    {
        $variation = ProductVariation::find($product_variation_id);

        if (!$variation || !$variation->track_serial_number) {
            throw new Exception('This variation is not serial-number tracked.');
        }

        $serial_no = trim($serial_no);

        if ($serial_no === '') {
            throw new Exception('Serial number is required.');
        }

        if (ProductVariationSerialNumber::where('business_id', $business_id)->where('serial_no', $serial_no)->exists()) {
            throw new Exception('Serial number ' . $serial_no . ' is already in use.');
        }

        $stock = ProductVariationStock::where('business_id', $business_id)
            ->where('warehouse_id', $warehouse_id)
            ->where('product_id', $product_id)
            ->where('product_variation_id', $product_variation_id)
            ->lockForUpdate()
            ->first();

        $existing_qty = $stock->quantity ?? 0;
        $existing_avg = $stock->avg_price ?? 0;
        $cost = $unit_cost !== null ? (float) $unit_cost : $existing_avg;
        $new_qty = $existing_qty + 1;
        $new_avg = $new_qty > 0 ? ((($existing_qty * $existing_avg) + $cost) / $new_qty) : 0;

        if ($stock) {
            $stock->update(['quantity' => $new_qty, 'avg_price' => $new_avg]);
        } else {
            $stock = ProductVariationStock::create([
                'product_variation_stock_id' => generateUuid(),
                'business_id' => $business_id,
                'warehouse_id' => $warehouse_id,
                'product_id' => $product_id,
                'product_variation_id' => $product_variation_id,
                'quantity' => $new_qty,
                'avg_price' => $new_avg,
                'status' => 'active',
                'createdby_id' => Auth::id(),
                'date_created' => now(),
            ]);
        }

        $serial = ProductVariationSerialNumber::create([
            'product_variation_serial_number_id' => generateUuid(),
            'business_id' => $business_id,
            'branch_id' => $branch_id,
            'product_id' => $product_id,
            'product_variation_id' => $product_variation_id,
            'warehouse_id' => $warehouse_id,
            'serial_no' => $serial_no,
            'status' => SerialStatus::AVAILABLE,
            'avg_price' => $cost,
            'source_reference_type' => null,
            'createdby_id' => Auth::id(),
            'date_created' => now(),
        ]);

        ProductVariationStockTransaction::create([
            'product_variation_stock_transaction_id' => generateUuid(),
            'transaction_date' => now(),
            'transaction_type' => TransactionType::ADJUSTMENT,
            'business_id' => $business_id,
            'product_id' => $product_id,
            'product_variation_id' => $product_variation_id,
            'warehouse_id' => $warehouse_id,
            'quantity' => 1,
            'base_quantity' => 1,
            'unit_price' => $cost,
            'total_price' => $cost,
            'quantity_after' => $new_qty,
            'avg_price_after' => $new_avg,
            'reference_id' => $serial->product_variation_serial_number_id,
            'reference_type' => ReferenceType::MANUAL,
            'remarks' => 'Found unit added manually via Serial Number screen' . ($notes ? ' - ' . $notes : ''),
            'createdby_id' => Auth::id(),
            'date_created' => now(),
        ]);

        $this->logMovement($serial, SerialMovementEventType::ADDED_MANUALLY, null, $warehouse_id, null, null, $notes);

        return $serial;
    }

    /**
     * Lock + fetch a set of serials by id and assert they're all in
     * $expected_status, throwing with the offending serial number named.
     *
     * @return \Illuminate\Support\Collection<ProductVariationSerialNumber>
     */
    protected function lockAndAssert(array $serial_ids, $expected_status)
    {
        $serial_ids = array_values(array_unique($serial_ids));

        if (empty($serial_ids)) {
            throw new Exception('No serial numbers were selected.');
        }

        $serials = ProductVariationSerialNumber::whereIn('product_variation_serial_number_id', $serial_ids)
            ->lockForUpdate()
            ->get();

        if ($serials->count() != count($serial_ids)) {
            throw new Exception('One or more selected serial numbers could not be found.');
        }

        $bad = $serials->firstWhere('status', '!=', $expected_status);
        if ($bad) {
            throw new Exception('Serial number ' . $bad->serial_no . ' is not ' . str_replace('_', ' ', $expected_status) . ' (current status: ' . $bad->status . ').');
        }

        return $serials;
    }

    protected function logMovement(ProductVariationSerialNumber $serial, $event_type, $from_warehouse_id, $to_warehouse_id, $reference_type = null, $reference_id = null, $notes = null)
    {
        ProductVariationSerialMovement::create([
            'product_variation_serial_movement_id' => generateUuid(),
            'product_variation_serial_number_id' => $serial->product_variation_serial_number_id,
            'business_id' => $serial->business_id,
            'branch_id' => $serial->branch_id,
            'event_type' => $event_type,
            'from_warehouse_id' => $from_warehouse_id,
            'to_warehouse_id' => $to_warehouse_id,
            'reference_type' => $reference_type,
            'reference_id' => $reference_id,
            'notes' => $notes,
            'createdby_id' => Auth::id(),
            'date_created' => now(),
        ]);
    }
}
