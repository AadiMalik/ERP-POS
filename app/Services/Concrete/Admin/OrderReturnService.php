<?php

namespace App\Services\Concrete\Admin;

use App\Enums\Filter;
use App\Enums\JournalSourceTypes;
use App\Enums\ReferenceType;
use App\Enums\RoleNames;
use App\Enums\Status;
use App\Enums\TransactionType;
use App\Models\AccountingSetting;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\JournalEntryDetail;
use App\Models\Order;
use App\Models\OrderReturn;
use App\Models\OrderReturnDetail;
use App\Models\OrderStatusHistory;
use App\Models\PaymentMethod;
use App\Models\ProductVariationStock;
use App\Models\ProductVariationStockTransaction;
use App\Repository\Repository;
use App\Traits\Auditable;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

/**
 * Sale/POS Return flow for posted (completed) orders - mirrors
 * PurchaseReturnService's pending/approved lifecycle and apply/reverse
 * posting pattern exactly: save() only ever writes order_returns/
 * order_return_details (no stock or accounting side-effects); status()
 * triggers applyOrderReturnPosting()/reverseOrderReturnPosting() on the
 * pending<->approved transition, which is the only place stock and the
 * general ledger are touched, mirroring OrderService::post()/void() for the
 * exact accounts/legs being reversed. A return never mutates the original
 * order's own line items/payments - it only reads them for pricing/cost
 * snapshots and, once every line on the order has been fully returned,
 * transitions the order's own status to 'returned' (reverted back to
 * 'posted' if that return is later un-approved).
 */
class OrderReturnService
{
    use Auditable;

    protected $model_order_return;
    protected $model_order_return_details;
    protected $with = [
        'business',
        'branch',
        'warehouse',
        'customer',
        'order',
        'refundPaymentMethod',
        'orderReturnDetails',
        'orderReturnDetails.product',
        'orderReturnDetails.productVariation',
        'orderReturnDetails.unit',
    ];

    public function __construct()
    {
        $this->model_order_return = new Repository(new OrderReturn());
        $this->model_order_return_details = new Repository(new OrderReturnDetail());
    }

    /**
     * Posted (completed) orders for this business - a Return can only ever
     * be raised against a fully posted sale, capped to the most recent 200
     * so the Create Return dropdown stays responsive on a business with a
     * long sales history.
     */
    public function getEligibleOrders($business_id = null)
    {
        $query = Order::with(['user', 'warehouse'])
            ->where('status', 'posted')
            ->where('is_deleted', 0);

        if (!empty($business_id)) {
            $query->where('business_id', $business_id);
        } else {
            $query->where('business_id', Auth::user()->business_id);
        }

        return $query->orderByDesc('sale_date')->limit(200)->get();
    }

    public function getData($obj)
    {
        $wh = [];
        $orderBy = Filter::ORDERBY;

        if (isset($obj['orderBy']) && $obj['orderBy'] != 0 && $obj['orderBy'] != "") {
            $orderBy = $obj['orderBy'];
        }
        if (isset($obj['business_id']) && $obj['business_id'] != 0 && $obj['business_id'] != "") {
            $wh[] = ['business_id', $obj['business_id']];
        }
        if (isset($obj['branch_id']) && $obj['branch_id'] != 0 && $obj['branch_id'] != "") {
            $wh[] = ['branch_id', $obj['branch_id']];
        }
        if (isset($obj['customer_id']) && $obj['customer_id'] != 0 && $obj['customer_id'] != "") {
            $wh[] = ['customer_id', $obj['customer_id']];
        }
        if (isset($obj['warehouse_id']) && $obj['warehouse_id'] != 0 && $obj['warehouse_id'] != "") {
            $wh[] = ['warehouse_id', $obj['warehouse_id']];
        }
        if (isset($obj['status']) && $obj['status'] != 0 && $obj['status'] != "") {
            $wh[] = ['status', $obj['status']];
        }
        if (!empty($obj['start_date'])) {
            $wh[] = ['order_return_date', '>=', Carbon::parse($obj['start_date'])->startOfDay()];
        }
        if (!empty($obj['end_date'])) {
            $wh[] = ['order_return_date', '<=', Carbon::parse($obj['end_date'])->endOfDay()];
        }

        $allow_roles = [
            RoleNames::SUPERADMIN,
            RoleNames::BUSINESSADMIN
        ];

        $datatable = $this->model_order_return->getModel()::with($this->with)
            ->withCount('orderReturnDetails as total_products')
            ->where($wh)
            ->where('is_deleted', 0)
            ->orderBy('order_return_date', $orderBy);
        $datatable = applyRoleScope($datatable, $allow_roles);

        return DataTables::of($datatable)
            ->addColumn('order_return_date', function ($item) {
                return !empty($item->order_return_date)
                    ? localDate($item->order_return_date)
                    : 'N/A';
            })
            ->addColumn('order_no', function ($item) {
                return $item->order->daily_order_id ?? '';
            })
            ->addColumn('customer', function ($item) {
                return $item->customer->name ?? '';
            })
            ->addColumn('warehouse', function ($item) {
                return $item->warehouse->name ?? '';
            })
            ->addColumn('business', function ($item) {
                return $item->business->name ?? '';
            })
            ->addColumn('branch', function ($item) {
                return $item->branch->name ?? '';
            })
            ->addColumn('total_products', function ($item) {
                return decimal($item->total_products ?? 0);
            })
            ->addColumn('total', function ($item) {
                return currency($item->total ?? 0);
            })
            ->addColumn('status', function ($item) {

                $statuses = [
                    Status::PENDING   => ucfirst(Status::PENDING),
                    Status::APPROVED  => ucfirst(Status::APPROVED),
                    Status::CANCELLED => ucfirst(Status::CANCELLED),
                ];

                $html = "<select class='form-select form-select-sm change-status'
                data-id='{$item->order_return_id}'>";

                foreach ($statuses as $value => $label) {
                    $selected = $item->status == $value ? 'selected' : '';
                    $html .= "<option value='{$value}' {$selected}>{$label}</option>";
                }

                $html .= "</select>";

                return $html;
            })
            ->addColumn('action', function ($item) {

                $editButton = $item->status === Status::PENDING
                    ? "<a class='btn btn-icon btn-outline-primary mr-2'
                        href='" . route('order-return.edit', $item->order_return_id) . "'
                        title='Edit'>
                        <i class='fa fa-pencil'></i>
                        </a>"
                    : "<button type='button' class='btn btn-icon btn-outline-primary mr-2' disabled
                        title='Only pending returns can be edited'>
                        <i class='fa fa-pencil'></i>
                        </button>";

                $viewJvButton = $item->status === Status::APPROVED
                    ? "<button type='button' class='btn btn-icon btn-outline-dark mr-2 view-jv-btn'
                        data-source-type='" . JournalSourceTypes::SALE_RETURN . "' data-source-id='{$item->order_return_id}' title='View JV'>
                        <i class='fa fa-book'></i>
                        </button>"
                    : '';

                $printButton = "<a class='btn btn-icon btn-outline-secondary mr-2' target='_blank'
                    href='" . route('order-return.print', $item->order_return_id) . "' title='Print'>
                    <i class='fa fa-print'></i>
                    </a>";

                $deleteButton = $item->status !== Status::CANCELLED
                    ? "<a class='btn btn-icon btn-outline-danger'
                    id='deleteOrderReturn'
                    data-id='{$item->order_return_id}'>
                    <i class='fa fa-trash'></i>
                    </a>"
                    : '';

                return $editButton . $viewJvButton . $printButton . $deleteButton;
            })
            ->rawColumns(['order_return_date', 'order_no', 'business', 'branch', 'warehouse', 'customer', 'total_products', 'total', 'status', 'action'])
            ->make(true);
    }

    /**
     * Sum of return_quantity across every approved Order Return line for
     * this order line - only approved (posted) returns count against the
     * returnable balance, mirroring PurchaseReturnService's equivalent.
     */
    protected function getAlreadyReturnedQuantity($order_detail_id)
    {
        return (float) OrderReturnDetail::query()
            ->join('order_returns', 'order_returns.order_return_id', '=', 'order_return_details.order_return_id')
            ->where('order_return_details.order_detail_id', $order_detail_id)
            ->where('order_returns.status', Status::APPROVED)
            ->where('order_returns.is_deleted', 0)
            ->sum('order_return_details.return_quantity');
    }

    /**
     * Fresh returnable lines for a posted order, used to seed a new Order
     * Return's product rows (Create Return screen and the sourceLines AJAX
     * endpoint it calls when an order is picked).
     */
    public function getSourceLines($order_id)
    {
        $order = Order::with([
            'user',
            'warehouse',
            'details',
            'details.product',
            'details.productVariation',
            'details.unit',
        ])->findOrFail($order_id);

        if ($order->status !== 'posted') {
            throw new Exception('This order is not eligible for a return. Only a posted (completed) order can be returned.');
        }

        $lines = [];

        foreach ($order->details as $detail) {
            $ordered_quantity = (float) ($detail->quantity ?? 0);
            $already_returned = $this->getAlreadyReturnedQuantity($detail->order_detail_id);
            $returnable = $ordered_quantity - $already_returned;

            if ($returnable <= 0) {
                continue;
            }

            $lines[] = [
                'order_detail_id'                      => $detail->order_detail_id,
                'product_id'                            => $detail->product_id,
                'product_name'                          => $detail->product->name ?? '',
                'product_variation_id'                  => $detail->product_variation_id,
                'product_variation_name'                => $detail->productVariation->name ?? '',
                'product_variation_unit_conversion_id'  => $detail->product_variation_unit_conversion_id,
                'ordered_quantity'                      => $ordered_quantity,
                'already_returned_quantity'             => $already_returned,
                'returnable_quantity'                   => $returnable,
                'unit_id'                                => $detail->unit_id,
                'unit_name'                              => $detail->unit->name ?? 'N/A',
                'conversion_factor'                      => $detail->conversion_factor,
                'unit_price'                             => $detail->unit_price,
                'discount'                               => $detail->discount ?? 0,
                'tax'                                    => $detail->tax ?? 0,
            ];
        }

        return [
            'header' => [
                'order_id'        => $order->order_id,
                'daily_order_id'  => $order->daily_order_id,
                'customer_id'     => $order->user_id,
                'customer_name'   => $order->user->name ?? '',
                'warehouse_id'    => $order->warehouse_id,
                'warehouse_name'  => $order->warehouse->name ?? '',
                'business_id'     => $order->business_id,
                'branch_id'       => $order->branch_id,
            ],
            'lines' => $lines,
        ];
    }

    public function save($obj)
    {
        DB::beginTransaction();

        try {
            $order = Order::with(['details.product'])->find($obj['order_id'] ?? null);

            if (!$order) {
                throw new Exception('The selected order was not found.');
            }

            if ($order->status !== 'posted') {
                throw new Exception('Only a posted (completed) order can be returned.');
            }

            $business_id = $order->business_id;
            $branch_id = $order->branch_id;
            $warehouse_id = $order->warehouse_id;
            $customer_id = $order->user_id;

            //====================================
            // Update
            //====================================

            if (!empty($obj['order_return_id'])) {

                $order_return = $this->model_order_return->getModel()::findOrFail($obj['order_return_id']);

                if ($order_return->status !== Status::PENDING) {
                    throw new Exception('Only pending order returns can be updated.');
                }

                $order_return->update([
                    'business_id'              => $business_id,
                    'branch_id'                => $branch_id,
                    'warehouse_id'             => $warehouse_id,
                    'customer_id'              => $customer_id,
                    'order_id'                 => $order->order_id,
                    'order_return_date'        => $obj['order_return_date'],
                    'refund_payment_method_id' => $obj['refund_payment_method_id'] ?? null,
                    'reason'                   => $obj['reason'] ?? null,
                    'description'              => $obj['description'] ?? null,
                    'updatedby_id'             => Auth::id(),
                    'date_updated'             => now(),
                ]);

                $this->model_order_return_details->getModel()::where('order_return_id', $order_return->order_return_id)
                    ->delete();
            }

            //====================================
            // Create
            //====================================

            else {

                $order_return = $this->model_order_return->create([
                    'order_return_id'          => generateUuid(),
                    'business_id'              => $business_id,
                    'branch_id'                => $branch_id,
                    'warehouse_id'             => $warehouse_id,
                    'customer_id'              => $customer_id,
                    'order_id'                 => $order->order_id,
                    'order_return_no'          => $obj['order_return_no'],
                    'order_return_date'        => $obj['order_return_date'],
                    'refund_payment_method_id' => $obj['refund_payment_method_id'] ?? null,
                    'reason'                   => $obj['reason'] ?? null,
                    'description'              => $obj['description'] ?? null,
                    'status'                   => Status::PENDING,
                    'createdby_id'             => Auth::id(),
                    'date_created'             => now(),
                ]);
            }

            //====================================
            // Save Items
            //====================================

            $subtotal = 0;
            $discount_amount_total = 0;
            $tax_amount_total = 0;
            $total = 0;
            $has_quantity = false;

            foreach ($obj['products'] as $product) {

                $detail = $order->details->firstWhere('order_detail_id', $product['order_detail_id'] ?? null);

                if (!$detail) {
                    throw new Exception('One of the selected lines does not belong to the chosen order.');
                }

                $ordered_quantity = (float) ($detail->quantity ?? 0);
                $already_returned = $this->getAlreadyReturnedQuantity($detail->order_detail_id);
                $conversion_factor = $detail->conversion_factor > 0 ? $detail->conversion_factor : 1;
                $unit_price = (float) $detail->unit_price;
                $discount_percent = (float) ($detail->discount ?? 0);
                $tax_percent = (float) ($detail->tax ?? 0);
                $product_name = $detail->product->name ?? 'a product';

                $return_quantity = (float) ($product['return_quantity'] ?? 0);

                if ($return_quantity < 0) {
                    throw new Exception('Return quantity cannot be negative.');
                }

                $returnable = $ordered_quantity - $already_returned;

                if ($return_quantity > $returnable) {
                    throw new Exception('Return quantity for "' . $product_name . '" exceeds the returnable quantity.');
                }

                if ($return_quantity > 0) {
                    $has_quantity = true;
                }

                $base_quantity = $return_quantity * $conversion_factor;

                $line_subtotal = $base_quantity * $unit_price;
                $line_discount_amount = round($line_subtotal * $discount_percent / 100, 3);
                $taxable = $line_subtotal - $line_discount_amount;
                $line_tax_amount = round($taxable * $tax_percent / 100, 3);
                $line_total = $taxable + $line_tax_amount;

                $subtotal += $line_subtotal;
                $discount_amount_total += $line_discount_amount;
                $tax_amount_total += $line_tax_amount;
                $total += $line_total;

                $this->model_order_return_details->create([
                    'order_return_detail_id'                => generateUuid(),
                    'order_return_id'                       => $order_return->order_return_id,
                    'order_id'                                => $order->order_id,
                    'order_detail_id'                         => $detail->order_detail_id,
                    'product_id'                              => $detail->product_id,
                    'product_variation_id'                    => $detail->product_variation_id,
                    'product_variation_unit_conversion_id'    => $detail->product_variation_unit_conversion_id,
                    'unit_id'                                  => $detail->unit_id,
                    'ordered_quantity'                        => $ordered_quantity,
                    'already_returned_quantity'               => $already_returned,
                    'return_quantity'                         => $return_quantity,
                    'conversion_factor'                       => $conversion_factor,
                    'base_quantity'                           => $base_quantity,
                    'unit_price'                               => $unit_price,
                    'discount'                                 => $discount_percent,
                    'discount_amount'                          => $line_discount_amount,
                    'tax'                                      => $tax_percent,
                    'tax_amount'                               => $line_tax_amount,
                    'subtotal'                                 => $line_subtotal,
                    'total'                                    => $line_total,
                    'cost_price'                               => $detail->cost_price ?? 0,
                    'reason'                                   => $product['reason'] ?? null,
                    'description'                              => $product['description'] ?? null,
                    'createdby_id'                             => Auth::id(),
                    'date_created'                             => now(),
                ]);
            }

            if (!$has_quantity) {
                throw new Exception('Please enter a return quantity for at least one product.');
            }

            $order_return->update([
                'subtotal'         => $subtotal,
                'discount_amount'  => $discount_amount_total,
                'tax_amount'       => $tax_amount_total,
                'total'            => $total,
            ]);

            DB::commit();

            return $order_return;
        } catch (Exception $e) {

            DB::rollBack();

            throw $e;
        }
    }

    public function getById($order_return_id)
    {
        return $this->model_order_return->with($this->with)->find($order_return_id);
    }

    public function getDetails($order_return_id)
    {
        try {
            $order_return = $this->model_order_return->getModel()::with($this->with)->findOrFail($order_return_id);

            $data = [
                'header' => [
                    'order_return_id'          => $order_return->order_return_id,
                    'order_id'                 => $order_return->order_id,
                    'daily_order_id'           => $order_return->order->daily_order_id ?? '',
                    'customer_id'              => $order_return->customer_id,
                    'warehouse_id'             => $order_return->warehouse_id,
                    'branch_id'                => $order_return->branch_id,
                    'order_return_no'          => $order_return->order_return_no,
                    'order_return_date'        => $order_return->order_return_date,
                    'refund_payment_method_id' => $order_return->refund_payment_method_id,
                    'reason'                   => $order_return->reason,
                    'description'              => $order_return->description,
                    'subtotal'                 => $order_return->subtotal,
                    'discount_amount'          => $order_return->discount_amount,
                    'tax_amount'               => $order_return->tax_amount,
                    'total'                    => $order_return->total,
                    'status'                   => $order_return->status,
                ],
                'details' => []
            ];

            foreach ($order_return->orderReturnDetails as $detail) {
                $data['details'][] = [
                    'order_return_detail_id'   => $detail->order_return_detail_id,
                    'order_detail_id'          => $detail->order_detail_id,
                    'product_id'               => $detail->product_id,
                    'product_name'             => $detail->product->name ?? '',
                    'product_variation_id'     => $detail->product_variation_id,
                    'product_variation_name'   => $detail->productVariation->name ?? '',
                    'ordered_quantity'         => $detail->ordered_quantity,
                    'already_returned_quantity' => $detail->already_returned_quantity,
                    'return_quantity'          => $detail->return_quantity,
                    'unit_id'                  => $detail->unit_id,
                    'unit_name'                => $detail->unit->name ?? 'N/A',
                    'conversion_factor'        => $detail->conversion_factor,
                    'unit_price'               => $detail->unit_price,
                    'discount'                 => $detail->discount,
                    'discount_amount'          => $detail->discount_amount,
                    'tax'                      => $detail->tax,
                    'tax_amount'               => $detail->tax_amount,
                    'subtotal'                 => $detail->subtotal,
                    'total'                    => $detail->total,
                    'reason'                   => $detail->reason,
                ];
            }

            return $data;
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function status($obj)
    {
        DB::beginTransaction();

        try {
            $order_return = $this->model_order_return->getModel()::with($this->with)->findOrFail($obj['order_return_id']);
            $old_status = $order_return->status;
            $new_status = $obj['status'];

            $order_return->update([
                'status'       => $new_status,
                'updatedby_id' => Auth::id(),
                'date_updated' => now(),
            ]);

            if ($new_status === Status::APPROVED && $old_status !== Status::APPROVED) {
                $this->applyOrderReturnPosting($order_return);
            } elseif ($old_status === Status::APPROVED && $new_status !== Status::APPROVED) {
                $this->reverseOrderReturnPosting($order_return);
            }

            DB::commit();

            $this->logActivity(
                'order_return',
                $order_return->order_return_id,
                $new_status === Status::APPROVED ? 'approved' : 'status_changed',
                ['status' => $old_status],
                ['status' => $new_status]
            );
        } catch (Exception $e) {
            DB::rollBack();

            throw $e;
        }

        return $order_return;
    }

    public function delete($order_return_id)
    {
        DB::beginTransaction();

        try {
            $order_return = $this->model_order_return->getModel()::with($this->with)->findOrFail($order_return_id);
            $was_approved = $order_return->status === Status::APPROVED;

            // Flip the status to CANCELLED before reversing (not after) - the
            // reversal's isOrderFullyReturned() check inside
            // reverseOrderReturnPosting() sums only APPROVED returns, so it
            // must see this one as no longer approved or it will wrongly
            // still count it and leave the order stuck on 'returned'.
            $order_return->update([
                'is_deleted'   => 1,
                'status'       => Status::CANCELLED,
                'deletedby_id' => Auth::id(),
                'date_deleted' => now(),
            ]);

            if ($was_approved) {
                $this->reverseOrderReturnPosting($order_return);
            }

            DB::commit();

            $this->logActivity('order_return', $order_return->order_return_id, 'deleted');

            return true;
        } catch (Exception $e) {
            DB::rollBack();

            throw $e;
        }
    }

    /**
     * True once every line on the order has been fully returned (summed
     * across all approved Order Returns against it) - the signal used to
     * transition the order itself to 'returned'.
     */
    protected function isOrderFullyReturned(Order $order): bool
    {
        $order->loadMissing('details');

        if ($order->details->isEmpty()) {
            return false;
        }

        foreach ($order->details as $detail) {
            $returned = $this->getAlreadyReturnedQuantity($detail->order_detail_id);

            if (((float) $detail->quantity - $returned) > 0.0009) {
                return false;
            }
        }

        return true;
    }

    /**
     * Self-contained order status transition + audit trail, mirroring
     * OrderService::recordStatusHistory()/transitionStatus() - kept local
     * here (rather than injecting OrderService) since this is the only order
     * status change this service ever needs to make.
     */
    protected function transitionOrderStatus(Order $order, $to_status, $reason)
    {
        $from_status = $order->status;

        $order->update([
            'status'       => $to_status,
            'updatedby_id' => Auth::id(),
            'date_updated' => now(),
        ]);

        OrderStatusHistory::create([
            'order_status_history_id' => generateUuid(),
            'order_id'                => $order->order_id,
            'from_status'             => $from_status,
            'to_status'               => $to_status,
            'reason'                  => $reason,
            'changedby_id'            => Auth::id(),
            'date_created'            => now(),
        ]);

        $this->logActivity('order', $order->order_id, 'status_changed', ['status' => $from_status], ['status' => $to_status], $reason);
    }

    /**
     * Auto-post a Sale Return Voucher, restock the returned quantities and
     * reverse their COGS/Inventory legs when an Order Return is approved.
     * Idempotent: a no-op if an active voucher already exists for this
     * return. Mirrors PurchaseReturnService::applyPurchaseReturnPosting()
     * and reverses the exact legs OrderService::post() originally posted
     * for the lines being returned.
     */
    protected function applyOrderReturnPosting(OrderReturn $order_return)
    {
        $existing = JournalEntry::where('source_type', JournalSourceTypes::SALE_RETURN)
            ->where('source_id', $order_return->order_return_id)
            ->where('is_deleted', 0)
            ->exists();

        if ($existing) {
            return;
        }

        $order = Order::with('details.product')->find($order_return->order_id);

        if (!$order) {
            throw new Exception('The originating order could not be found.');
        }

        $accounting_setting = AccountingSetting::where('business_id', $order_return->business_id)->first();

        if (!$accounting_setting || !$accounting_setting->enable_accounting) {
            throw new Exception('Accounting is not enabled for this business. Please configure Accounting Settings before approving order returns.');
        }

        app(\App\Services\Concrete\Admin\AccountingPeriodService::class)->assertPostable($order_return->business_id, now());

        if (empty($accounting_setting->default_sale_return_account_id)) {
            throw new Exception('Sale Return Account is not configured in Accounting Settings. Please configure it before approving order returns.');
        }

        if ((float) $order_return->tax_amount > 0 && empty($accounting_setting->default_tax_account_id)) {
            throw new Exception('Tax Account is not configured in Accounting Settings.');
        }

        if ((float) $order_return->discount_amount > 0 && empty($accounting_setting->default_discount_account_id)) {
            throw new Exception('Discount Account is not configured in Accounting Settings.');
        }

        if (empty($accounting_setting->default_inventory_account_id) || empty($accounting_setting->default_cogs_account_id)) {
            throw new Exception('Inventory and COGS Accounts must be configured in Accounting Settings before approving order returns.');
        }

        // Resolve the refund/credit account - mirrors how OrderService::post()
        // resolves each payment's account: a Cash refund hits the Cash
        // Account, any other named payment method hits its own mapped
        // account, and no method at all simply credits the customer's
        // receivable account (a credit note against their balance rather
        // than an actual cash movement).
        $refund_account_id = null;
        $refund_method = null;

        if (!empty($order_return->refund_payment_method_id)) {
            $refund_method = PaymentMethod::find($order_return->refund_payment_method_id);

            if (!$refund_method) {
                throw new Exception('The selected refund payment method no longer exists.');
            }

            if ($refund_method->type === 'credit') {
                throw new Exception('"Credit" is not a valid refund method - choose Cash, a bank/card method, or leave it blank to credit the customer\'s account.');
            }

            if ($refund_method->type === 'cash') {
                $refund_account_id = $accounting_setting->default_cash_account_id;

                if (empty($refund_account_id)) {
                    throw new Exception('Cash Account is not configured in Accounting Settings.');
                }
            } else {
                $refund_account_id = $refund_method->account_id;

                if (empty($refund_account_id)) {
                    throw new Exception('Payment method "' . $refund_method->name . '" is not mapped to an account.');
                }
            }
        } else {
            $refund_account_id = $accounting_setting->default_customer_account_id;

            if (empty($refund_account_id)) {
                throw new Exception('Customer Receivable Account is not configured in Accounting Settings, required to credit a return with no refund method selected.');
            }
        }

        $journal = Journal::where('short', 'SRV')->where('is_deleted', 0)->first();

        if (!$journal) {
            throw new Exception('No "Sale Return Voucher" journal category found. Please configure it before approving order returns.');
        }

        $entry_no = generateJVNum($journal->journal_id);

        $journal_entry = JournalEntry::create([
            'journal_entry_id' => generateUuid(),
            'journal_id'       => $journal->journal_id,
            'business_id'      => $order_return->business_id,
            'branch_id'        => $order_return->branch_id,
            'entry_no'         => $entry_no,
            'reference_no'     => $order_return->order_return_no,
            'entry_date'       => now(),
            'description'      => 'Auto-generated return voucher for approved order return ' . $order_return->order_return_no,
            'source_type'      => JournalSourceTypes::SALE_RETURN,
            'source_id'        => $order_return->order_return_id,
            'status'           => 'posted',
            'postedby_id'      => Auth::id(),
            'date_posted'      => now(),
            'createdby_id'     => Auth::id(),
            'date_created'     => now(),
        ]);

        // Credit: discount reversed - contra to the original discount debit.
        if ((float) $order_return->discount_amount > 0) {
            JournalEntryDetail::create([
                'journal_entry_detail_id' => generateUuid(),
                'journal_entry_id'        => $journal_entry->journal_entry_id,
                'account_id'              => $accounting_setting->default_discount_account_id,
                'debit'                   => 0,
                'credit'                  => $order_return->discount_amount,
                'description'             => 'Order Return - ' . $order_return->order_return_no,
            ]);
        }

        // Debit: Sale Return account (contra-revenue) - reverses the
        // original sale's credit to the Sale Account.
        JournalEntryDetail::create([
            'journal_entry_detail_id' => generateUuid(),
            'journal_entry_id'        => $journal_entry->journal_entry_id,
            'account_id'              => $accounting_setting->default_sale_return_account_id,
            'debit'                   => $order_return->subtotal,
            'credit'                  => 0,
            'description'             => 'Order Return - ' . $order_return->order_return_no,
        ]);

        // Debit: tax reversed - contra to the original tax credit.
        if ((float) $order_return->tax_amount > 0) {
            JournalEntryDetail::create([
                'journal_entry_detail_id' => generateUuid(),
                'journal_entry_id'        => $journal_entry->journal_entry_id,
                'account_id'              => $accounting_setting->default_tax_account_id,
                'debit'                   => $order_return->tax_amount,
                'credit'                  => 0,
                'description'             => 'Order Return - ' . $order_return->order_return_no . ' - Tax',
            ]);
        }

        // Credit: the refund/credit-note account for the total handed back.
        JournalEntryDetail::create([
            'journal_entry_detail_id' => generateUuid(),
            'journal_entry_id'        => $journal_entry->journal_entry_id,
            'account_id'              => $refund_account_id,
            'debit'                   => 0,
            'credit'                  => $order_return->total,
            'user_id'                 => empty($refund_method) ? $order_return->customer_id : null,
            'description'             => 'Order Return - ' . $order_return->order_return_no . ($refund_method ? (' - ' . $refund_method->name) : ' - Customer Credit'),
        ]);

        // Per line: return stock to inventory at its original cost basis
        // (the order_detail.cost_price snapshot from the original sale) and
        // accumulate the COGS/Inventory reversal total.
        $total_cost = 0;

        foreach ($order_return->orderReturnDetails as $detail) {
            $base_quantity = $detail->base_quantity;

            if ($base_quantity <= 0) {
                continue;
            }

            $stock = ProductVariationStock::where('business_id', $order_return->business_id)
                ->where('warehouse_id', $order_return->warehouse_id)
                ->where('product_id', $detail->product_id)
                ->where('product_variation_id', $detail->product_variation_id)
                ->first();

            $existing_qty = $stock->quantity ?? 0;
            $existing_avg = $stock->avg_price ?? 0;
            $cost_price = (float) ($detail->cost_price ?? 0);
            $new_qty = $existing_qty + $base_quantity;
            $line_cost = round($base_quantity * $cost_price, 3);
            $new_avg = $new_qty > 0 ? ((($existing_qty * $existing_avg) + $line_cost) / $new_qty) : 0;
            $total_cost += $line_cost;

            if ($stock) {
                $stock->update([
                    'quantity'  => $new_qty,
                    'avg_price' => $new_avg,
                ]);
            } else {
                $stock = ProductVariationStock::create([
                    'product_variation_stock_id' => generateUuid(),
                    'business_id'                => $order_return->business_id,
                    'warehouse_id'               => $order_return->warehouse_id,
                    'product_id'                 => $detail->product_id,
                    'product_variation_id'       => $detail->product_variation_id,
                    'quantity'                   => $new_qty,
                    'avg_price'                  => $new_avg,
                    'status'                     => 'active',
                    'createdby_id'               => Auth::id(),
                    'date_created'               => now(),
                ]);
            }

            ProductVariationStockTransaction::create([
                'product_variation_stock_transaction_id' => generateUuid(),
                'transaction_date'                       => now(),
                'transaction_type'                        => TransactionType::SALE_RETURN,
                'business_id'                             => $order_return->business_id,
                'product_id'                              => $detail->product_id,
                'product_variation_id'                    => $detail->product_variation_id,
                'warehouse_id'                             => $order_return->warehouse_id,
                'unit_id'                                  => $detail->unit_id,
                'product_variation_unit_conversion_id'     => $detail->product_variation_unit_conversion_id,
                'conversion_factor'                        => $detail->conversion_factor,
                'quantity'                                 => $detail->return_quantity,
                'base_quantity'                            => $base_quantity,
                'unit_price'                               => $detail->unit_price,
                'total_price'                              => $line_cost,
                'quantity_after'                           => $new_qty,
                'avg_price_after'                          => $new_avg,
                'reference_id'                              => $order_return->order_return_id,
                'reference_type'                            => ReferenceType::SALE_RETURN,
                'remarks'                                   => 'Auto-created on approval of order return ' . $order_return->order_return_no,
                'createdby_id'                              => Auth::id(),
                'date_created'                              => now(),
            ]);
        }

        if ($total_cost > 0) {
            JournalEntryDetail::create([
                'journal_entry_detail_id' => generateUuid(),
                'journal_entry_id'        => $journal_entry->journal_entry_id,
                'account_id'              => $accounting_setting->default_inventory_account_id,
                'debit'                   => $total_cost,
                'credit'                  => 0,
                'description'             => 'Order Return - ' . $order_return->order_return_no . ' - Inventory',
            ]);

            JournalEntryDetail::create([
                'journal_entry_detail_id' => generateUuid(),
                'journal_entry_id'        => $journal_entry->journal_entry_id,
                'account_id'              => $accounting_setting->default_cogs_account_id,
                'debit'                   => 0,
                'credit'                  => $total_cost,
                'description'             => 'Order Return - ' . $order_return->order_return_no . ' - COGS',
            ]);
        }

        if ($order->status !== 'returned' && $this->isOrderFullyReturned($order)) {
            $this->transitionOrderStatus($order, 'returned', 'Fully returned via Order Return ' . $order_return->order_return_no);
        }
    }

    /**
     * Reverse the Sale Return Voucher and stock effects created when an
     * Order Return was approved, and revert the order back to 'posted' if
     * it had been marked 'returned' because of this return. Idempotent: a
     * no-op if nothing active remains to reverse. Mirrors
     * PurchaseReturnService::reversePurchaseReturnPosting().
     */
    protected function reverseOrderReturnPosting(OrderReturn $order_return)
    {
        $journal_entry = JournalEntry::where('source_type', JournalSourceTypes::SALE_RETURN)
            ->where('source_id', $order_return->order_return_id)
            ->where('is_deleted', 0)
            ->first();

        if ($journal_entry) {
            $journal_entry->update([
                'is_deleted'   => 1,
                'deletedby_id' => Auth::id(),
                'date_deleted' => now(),
            ]);
        }

        $stock_transactions = ProductVariationStockTransaction::where('reference_type', ReferenceType::SALE_RETURN)
            ->where('reference_id', $order_return->order_return_id)
            ->where('is_deleted', 0)
            ->get();

        if ($stock_transactions->isNotEmpty()) {
            $stock_transactions->each(function ($transaction) {
                $transaction->update([
                    'is_deleted'   => 1,
                    'deletedby_id' => Auth::id(),
                    'date_deleted' => now(),
                ]);
            });

            $affected = $stock_transactions->unique(function ($transaction) {
                return $transaction->business_id . '|' . $transaction->warehouse_id . '|' .
                    $transaction->product_id . '|' . $transaction->product_variation_id;
            });

            $stock_service = app(ProductVariationStockService::class);

            foreach ($affected as $transaction) {
                $stock_service->recomputeLedger(
                    $transaction->business_id,
                    $transaction->warehouse_id,
                    $transaction->product_id,
                    $transaction->product_variation_id
                );
            }
        }

        $order = Order::with('details')->find($order_return->order_id);

        if ($order && $order->status === 'returned' && !$this->isOrderFullyReturned($order)) {
            $this->transitionOrderStatus($order, 'posted', 'Order Return ' . $order_return->order_return_no . ' reversed - order is no longer fully returned');
        }
    }
}
