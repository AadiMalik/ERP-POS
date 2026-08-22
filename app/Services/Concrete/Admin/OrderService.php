<?php

namespace App\Services\Concrete\Admin;

use App\Enums\Filter;
use App\Enums\JournalSourceTypes;
use App\Enums\ReferenceType;
use App\Enums\RoleNames;
use App\Enums\Status;
use App\Enums\TransactionType;
use App\Models\AccountingSetting;
use App\Models\BusinessSetting;
use App\Models\CustomerProfile;
use App\Models\CustomerSetting;
use App\Models\Discount;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\JournalEntryDetail;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\OrderPayment;
use App\Models\OrderStatusHistory;
use App\Models\OrderType;
use App\Models\PaymentMethod;
use App\Models\PosRegisterSession;
use App\Models\PosSetting;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\ProductVariationStock;
use App\Models\ProductVariationStockTransaction;
use App\Models\ProductVariationUnitConversion;
use App\Models\NotificationSetting;
use App\Models\SaleType;
use App\Models\Voucher;
use App\Repository\Repository;
use App\Traits\Auditable;
use App\Traits\Notifiable;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

/**
 * The central, channel-agnostic sales-transaction service - shared by POS,
 * Website, Mobile App, API and any future sales channel via `order_source_id`.
 * Mirrors PurchaseReturnService's apply/reverse posting pattern exactly:
 * save() only ever writes orders/order_details/order_payments (draft/hold, no
 * stock or accounting side-effects); post() is the one place that touches
 * stock and the general ledger, wrapped in a single DB transaction,
 * idempotency-guarded on the JournalEntry(source_type=POS_SALE,
 * source_id=order_id); void() is the exact mirror, reusing
 * ProductVariationStockService::recomputeLedger() for the stock-reversal
 * replay rather than reimplementing it.
 *
 * A register/register-session is only required for the POS channel - when
 * `register_session_id` is absent from the payload, business_id/branch_id/
 * warehouse_id must be supplied directly (Website/Mobile App/API channels).
 */
class OrderService
{
    use Auditable;
    use Notifiable;

    protected $model_order;
    protected $model_order_detail;
    protected $model_order_payment;

    protected $with = [
        'business',
        'branch',
        'warehouse',
        'register',
        'registerSession',
        'cashier',
        'user',
        'orderType',
        'orderSource',
        'saleType',
        'discount',
        'voucher',
        'details',
        'details.product',
        'details.productVariation',
        'details.unit',
        'details.saleType',
        'payments',
        'payments.paymentMethod',
        'customerPayments',
    ];

    protected $discount_service;
    protected $voucher_service;
    protected $customer_service;
    protected $stock_service;
    protected $pricing_engine;

    public function __construct(
        DiscountService $discount_service,
        VoucherService $voucher_service,
        CustomerService $customer_service,
        ProductVariationStockService $stock_service,
        VariationPricingService $pricing_engine
    ) {
        $this->model_order = new Repository(new Order());
        $this->model_order_detail = new Repository(new OrderDetail());
        $this->model_order_payment = new Repository(new OrderPayment());

        $this->discount_service = $discount_service;
        $this->voucher_service = $voucher_service;
        $this->customer_service = $customer_service;
        $this->stock_service = $stock_service;
        $this->pricing_engine = $pricing_engine;
    }

    /**
     * Builds the where/scope conditions shared by getData() (the POS Order
     * History + Admin Order List table) and getHistorySummary() (the POS
     * Order History "Sales Summary" panel), so the two always agree on
     * exactly which orders a given filter set + role is allowed to see.
     */
    protected function applyHistoryFilters($query, $obj)
    {
        $wh = [];

        if (!empty($obj['order_id'])) {
            $wh[] = ['order_id', $obj['order_id']];
        }
        if (!empty($obj['daily_order_id'])) {
            $wh[] = ['daily_order_id', $obj['daily_order_id']];
        }
        if (isset($obj['business_id']) && $obj['business_id'] != 0 && $obj['business_id'] != "") {
            $wh[] = ['business_id', $obj['business_id']];
        }
        if (isset($obj['branch_id']) && $obj['branch_id'] != 0 && $obj['branch_id'] != "") {
            $wh[] = ['branch_id', $obj['branch_id']];
        }
        if (isset($obj['warehouse_id']) && $obj['warehouse_id'] != 0 && $obj['warehouse_id'] != "") {
            $wh[] = ['warehouse_id', $obj['warehouse_id']];
        }
        if (isset($obj['register_id']) && $obj['register_id'] != 0 && $obj['register_id'] != "") {
            $wh[] = ['register_id', $obj['register_id']];
        }
        if (isset($obj['cashier_id']) && $obj['cashier_id'] != 0 && $obj['cashier_id'] != "") {
            $wh[] = ['cashier_id', $obj['cashier_id']];
        }
        if (isset($obj['customer_id']) && $obj['customer_id'] != 0 && $obj['customer_id'] != "") {
            $wh[] = ['user_id', $obj['customer_id']];
        }
        if (isset($obj['order_type_id']) && $obj['order_type_id'] != 0 && $obj['order_type_id'] != "") {
            $wh[] = ['order_type_id', $obj['order_type_id']];
        }
        if (isset($obj['order_source_id']) && $obj['order_source_id'] != 0 && $obj['order_source_id'] != "") {
            $wh[] = ['order_source_id', $obj['order_source_id']];
        }
        if (isset($obj['status']) && $obj['status'] !== '' && $obj['status'] !== []) {
            // The POS Held Orders list needs both 'draft' and 'hold' at once
            // (see fetchHeldOrders() in pos-screen.js) - every other caller
            // still sends a single scalar status, which keeps the original
            // exact-match behavior via $wh below.
            if (is_array($obj['status'])) {
                $query->whereIn('status', $obj['status']);
            } elseif ($obj['status'] != 0) {
                $wh[] = ['status', $obj['status']];
            }
        }
        // Order Takers may only ever browse today's sales - whatever date
        // range the client sends (or omits) is ignored and today is forced
        // instead, so this holds even if the frontend's date inputs are
        // bypassed entirely. Every other role keeps the normal client-
        // supplied range. Enforced here (the single place order/data and
        // order/history-summary are served from) so it protects the POS
        // Order History page, its Sales Summary, and the full Admin Order
        // List alike.
        if (getRoleName() === RoleNames::ORDERTAKER) {
            $today = Carbon::today()->format('Y-m-d');
            $wh[] = ['sale_date', '>=', $today];
            $wh[] = ['sale_date', '<=', $today];
        } else {
            if (!empty($obj['start_date'])) {
                $wh[] = ['order_date', '>=', Carbon::parse($obj['start_date'])->startOfDay()];
            }
            if (!empty($obj['end_date'])) {
                $wh[] = ['order_date', '<=', Carbon::parse($obj['end_date'])->endOfDay()];
            }
            if (!empty($obj['sale_date_start'])) {
                $wh[] = ['sale_date', '>=', Carbon::parse($obj['sale_date_start'])->format('Y-m-d')];
            }
            if (!empty($obj['sale_date_end'])) {
                $wh[] = ['sale_date', '<=', Carbon::parse($obj['sale_date_end'])->format('Y-m-d')];
            }
        }

        $query->where($wh)->where('is_deleted', 0);

        if (!empty($obj['payment_method_id'])) {
            $query->whereHas('payments', function ($q) use ($obj) {
                $q->where('payment_method_id', $obj['payment_method_id']);
            });
        }

        // Payment status has no dedicated column - it's derived from
        // paid_amount vs total (same rule the thermal receipt uses, see
        // admin/order/print/thermal.blade.php) - so it's filtered here via
        // that same comparison rather than a stored value.
        if (!empty($obj['payment_status'])) {
            if ($obj['payment_status'] === 'paid') {
                $query->whereColumn('paid_amount', '>=', 'total');
            } elseif ($obj['payment_status'] === 'unpaid') {
                $query->where('paid_amount', '<=', 0);
            } elseif ($obj['payment_status'] === 'partially_paid') {
                $query->where('paid_amount', '>', 0)->whereColumn('paid_amount', '<', 'total');
            }
        }

        // Dashboard callers may inject a wider allow_roles (e.g. every Tier-1
        // dashboard role) via $obj without touching this default, which
        // still gates the POS Order History page and the Admin Order List.
        $allow_roles = $obj['allow_roles'] ?? [
            RoleNames::SUPERADMIN,
            RoleNames::BUSINESSADMIN,
            RoleNames::BRANCHADMIN,
            RoleNames::POSMANAGER,
            RoleNames::ORDERTAKER,
        ];

        return applyRoleScope($query, $allow_roles);
    }

    public function getData($obj)
    {
        $orderBy = Filter::ORDERBY;

        if (isset($obj['orderBy']) && $obj['orderBy'] != 0 && $obj['orderBy'] != "") {
            $orderBy = $obj['orderBy'];
        }

        $datatable = $this->applyHistoryFilters(
            $this->model_order->getModel()::with($this->with)->withCount('details as total_products'),
            $obj
        );

        $datatable->orderBy('order_date', $orderBy);

        return DataTables::of($datatable)
            ->addColumn('order_date', function ($item) {
                return $item->order_date ? localDateTime($item->order_date) : '-';
            })
            ->addColumn('sale_date', function ($item) {
                return $item->sale_date ? localDate($item->sale_date) : '-';
            })
            ->addColumn('business', function ($item) {
                return $item->business->name ?? '-';
            })
            ->addColumn('branch', function ($item) {
                return $item->branch->name ?? '-';
            })
            ->addColumn('warehouse', function ($item) {
                return $item->warehouse->name ?? '-';
            })
            ->addColumn('register', function ($item) {
                return $item->register->name ?? '-';
            })
            ->addColumn('cashier', function ($item) {
                return $item->cashier->name ?? '-';
            })
            ->addColumn('customer', function ($item) {
                return $item->user->name ?? '-';
            })
            ->addColumn('order_type', function ($item) {
                return $item->orderType->name ?? '-';
            })
            ->addColumn('order_source', function ($item) {
                return $item->orderSource->name ?? '-';
            })
            ->addColumn('total', function ($item) {
                return currency($item->total ?? 0);
            })
            ->addColumn('paid_amount', function ($item) {
                return currency($item->paid_amount ?? 0);
            })
            ->addColumn('due_amount', function ($item) {
                return currency(max(($item->total ?? 0) - ($item->paid_amount ?? 0), 0));
            })
            ->addColumn('payment_method', function ($item) {
                return $this->resolvePaymentMethodLabel($item);
            })
            ->addColumn('payment_status', function ($item) {
                $due = max(($item->total ?? 0) - ($item->paid_amount ?? 0), 0);

                if ($due <= 0) {
                    $payment_status = Status::PAID;
                } elseif (($item->paid_amount ?? 0) > 0) {
                    $payment_status = Status::PARTIALLY_PAID;
                } else {
                    $payment_status = Status::UNPAID;
                }

                $badges = [
                    Status::PAID => 'bg-label-success',
                    Status::PARTIALLY_PAID => 'bg-label-warning',
                    Status::UNPAID => 'bg-label-danger',
                ];
                $badge = $badges[$payment_status] ?? 'bg-label-secondary';
                $label = ucwords(str_replace('_', ' ', $payment_status));

                return '<span class="badge ' . $badge . '">' . $label . '</span>';
            })
            ->addColumn('sale_type', function ($item) {
                return $this->formatSaleTypeBadge($item->details);
            })
            ->addColumn('status', function ($item) {
                $badges = [
                    'draft' => 'bg-label-secondary',
                    'hold' => 'bg-label-warning',
                    'posted' => 'bg-label-success',
                    'cancelled' => 'bg-label-dark',
                    'void' => 'bg-label-danger',
                    'returned' => 'bg-label-info',
                ];
                $badge = $badges[$item->status] ?? 'bg-label-secondary';
                return '<span class="badge ' . $badge . '">' . ucfirst($item->status) . '</span>';
            })
            // Raw (non-badge) status value, alongside the HTML-badge 'status'
            // column above - needed by callers like the POS Held Orders list
            // (pos-screen.js fetchHeldOrders()) that need the actual status
            // string, not a rendered badge.
            ->addColumn('raw_status', function ($item) {
                return $item->status;
            })
            ->addColumn('action', function ($item) use ($obj) {
                $printButton = "<a class='btn btn-icon btn-outline-secondary mr-2' target='_blank'
                    href='" . route('order.print', $item->order_id) . "' title='Print'>
                    <i class='fa fa-print'></i>
                    </a>";

                $thermalPrintButton = "<a class='btn btn-icon btn-outline-info mr-2' target='_blank'
                    href='" . route('order.thermal-print', $item->order_id) . "' title='Thermal Print'>
                    <i class='fa fa-receipt'></i>
                    </a>";

                $viewButton = "<a class='btn btn-icon btn-outline-primary mr-2'
                    href='" . route('order.show', $item->order_id) . "' title='View'>
                    <i class='fa fa-eye'></i>
                    </a>";

                // POS Order History only ever needs View + Thermal Print -
                // the normal Print and JV buttons stay for the main Admin
                // Order List (which shares this same getData() endpoint but
                // never sends context=pos).
                if (($obj['context'] ?? null) === 'pos') {
                    return $viewButton . $thermalPrintButton;
                }

                $viewJvButton = in_array($item->status, ['posted', 'returned'])
                    ? "<button type='button' class='btn btn-icon btn-outline-dark mr-2 view-jv-btn'
                        data-source-type='" . \App\Enums\JournalSourceTypes::POS_SALE . "' data-source-id='{$item->order_id}' title='View JV'>
                        <i class='fa fa-book'></i>
                        </button>"
                    : '';

                return $viewButton . $viewJvButton . $printButton . $thermalPrintButton;
            })
            ->rawColumns(['business', 'branch', 'warehouse', 'register', 'cashier', 'customer', 'order_type', 'order_source', 'total', 'status', 'payment_status', 'sale_type', 'action'])
            ->make(true);
    }

    /**
     * Renders the order-list/order-detail "Sale Type" badge: the single sale
     * type if every line used the same one, otherwise "Mixed" - never stored
     * redundantly on the order itself, always derived from order_details.
     */
    public function formatSaleTypeBadge($details): string
    {
        $labels = collect($details)->map(fn ($detail) => $this->resolveSaleTypeLabel($detail))->filter()->unique();

        if ($labels->isEmpty()) {
            return '-';
        }

        if ($labels->count() > 1) {
            return '<span class="badge bg-label-dark">Mixed</span>';
        }

        return '<span class="badge bg-label-secondary">' . $labels->first() . '</span>';
    }

    public function resolveSaleTypeLabel($detail): ?string
    {
        return $detail->saleType->name ?? null;
    }

    /**
     * Order-level "payment method" label shown across the Order List/POS
     * Order History table, the Order Detail modal, and Order Show:
     * - "Partial" while due > 0 (a credit order that hasn't received its
     *   full total yet - regardless of whether it's received any money at
     *   all so far).
     * - the single method name if every payment received (POS-time
     *   order_payments + later posted customer_payments) used the same one.
     * - "Multi Payment" once due <= 0 but more than one distinct method
     *   contributed (e.g. Cash at checkout + Card via a later Receive
     *   Payment, or two separate Receive Payments in different methods).
     * Credit-type order_payments lines are excluded - they're a due
     * placeholder recorded at sale time, not money actually received,
     * exactly like post() already treats them for paid_amount.
     */
    public function resolvePaymentMethodLabel(Order $order): string
    {
        $due = round((float) ($order->total ?? 0) - (float) ($order->paid_amount ?? 0), 3);

        $labels = collect();

        foreach ($order->payments as $payment) {
            $method = $payment->paymentMethod;
            if ($method && $method->type !== 'credit') {
                $labels->push($method->name);
            }
        }

        foreach ($order->customerPayments as $payment) {
            if ($payment->status === Status::POSTED) {
                $labels->push(ucwords(str_replace('_', ' ', $payment->payment_method)));
            }
        }

        $labels = $labels->filter()->unique();

        if ($due > 0.001) {
            return 'Partial';
        }

        return $labels->count() > 1 ? 'Multi Payment' : ($labels->first() ?? '-');
    }

    /**
     * Aggregate totals for whatever filter set the POS Order History page
     * (or the Admin Order List) currently has applied - backs the "Sales
     * Summary" panel and its thermal print. Same filters/scope as getData(),
     * just summed instead of paginated.
     */
    public function getHistorySummary($obj)
    {
        $base = fn () => $this->applyHistoryFilters($this->model_order->getModel()::query(), $obj);

        $total_orders = $base()->count();
        $total_sales = (float) $base()->sum('total');
        $total_paid = (float) $base()->sum('paid_amount');
        $total_due = max($total_sales - $total_paid, 0);

        $by_status = $base()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $by_payment_method = $base()
            ->with('payments.paymentMethod')
            ->get()
            ->flatMap(fn ($order) => $order->payments)
            ->groupBy(fn ($payment) => $payment->paymentMethod->name ?? 'Unknown')
            ->map(fn ($payments) => (float) $payments->sum('amount'));

        return [
            'total_orders' => $total_orders,
            'total_sales' => $total_sales,
            'total_paid' => $total_paid,
            'total_due' => $total_due,
            'by_status' => $by_status,
            'by_payment_method' => $by_payment_method,
        ];
    }

    /**
     * Dashboard-shaped sales summary: extends getHistorySummary()'s
     * total_orders/total_sales with by_order_type and by_order_source
     * groupings (mirroring its existing by_payment_method shape). Reuses
     * applyHistoryFilters() so role/branch/date scoping - including Order
     * Taker's forced-today lock - applies exactly as it does everywhere else.
     */
    public function getDashboardSummary(array $obj): array
    {
        $summary = $this->getHistorySummary($obj);

        $base = fn () => $this->applyHistoryFilters($this->model_order->getModel()::query(), $obj);

        $by_order_type = $base()
            ->with('orderType')
            ->get(['order_id', 'order_type_id', 'total'])
            ->groupBy(fn ($order) => $order->orderType->name ?? 'Unknown')
            ->map(fn ($orders) => (float) $orders->sum('total'));

        $by_order_source = $base()
            ->with('orderSource')
            ->get(['order_id', 'order_source_id', 'total'])
            ->groupBy(fn ($order) => $order->orderSource->name ?? 'Unknown')
            ->map(fn ($orders) => (float) $orders->sum('total'));

        return array_merge($summary, [
            'by_order_type' => $by_order_type,
            'by_order_source' => $by_order_source,
        ]);
    }

    /**
     * Daily sales totals for a dashboard's filter set - powers the sales
     * trend chart. Reuses applyHistoryFilters() like every other dashboard
     * aggregate.
     */
    public function getDailyTrend(array $obj): array
    {
        return $this->applyHistoryFilters($this->model_order->getModel()::query(), $obj)
            ->selectRaw('DATE(order_date) as day, SUM(total) as total')
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('total', 'day')
            ->map(fn ($v) => (float) $v)
            ->all();
    }

    /**
     * Top-selling products for a dashboard's filter set, ranked by quantity
     * sold. The in-scope order IDs come from applyHistoryFilters() used as a
     * subquery, so this stays in lockstep with every other filter/scope rule
     * without duplicating them.
     */
    public function getTopSellingProducts(array $obj, int $limit = 8)
    {
        $orderIds = $this->applyHistoryFilters($this->model_order->getModel()::query(), $obj)
            ->select('order_id');

        return OrderDetail::query()
            ->whereIn('order_id', $orderIds)
            ->select('product_id', 'product_variation_id')
            ->selectRaw('SUM(quantity) as total_quantity, SUM(subtotal) as total_revenue')
            ->groupBy('product_id', 'product_variation_id')
            ->with(['product:product_id,name', 'productVariation:product_variation_id,name'])
            ->orderByDesc('total_quantity')
            ->limit($limit)
            ->get();
    }

    /**
     * Most recent orders for a dashboard's filter set - same filter/scope
     * rules as getData(), just a plain limited list instead of a DataTable.
     */
    public function getRecent(array $obj, int $limit = 8)
    {
        return $this->applyHistoryFilters(
            $this->model_order->getModel()::with($this->with),
            $obj
        )
            ->orderByDesc('order_date')
            ->limit($limit)
            ->get();
    }

    public function getById($order_id)
    {
        return $this->model_order->getModel()::with($this->with)->find($order_id);
    }

    public function getDetails($order_id)
    {
        $order = $this->model_order->getModel()::with($this->with)->findOrFail($order_id);

        $data = [
            'header' => [
                'order_id' => $order->order_id,
                'daily_order_id' => $order->daily_order_id,
                'business_id' => $order->business_id,
                'branch_id' => $order->branch_id,
                'warehouse_id' => $order->warehouse_id,
                'register_id' => $order->register_id,
                'register_session_id' => $order->register_session_id,
                'cashier_id' => $order->cashier_id,
                'customer_id' => $order->user_id,
                'order_type_id' => $order->order_type_id,
                'order_source_id' => $order->order_source_id,
                'sale_type_id' => $order->sale_type_id,
                'order_date' => $order->order_date,
                'sale_date' => $order->sale_date,
                'subtotal' => $order->subtotal,
                'discount' => $order->discount,
                'discount_amount' => $order->discount_amount,
                'tax' => $order->tax,
                'tax_amount' => $order->tax_amount,
                'total' => $order->total,
                'paid_amount' => $order->paid_amount,
                'due_amount' => round(max((float) $order->total - (float) $order->paid_amount, 0), 3),
                'payment_method_label' => $this->resolvePaymentMethodLabel($order),
                'change_amount' => $order->change_amount,
                'discount_id' => $order->discount_id,
                'voucher_id' => $order->voucher_id,
                'voucher_code' => optional($order->voucher)->code,
                'voucher_discount_amount' => $order->voucher_discount_amount,
                'notes' => $order->notes,
                'due_date' => $order->due_date,
                'delivery_address' => $order->delivery_address,
                'status' => $order->status,
                'fbr_invoice_number' => $order->fbr_invoice_number,
                'fbr_status' => $order->fbr_status,
                'pra_invoice_number' => $order->pra_invoice_number,
                'pra_status' => $order->pra_status,
            ],
            'details' => [],
            'payments' => [],
            'customer_payments' => [],
        ];

        foreach ($order->details as $detail) {
            $data['details'][] = [
                'order_detail_id' => $detail->order_detail_id,
                'product_id' => $detail->product_id,
                'product_name' => $detail->product->name ?? '',
                'product_variation_id' => $detail->product_variation_id,
                'product_variation_name' => $detail->productVariation->name ?? '',
                'unit_id' => $detail->unit_id,
                'unit_name' => $detail->unit->name ?? '',
                'quantity' => $detail->quantity,
                'conversion_factor' => $detail->conversion_factor,
                'base_quantity' => $detail->base_quantity,
                'unit_price' => $detail->unit_price,
                'sale_type_id' => $detail->sale_type_id,
                'sale_type_name' => $detail->saleType->name ?? '',
                'final_unit_price' => $detail->final_unit_price,
                'discount' => $detail->discount,
                'discount_amount' => $detail->discount_amount,
                'tax' => $detail->tax,
                'tax_amount' => $detail->tax_amount,
                'subtotal' => $detail->subtotal,
                'total' => $detail->total,
                'cost_price' => $detail->cost_price,
                'notes' => $detail->notes,
            ];
        }

        foreach ($order->payments as $payment) {
            $data['payments'][] = [
                'order_payment_id' => $payment->order_payment_id,
                'payment_method_id' => $payment->payment_method_id,
                'payment_method_name' => $payment->paymentMethod->name ?? '',
                'amount' => $payment->amount,
                'reference_no' => $payment->reference_no,
            ];
        }

        // Later settlements against this order (Receive Payment / Customer
        // Payment) - running "paid to date"/"remaining balance" mirrors the
        // same logic already used in admin/order/show.blade.php's Payment
        // History panel, just computed here so the POS Order Detail modal
        // (which only has this endpoint to work with) can render it too.
        $running_paid = 0;
        foreach ($order->customerPayments->sortBy('payment_date')->values() as $payment) {
            $running_paid += $payment->status === Status::POSTED ? (float) $payment->net_amount : 0;
            $data['customer_payments'][] = [
                'customer_payment_id' => $payment->customer_payment_id,
                'payment_date' => $payment->payment_date,
                'payment_method' => ucwords(str_replace('_', ' ', $payment->payment_method)),
                'reference_no' => $payment->reference_no,
                'status' => $payment->status,
                'amount' => $payment->net_amount,
                'paid_to_date' => round($running_paid, 3),
                'remaining_due' => round(max((float) $order->total - $running_paid, 0), 3),
            ];
        }

        return $data;
    }

    /**
     * Creates or updates a draft/hold order. NEVER touches stock or
     * accounting - only post() does that. Every money figure is recomputed
     * here from server-known configuration (product prices, tax rates,
     * discount/voucher eligibility) rather than trusted from the client, so a
     * tampered request body cannot change what an order is actually worth;
     * the client only ever supplies product/qty/selected-discount-or-voucher
     * identifiers.
     *
     * `register_session_id` is only present for the POS channel - business_id/
     * branch_id/warehouse_id are derived from the open session in that case.
     * For any other channel (Website/Mobile App/API) the caller must supply
     * business_id/branch_id/warehouse_id directly and register/session/cashier
     * stay null on the order.
     */
    public function save($obj)
    {
        DB::beginTransaction();

        try {
            $session = null;
            $register_id = null;
            $cashier_id = null;

            if (!empty($obj['register_session_id'])) {
                $session = PosRegisterSession::find($obj['register_session_id']);

                if (!$session || $session->status !== 'open') {
                    throw new Exception('An open register session is required to create or edit a POS order.');
                }

                $business_id = $session->business_id;
                $branch_id = $session->branch_id;
                $warehouse_id = $session->register->warehouse_id ?? null;
                $register_id = $session->pos_register_id;
                $cashier_id = $session->cashier_id;

                if (empty($warehouse_id)) {
                    throw new Exception('The selected register is not linked to a warehouse.');
                }
            } else {
                $business_id = $obj['business_id'] ?? Auth::user()->business_id ?? null;
                $branch_id = $obj['branch_id'] ?? null;
                $warehouse_id = $obj['warehouse_id'] ?? null;

                if (empty($business_id) || empty($branch_id) || empty($warehouse_id)) {
                    throw new Exception('business_id, branch_id and warehouse_id are required to create an order for this channel.');
                }
            }

            // firstOrCreate (not first()) so a business that has never touched
            // POS Settings still gets a sane default row instead of every ??
            // fallback below silently degrading on a null object.
            $pos_setting = PosSetting::firstOrCreate(['business_id' => $business_id]);

            $sale_date = !empty($obj['sale_date']) ? Carbon::parse($obj['sale_date']) : Carbon::today();
            $this->validateSaleDate($sale_date, $pos_setting);

            $user_id = $obj['customer_id'] ?? $pos_setting->default_customer_user_id ?? null;

            if (empty($user_id)) {
                $walkin = CustomerProfile::where('business_id', $business_id)->where('is_walkin', 1)->where('is_deleted', 0)->first();
                $user_id = $walkin->user_id ?? null;
            }

            $order_type_id = $obj['order_type_id'] ?? $pos_setting->default_order_type_id ?? null;
            $order_source_id = $obj['order_source_id'] ?? $pos_setting->default_order_source_id ?? null;

            if (empty($order_source_id)) {
                throw new Exception('order_source_id is required to identify the originating sales channel.');
            }

            // Order-level default Sale Type - falls back to the business's
            // default Sale Type (see SaleTypeService::seedDefaults()) when the
            // client doesn't send one.
            $sale_type_id = $obj['sale_type_id'] ?? SaleType::where('business_id', $business_id)
                ->where('is_default', 1)
                ->where('is_deleted', 0)
                ->value('sale_type_id');

            // "Delivery" order types are identified by the default seeded
            // code (OrderTypeService::$default_types) rather than a dedicated
            // flag column - see OrderService class docblock context. A
            // delivery order must carry a delivery address before it can be
            // saved as draft/hold, same as any other required order field.
            $is_delivery_order = !empty($order_type_id)
                && OrderType::where('order_type_id', $order_type_id)->value('code') === 'DELIVERY';

            if ($is_delivery_order && empty(trim($obj['delivery_address'] ?? ''))) {
                throw new Exception('Delivery address is required for delivery orders.');
            }

            $status = in_array($obj['status'] ?? 'draft', ['draft', 'hold'], true) ? $obj['status'] : 'draft';

            //====================================
            // Update
            //====================================
            if (!empty($obj['order_id'])) {
                $order = $this->model_order->getModel()::findOrFail($obj['order_id']);

                if (!in_array($order->status, ['draft', 'hold'], true)) {
                    throw new Exception('Only draft or held orders can be edited.');
                }

                $order->update([
                    'warehouse_id' => $warehouse_id,
                    'register_id' => $register_id,
                    'register_session_id' => $session->pos_register_session_id ?? null,
                    'cashier_id' => $cashier_id,
                    'user_id' => $user_id,
                    'order_type_id' => $order_type_id,
                    'order_source_id' => $order_source_id,
                    'sale_type_id' => $sale_type_id,
                    'sale_date' => $sale_date->format('Y-m-d'),
                    'notes' => $obj['notes'] ?? null,
                    'delivery_address' => $obj['delivery_address'] ?? null,
                    'status' => $status,
                    'fbr_invoice_number' => $obj['fbr_invoice_number'] ?? $order->fbr_invoice_number,
                    'pra_invoice_number' => $obj['pra_invoice_number'] ?? $order->pra_invoice_number,
                    'updatedby_id' => Auth::id(),
                    'date_updated' => now(),
                ]);

                $this->model_order_detail->getModel()::where('order_id', $order->order_id)->delete();
                OrderPayment::where('order_id', $order->order_id)->delete();
            }
            //====================================
            // Create
            //====================================
            else {
                $daily_order_id = generateDailyOrderNumber($business_id, $branch_id, $sale_date, $pos_setting->daily_order_id_reset ?? 'daily');

                $order = $this->model_order->create([
                    'order_id' => generateUuid(),
                    'daily_order_id' => $daily_order_id,
                    'business_id' => $business_id,
                    'branch_id' => $branch_id,
                    'warehouse_id' => $warehouse_id,
                    'register_id' => $register_id,
                    'register_session_id' => $session->pos_register_session_id ?? null,
                    'cashier_id' => $cashier_id,
                    'user_id' => $user_id,
                    'order_type_id' => $order_type_id,
                    'order_source_id' => $order_source_id,
                    'sale_type_id' => $sale_type_id,
                    'order_date' => now(),
                    'sale_date' => $sale_date->format('Y-m-d'),
                    'notes' => $obj['notes'] ?? null,
                    'delivery_address' => $obj['delivery_address'] ?? null,
                    'status' => $status,
                    'fbr_invoice_number' => $obj['fbr_invoice_number'] ?? null,
                    'pra_invoice_number' => $obj['pra_invoice_number'] ?? null,
                    'is_deleted' => 0,
                    'createdby_id' => Auth::id(),
                    'date_created' => now(),
                ]);

                $this->recordStatusHistory($order->order_id, null, $status, 'Order created');
            }

            $totals = $this->saveLinesAndComputeTotals($order, $obj, $pos_setting);

            $order->update([
                'subtotal' => $totals['subtotal'],
                'discount' => $totals['discount_display'],
                'discount_amount' => $totals['discount_amount'],
                'tax' => $totals['tax_display'],
                'tax_amount' => $totals['tax_amount'],
                'total' => $totals['total'],
                'discount_id' => $totals['discount_id'],
                'voucher_id' => $totals['voucher_id'],
                'voucher_discount_amount' => $totals['voucher_discount_amount'],
            ]);

            if (!empty($obj['payments'])) {
                $this->saveLinePayments($order->order_id, $obj['payments']);
            }

            DB::commit();

            return $this->getById($order->order_id);
        } catch (Exception $e) {
            DB::rollBack();

            throw $e;
        }
    }

    protected function validateSaleDate(Carbon $sale_date, ?PosSetting $pos_setting)
    {
        $today = Carbon::today();

        if ($sale_date->gt($today)) {
            throw new Exception('Sale date cannot be in the future.');
        }

        if ($sale_date->lt($today)) {
            if (empty($pos_setting) || !$pos_setting->allow_backdated_sale) {
                throw new Exception('Backdated sales are not allowed. Please configure this in POS Settings if needed.');
            }

            if (!empty($pos_setting->backdated_sale_max_days)) {
                $earliest = $today->copy()->subDays((int) $pos_setting->backdated_sale_max_days);

                if ($sale_date->lt($earliest)) {
                    throw new Exception('Sale date is beyond the allowed backdating window of ' . $pos_setting->backdated_sale_max_days . ' day(s).');
                }
            }
        }
    }

    /**
     * Resolves the tax percent to apply to an order from Business Settings -
     * never hard-coded, never picked per line. The Card Tax Rate only applies
     * when every payment on the order is a card-type payment method; any
     * cash/other component in the mix falls back to the Overall Tax Rate.
     */
    protected function resolveTaxPercent($business_id, array $payment_method_ids): float
    {
        $business_setting = BusinessSetting::where('business_id', $business_id)->first();

        if (!$business_setting) {
            return 0;
        }

        $is_fully_card = !empty($payment_method_ids) && collect($payment_method_ids)->every(function ($payment_method_id) {
            $method = PaymentMethod::find($payment_method_id);

            return $method && $method->type === 'card';
        });

        return (float) ($is_fully_card ? $business_setting->card_tax_rate : $business_setting->overall_tax_rate);
    }

    /**
     * Rebuilds the order's line items and computes every money figure
     * server-side. Discount stacking is SEQUENTIAL: each line's own discount
     * (if enable_discount + discount_level permits line-level) reduces that
     * line's subtotal first; an order-level discount/voucher (if
     * discount_level permits order-level) is then applied on top of the sum
     * of the already-line-discounted subtotals. Tax is a single business-wide
     * percent (Overall or Card, see resolveTaxPercent()) applied uniformly to
     * every line's post-line-discount (but pre-order-discount) taxable
     * amount - the order-level discount is booked as a separate contra-
     * revenue amount at posting time rather than retroactively reducing the
     * tax basis already established per line. Payments are usually not yet
     * known when a draft is first saved, so this uses whichever payments are
     * available now as a working estimate - post() re-resolves the rate from
     * the final payments and reconciles the order before it is posted.
     */
    protected function saveLinesAndComputeTotals(Order $order, array $obj, ?PosSetting $pos_setting)
    {
        $enable_discount = (bool) ($pos_setting->enable_discount ?? true);
        $discount_level = $pos_setting->discount_level ?? 'both';
        $line_discount_allowed = $enable_discount && in_array($discount_level, ['line', 'both'], true);
        $order_discount_allowed = $enable_discount && in_array($discount_level, ['order', 'both'], true);

        $payment_method_ids = collect($obj['payments'] ?? [])->pluck('payment_method_id')->filter()->values()->all();
        $tax_percent = $this->resolveTaxPercent($order->business_id, $payment_method_ids);

        $subtotal = 0;
        $line_discount_total = 0;
        $tax_amount_total = 0;
        $has_line = false;

        $products = $obj['products'] ?? [];

        // Whether a line may price under a different Sale Type than the
        // order's default - when off, every line is forced onto the order's
        // Sale Type server-side regardless of what the client sent.
        $allow_mixed_sale_types = (bool) ($pos_setting->allow_mixed_sale_types ?? true);

        // Master switch for manual price editing - when off, a manually sent
        // unit_price is ignored server-side even if the controller let it
        // through (e.g. a stale/tampered request), so this is the
        // authoritative enforcement point, same as allow_mixed_sale_types
        // above. The order.price.change permission is what gates the price
        // field being editable in the UI in the first place.
        $allow_price_change_in_cart = (bool) ($pos_setting->allow_price_change_in_cart ?? false);

        // Each line is priced by whichever Sale Type applies to it (its own
        // override, else the order's default) - group variation IDs by that
        // sale type first so VariationPricingService is called once per
        // distinct sale type instead of once per line.
        $variation_ids_by_sale_type = [];

        foreach ($products as $line) {
            if ((float) ($line['quantity'] ?? 0) <= 0 || empty($line['product_variation_id'])) {
                continue;
            }

            $line_sale_type_id = $allow_mixed_sale_types ? ($line['sale_type_id'] ?? $order->sale_type_id) : $order->sale_type_id;
            $variation_ids_by_sale_type[$line_sale_type_id ?? ''][] = $line['product_variation_id'];
        }

        $resolved_prices = [];

        foreach ($variation_ids_by_sale_type as $sale_type_id => $variation_ids) {
            $sale_type_id = $sale_type_id === '' ? null : $sale_type_id;

            foreach ($this->pricing_engine->resolveBulk($variation_ids, $sale_type_id) as $variation_id => $result) {
                $resolved_prices[$variation_id . '|' . $sale_type_id] = $result;
            }
        }

        // Selling below the Minimum Selling Price requires price editing to
        // be enabled ($allow_price_change_in_cart, computed above) AND the
        // separate allow_price_below_minimum business setting to be on -
        // matches canOverrideMinPrice() in pos-screen.js. The
        // override_minimum_price flag itself is already zeroed by the
        // controller when the acting user lacks order.price.override-minimum
        // (OrderController::store()), so this is the final authoritative AND
        // of all three conditions.
        $allow_below_minimum = $allow_price_change_in_cart
            && (bool) ($pos_setting->allow_price_below_minimum ?? false)
            && !empty($obj['override_minimum_price']);

        foreach ($products as $line) {
            $quantity = (float) ($line['quantity'] ?? 0);

            if ($quantity <= 0) {
                continue;
            }

            $has_line = true;

            $variation = ProductVariation::findOrFail($line['product_variation_id']);
            $line_sale_type_id = $allow_mixed_sale_types ? ($line['sale_type_id'] ?? $order->sale_type_id) : $order->sale_type_id;
            $resolved = $resolved_prices[$variation->product_variation_id . '|' . $line_sale_type_id] ?? null;

            $conversion_factor = 1;
            $unit_id = $line['unit_id'] ?? $variation->base_unit_id;

            if (!empty($line['product_variation_unit_conversion_id'])) {
                $conversion = ProductVariationUnitConversion::find($line['product_variation_unit_conversion_id']);

                if ($conversion) {
                    $conversion_factor = (float) $conversion->conversion_factor > 0 ? (float) $conversion->conversion_factor : 1;
                    $unit_id = $conversion->to_unit_id;
                }
            }

            $base_quantity = $quantity * $conversion_factor;

            // A custom unit_price is only honored if explicitly provided - the
            // caller (controller) is responsible for only allowing that when the
            // cashier holds the order.price.change permission; the service itself
            // does not perform permission checks (this codebase's convention
            // keeps authorization in controllers). Any other line is priced by
            // the VariationPricingService result computed above, keyed by this
            // line's own Sale Type (or the order's default Sale Type).
            $manual_override = $allow_price_change_in_cart && isset($line['unit_price']) && (float) $line['unit_price'] > 0;

            $unit_price = $manual_override
                ? (float) $line['unit_price']
                : (float) ($resolved['price'] ?? $variation->sale_price);

            $line_subtotal = round($base_quantity * $unit_price, 3);

            // The line's own discount % defaults to whatever the pricing engine
            // resolved for this Sale Type (0 if the variation's discount
            // doesn't apply to it) - an authorized user can still send a
            // different value, exactly as before this feature existed.
            $default_discount = $resolved['discount_percentage'] ?? 0.0;
            $discount_percent = $line_discount_allowed
                ? max(0, min(100, (float) ($line['discount'] ?? $default_discount)))
                : 0;
            $line_discount_amount = round($line_subtotal * $discount_percent / 100, 3);

            $taxable = $line_subtotal - $line_discount_amount;

            $minimum_selling_price = $resolved['minimum_selling_price'] ?? $variation->minimum_selling_price;
            $net_unit_price = $base_quantity > 0 ? $taxable / $base_quantity : $unit_price;

            if (!$allow_below_minimum && $minimum_selling_price !== null && $this->pricing_engine->isBelowFloor((float) $minimum_selling_price, $net_unit_price)) {
                throw new Exception(sprintf(
                    'The price for "%s" (%s) is below its minimum selling price of %s.',
                    $variation->name ?: $variation->sku,
                    currency($net_unit_price),
                    currency($minimum_selling_price)
                ));
            }

            $line_tax_amount = round($taxable * $tax_percent / 100, 3);
            $line_total = $taxable + $line_tax_amount;

            $subtotal += $line_subtotal;
            $line_discount_total += $line_discount_amount;
            $tax_amount_total += $line_tax_amount;

            $this->model_order_detail->create([
                'order_detail_id' => generateUuid(),
                'order_id' => $order->order_id,
                'product_id' => $variation->product_id,
                'product_variation_id' => $variation->product_variation_id,
                'product_variation_unit_conversion_id' => $line['product_variation_unit_conversion_id'] ?? null,
                'unit_id' => $unit_id,
                'quantity' => $quantity,
                'conversion_factor' => $conversion_factor,
                'base_quantity' => $base_quantity,
                'unit_price' => $unit_price,
                'sale_type_id' => $line_sale_type_id,
                'final_unit_price' => round($net_unit_price, 3),
                'discount' => $discount_percent,
                'discount_amount' => $line_discount_amount,
                'tax' => $tax_percent,
                'tax_amount' => $line_tax_amount,
                'subtotal' => $line_subtotal,
                'total' => $line_total,
                'cost_price' => 0,
                'notes' => $line['notes'] ?? null,
                'createdby_id' => Auth::id(),
                'date_created' => now(),
            ]);
        }

        if (!$has_line) {
            throw new Exception('An order must contain at least one product with a quantity greater than zero.');
        }

        $post_line_discount_subtotal = $subtotal - $line_discount_total;

        $order_discount_amount = 0;
        $order_discount_display = 0;
        $discount_id = null;
        $voucher_id = null;

        if ($order_discount_allowed && !empty($obj['discount_id'])) {
            $discount = Discount::where('discount_id', $obj['discount_id'])
                ->where('business_id', $order->business_id)
                ->where('status', Status::ACTIVE)
                ->where('is_deleted', 0)
                ->first();

            if (!$discount) {
                throw new Exception('The selected discount is not available.');
            }

            $order_discount_amount = $discount->type === 'percent'
                ? round($post_line_discount_subtotal * $discount->value / 100, 3)
                : min((float) $discount->value, $post_line_discount_subtotal);

            $order_discount_display = $discount->type === 'percent' ? $discount->value : 0;
            $discount_id = $discount->discount_id;
        }

        $voucher_discount_amount = 0;

        if (!empty($obj['voucher_code']) || !empty($obj['voucher_id'])) {
            $voucher = !empty($obj['voucher_id'])
                ? Voucher::with(['products', 'categories', 'users', 'orderTypes', 'branches'])->find($obj['voucher_id'])
                : $this->voucher_service->findByCode($obj['voucher_code'], $order->business_id);

            if (!$voucher) {
                throw new Exception('The voucher/coupon code was not found.');
            }

            $eligibility = $this->voucher_service->isApplicable($voucher, [
                'user_id' => $order->user_id,
                'order_type_id' => $order->order_type_id,
                'branch_id' => $order->branch_id,
                'order_amount' => $post_line_discount_subtotal - $order_discount_amount,
            ]);

            if (!$eligibility['eligible']) {
                throw new Exception($eligibility['reason'] ?? 'The selected voucher is not applicable to this order.');
            }

            $remaining = $post_line_discount_subtotal - $order_discount_amount;
            $voucher_discount_amount = $voucher->type === 'percent'
                ? round($remaining * $voucher->value / 100, 3)
                : min((float) $voucher->value, $remaining);

            $voucher_id = $voucher->voucher_id;
        }

        $discount_amount = $line_discount_total + $order_discount_amount + $voucher_discount_amount;
        $total = $subtotal - $discount_amount + $tax_amount_total;

        return [
            'subtotal' => $subtotal,
            'discount_amount' => round($discount_amount, 3),
            'discount_display' => $order_discount_display,
            'tax_amount' => round($tax_amount_total, 3),
            'tax_display' => $tax_percent,
            'total' => round($total, 3),
            'discount_id' => $discount_id,
            'voucher_id' => $voucher_id,
            'voucher_discount_amount' => round($voucher_discount_amount, 3),
        ];
    }

    /**
     * Re-resolves the tax percent from the order's final, confirmed payments
     * and reconciles the order/order_details if it differs from the working
     * estimate used when the draft was last saved (e.g. the cart was built
     * assuming cash but the customer paid by card). A no-op when the rate is
     * unchanged - which is the common case once payments were already known
     * at save() time.
     */
    protected function recomputeOrderTax(Order $order, $payments)
    {
        $payment_method_ids = $payments->pluck('payment_method_id')->filter()->values()->all();
        $tax_percent = $this->resolveTaxPercent($order->business_id, $payment_method_ids);

        if (abs((float) $order->tax - $tax_percent) < 0.0001) {
            return;
        }

        $tax_amount_total = 0;

        foreach ($order->details as $detail) {
            $taxable = (float) $detail->subtotal - (float) $detail->discount_amount;
            $line_tax_amount = round($taxable * $tax_percent / 100, 3);

            $detail->update([
                'tax' => $tax_percent,
                'tax_amount' => $line_tax_amount,
                'total' => round($taxable + $line_tax_amount, 3),
            ]);

            $tax_amount_total += $line_tax_amount;
        }

        $tax_amount_total = round($tax_amount_total, 3);
        $new_total = round((float) $order->subtotal - (float) $order->discount_amount + $tax_amount_total, 3);

        $order->update([
            'tax' => $tax_percent,
            'tax_amount' => $tax_amount_total,
            'total' => $new_total,
        ]);
    }

    protected function saveLinePayments($order_id, array $payments)
    {
        OrderPayment::where('order_id', $order_id)->delete();

        foreach ($payments as $payment) {
            if (empty($payment['payment_method_id']) || (float) ($payment['amount'] ?? 0) <= 0) {
                continue;
            }

            $this->model_order_payment->create([
                'order_payment_id' => generateUuid(),
                'order_id' => $order_id,
                'payment_method_id' => $payment['payment_method_id'],
                'amount' => $payment['amount'],
                'reference_no' => $payment['reference_no'] ?? null,
                'is_deleted' => 0,
                'createdby_id' => Auth::id(),
                'date_created' => now(),
            ]);
        }
    }

    public function hold($order_id)
    {
        return $this->transitionStatus($order_id, ['draft'], 'hold', 'Order held');
    }

    public function resume($order_id)
    {
        return $this->transitionStatus($order_id, ['hold'], 'draft', 'Order resumed');
    }

    public function reopen($order_id)
    {
        return $this->transitionStatus($order_id, ['cancelled'], 'draft', 'Order reopened');
    }

    public function cancel($order_id)
    {
        $order = $this->model_order->getModel()::findOrFail($order_id);

        if ($order->status === Status::POSTED) {
            throw new Exception('A posted order cannot be cancelled directly - void it instead.');
        }

        if (!in_array($order->status, ['draft', 'hold'], true)) {
            throw new Exception('Only draft or held orders can be cancelled.');
        }

        return $this->transitionStatus($order_id, ['draft', 'hold'], 'cancelled', 'Order cancelled');
    }

    /**
     * Records the optional due_date/note from the POS Credit Payment popup
     * (shown after a Credit-type sale completes) - a plain field update,
     * deliberately kept separate from post()'s JV-generating transaction.
     */
    public function updateCreditInfo(array $obj)
    {
        $order = $this->model_order->getModel()::findOrFail($obj['order_id']);

        if ($order->business_id !== (Auth::user()->business_id ?? null)) {
            throw new Exception('Order not found.');
        }

        $order->update([
            'due_date' => $obj['due_date'] ?? null,
            'notes' => $obj['notes'] ?? $order->notes,
            'updatedby_id' => Auth::id(),
            'date_updated' => now(),
        ]);

        return $order;
    }

    protected function transitionStatus($order_id, array $from_allowed, $to_status, $note)
    {
        DB::beginTransaction();

        try {
            $order = $this->model_order->getModel()::findOrFail($order_id);

            if (!in_array($order->status, $from_allowed, true)) {
                throw new Exception('This order cannot transition from "' . $order->status . '" to "' . $to_status . '".');
            }

            $from_status = $order->status;

            $order->update([
                'status' => $to_status,
                'updatedby_id' => Auth::id(),
                'date_updated' => now(),
            ]);

            $this->recordStatusHistory($order_id, $from_status, $to_status, $note);

            DB::commit();

            return $this->getById($order_id);
        } catch (Exception $e) {
            DB::rollBack();

            throw $e;
        }
    }

    protected function recordStatusHistory($order_id, $from_status, $to_status, $reason = null)
    {
        OrderStatusHistory::create([
            'order_status_history_id' => generateUuid(),
            'order_id' => $order_id,
            'from_status' => $from_status,
            'to_status' => $to_status,
            'reason' => $reason,
            'changedby_id' => Auth::id(),
            'date_created' => now(),
        ]);

        $action = $from_status === null ? 'created' : ($to_status === Status::POSTED ? 'posted' : 'status_changed');

        $this->logActivity(
            'order',
            $order_id,
            $action,
            $from_status !== null ? ['status' => $from_status] : null,
            ['status' => $to_status],
            $reason ?? ('Order status changed to ' . $to_status)
        );

        $business_id = Auth::user()?->business_id;
        $notification_setting = $business_id ? NotificationSetting::where('business_id', $business_id)->first() : null;

        if (!$notification_setting || $notification_setting->order_status_alert_enabled) {
            $this->notify(
                'order_status',
                null,
                null,
                'Order Status Updated',
                $reason ?? ('Order status changed to ' . $to_status),
                'order',
                $order_id,
                route('order.show', $order_id),
                ['from_status' => $from_status, 'to_status' => $to_status],
                $to_status
            );
        }
    }

    /**
     * Posts a draft/held order: validates payments cover the order total (a
     * cash payment may tender more than the total, in which case the excess
     * becomes change_amount and is never booked as revenue), then atomically
     * posts the Sales/Tax/Discount/COGS/Payment journal entry, decrements
     * stock per line, redeems any applied voucher, and marks the order
     * posted. Idempotent - a no-op if an active JournalEntry already exists
     * for this order (mirrors PurchaseReturnService::applyPurchaseReturnPosting()).
     */
    public function post($obj)
    {
        DB::beginTransaction();

        try {
            $order = $this->model_order->getModel()::with(['details.product', 'payments'])->findOrFail($obj['order_id']);

            $existing = JournalEntry::where('source_type', JournalSourceTypes::POS_SALE)
                ->where('source_id', $order->order_id)
                ->where('is_deleted', 0)
                ->exists();

            if ($existing) {
                DB::commit();

                return $this->getById($order->order_id);
            }

            if (!in_array($order->status, ['draft', 'hold'], true)) {
                throw new Exception('Only a draft or held order can be posted.');
            }

            // The register session is only relevant for POS-originated orders -
            // Website/Mobile App/API orders have no register_session_id at all.
            if (!empty($order->register_session_id)) {
                $session = PosRegisterSession::find($order->register_session_id);

                if (!$session || $session->status !== 'open') {
                    throw new Exception('The register session for this order is not open.');
                }
            }

            if (!empty($obj['payments'])) {
                $this->saveLinePayments($order->order_id, $obj['payments']);
                $order->refresh();
            }

            $payments = OrderPayment::where('order_id', $order->order_id)->where('is_deleted', 0)->get();

            if ($payments->isEmpty()) {
                throw new Exception('At least one payment is required to complete the sale.');
            }

            // The rate used while the cart was being built was only ever a working
            // estimate (payments aren't known until checkout) - re-resolve it now
            // that the final payment methods are known and reconcile the order
            // before anything is posted to the ledger.
            $this->recomputeOrderTax($order, $payments);

            $order_total = round((float) $order->total, 2);

            // Cash may be tendered above the order total (change); every other
            // method must exactly cover its allocated portion - so only the cash
            // leg is allowed to carry the surplus that becomes change_amount.
            $non_cash_total = 0;
            $cash_tendered = 0;
            $has_cash = false;

            foreach ($payments as $payment) {
                $method = PaymentMethod::find($payment->payment_method_id);

                if (!$method) {
                    throw new Exception('One of the selected payment methods no longer exists.');
                }

                if ($method->type === 'cash') {
                    $has_cash = true;
                    $cash_tendered += (float) $payment->amount;
                } else {
                    $non_cash_total += (float) $payment->amount;
                }
            }

            $non_cash_total = round($non_cash_total, 2);

            if ($non_cash_total > $order_total + 0.01) {
                throw new Exception('Non-cash payment total (' . $non_cash_total . ') exceeds the order total (' . $order_total . ').');
            }

            $cash_required = round($order_total - $non_cash_total, 2);
            $change_amount = 0;
            $cash_applied = $cash_tendered;

            if ($cash_required > 0.01) {
                if (round($cash_tendered, 2) < $cash_required - 0.01) {
                    $payments_total = round($non_cash_total + $cash_tendered, 2);
                    throw new Exception('Payment total (' . $payments_total . ') does not cover the order total (' . $order_total . ').');
                }

                $change_amount = round($cash_tendered - $cash_required, 3);
                $cash_applied = $cash_required;
            } elseif ($cash_tendered > 0) {
                // No cash was actually needed to cover the total (fully paid by
                // other methods) but a cash line was still submitted - the whole
                // amount is handed straight back as change.
                $change_amount = round($cash_tendered, 3);
                $cash_applied = 0;
            }

            if ($change_amount > 0 && !$has_cash) {
                throw new Exception('Change can only be given against a cash payment.');
            }

            $paid_amount = round($non_cash_total + $cash_tendered, 3);
            // Net amount actually applied to the sale (excludes change handed
            // back) - this is what the JV/order_payments must reconcile to.
            $applied_total = round($non_cash_total + $cash_applied, 2);

            if (abs($applied_total - $order_total) > 0.01) {
                throw new Exception('Payment total (' . $applied_total . ') does not match the order total (' . $order_total . ').');
            }

            $accounting_setting = AccountingSetting::where('business_id', $order->business_id)->first();

            if (!$accounting_setting || !$accounting_setting->enable_accounting) {
                throw new Exception('Accounting is not enabled for this business. Please configure Accounting Settings before completing sales.');
            }

            app(\App\Services\Concrete\Admin\AccountingPeriodService::class)->assertPostable($order->business_id, now());

            if (empty($accounting_setting->default_sale_account_id)) {
                throw new Exception('Sale Account is not configured in Accounting Settings.');
            }

            if ((float) $order->tax_amount > 0 && empty($accounting_setting->default_tax_account_id)) {
                throw new Exception('Tax Account is not configured in Accounting Settings.');
            }

            if ((float) $order->discount_amount > 0 && empty($accounting_setting->default_discount_account_id)) {
                throw new Exception('Discount Account is not configured in Accounting Settings.');
            }

            if (empty($accounting_setting->default_inventory_account_id) || empty($accounting_setting->default_cogs_account_id)) {
                throw new Exception('Inventory and COGS Accounts must be configured in Accounting Settings before completing sales.');
            }

            $has_credit_payment = false;

            foreach ($payments as $payment) {
                $method = PaymentMethod::find($payment->payment_method_id);

                if ($method->type === 'credit') {
                    $has_credit_payment = true;
                    continue;
                }

                if ($method->type !== 'cash' && empty($method->account_id)) {
                    throw new Exception('Payment method "' . $method->name . '" is not mapped to an account.');
                }
            }

            if ($has_cash && empty($accounting_setting->default_cash_account_id)) {
                throw new Exception('Cash Account is not configured in Accounting Settings.');
            }

            if ($has_credit_payment) {
                if (empty($accounting_setting->default_customer_account_id)) {
                    throw new Exception('Customer Receivable Account is not configured in Accounting Settings, required for credit payments.');
                }

                $this->validateCreditLimit($order, $payments);
            }

            $limit = checkPackageLimit('sales');

            if (!$limit['status']) {
                throw new Exception($limit['message']);
            }

            $journal = Journal::where('short', 'SV')->where('is_deleted', 0)->first();

            if (!$journal) {
                throw new Exception('No "Sale Voucher" journal category found. Please run the POS journal setup migration.');
            }

            $entry_no = generateJVNum($journal->journal_id);

            $journal_entry = JournalEntry::create([
                'journal_entry_id' => generateUuid(),
                'journal_id' => $journal->journal_id,
                'business_id' => $order->business_id,
                'branch_id' => $order->branch_id,
                'entry_no' => $entry_no,
                'reference_no' => 'ORD-' . $order->daily_order_id,
                'entry_date' => now(),
                'description' => 'Auto-generated sale voucher for order #' . $order->daily_order_id,
                'source_type' => JournalSourceTypes::POS_SALE,
                'source_id' => $order->order_id,
                'status' => Status::POSTED,
                'postedby_id' => Auth::id(),
                'date_posted' => now(),
                'createdby_id' => Auth::id(),
                'date_created' => now(),
            ]);

            // Debit: each payment's mapped account (Credit-type payments debit the
            // shared customer receivable account, tagged with user_id - the
            // exact technique SupplierPaymentService uses for the payable side,
            // and what CustomerService::getCustomerLedger() sums back up later).
            // Cash is debited net of change handed back - change is a cash-drawer
            // movement only and is never booked as sales revenue.
            foreach ($payments as $payment) {
                $method = PaymentMethod::find($payment->payment_method_id);

                if ($method->type === 'cash') {
                    $debit = $cash_applied;
                    $account_id = $accounting_setting->default_cash_account_id;
                } elseif ($method->type === 'credit') {
                    $debit = (float) $payment->amount;
                    $account_id = $accounting_setting->default_customer_account_id;
                } else {
                    $debit = (float) $payment->amount;
                    $account_id = $method->account_id;
                }

                if ($debit <= 0) {
                    continue;
                }

                JournalEntryDetail::create([
                    'journal_entry_detail_id' => generateUuid(),
                    'journal_entry_id' => $journal_entry->journal_entry_id,
                    'account_id' => $account_id,
                    'debit' => $debit,
                    'credit' => 0,
                    'user_id' => $method->type === 'credit' ? $order->user_id : null,
                    'description' => 'Order #' . $order->daily_order_id . ' - ' . $method->name,
                ]);
            }

            // Debit: discount given (contra-revenue).
            if ((float) $order->discount_amount > 0) {
                JournalEntryDetail::create([
                    'journal_entry_detail_id' => generateUuid(),
                    'journal_entry_id' => $journal_entry->journal_entry_id,
                    'account_id' => $accounting_setting->default_discount_account_id,
                    'debit' => $order->discount_amount,
                    'credit' => 0,
                    'description' => 'Order #' . $order->daily_order_id . ' - Discount',
                ]);
            }

            // Credit: gross sales revenue (subtotal, before discount).
            JournalEntryDetail::create([
                'journal_entry_detail_id' => generateUuid(),
                'journal_entry_id' => $journal_entry->journal_entry_id,
                'account_id' => $accounting_setting->default_sale_account_id,
                'debit' => 0,
                'credit' => $order->subtotal,
                'description' => 'Order #' . $order->daily_order_id,
            ]);

            // Credit: tax collected.
            if ((float) $order->tax_amount > 0) {
                JournalEntryDetail::create([
                    'journal_entry_detail_id' => generateUuid(),
                    'journal_entry_id' => $journal_entry->journal_entry_id,
                    'account_id' => $accounting_setting->default_tax_account_id,
                    'debit' => 0,
                    'credit' => $order->tax_amount,
                    'description' => 'Order #' . $order->daily_order_id . ' - Tax',
                ]);
            }

            // applied_total was already validated to equal order_total above
            // (within a 1-cent tolerance since cash tendering is never sub-cent
            // precise), but every leg above was posted from the order's stored
            // total/subtotal/tax/discount, not the literal applied amounts - so
            // any such difference must be absorbed into a round-off leg or the
            // JV would be left unbalanced by that same fraction of a cent.
            $rounding_diff = round($applied_total - $order_total, 3);

            if (abs($rounding_diff) > 0.0001) {
                if (empty($accounting_setting->default_round_off_account_id)) {
                    throw new Exception('Round Off Account is not configured in Accounting Settings, required to reconcile a rounding difference of ' . $rounding_diff . '.');
                }

                JournalEntryDetail::create([
                    'journal_entry_detail_id' => generateUuid(),
                    'journal_entry_id' => $journal_entry->journal_entry_id,
                    'account_id' => $accounting_setting->default_round_off_account_id,
                    'debit' => $rounding_diff < 0 ? abs($rounding_diff) : 0,
                    'credit' => $rounding_diff > 0 ? $rounding_diff : 0,
                    'description' => 'Order #' . $order->daily_order_id . ' - Rounding',
                ]);
            }

            // Stock sufficiency check - tracked products only. Runs as a
            // separate pass before any decrement below, so a shortfall on
            // any line aborts the whole sale (via the rollBack() in the
            // catch below) before any earlier line's stock has been
            // mutated. Mirrors TransferNoteService's insufficient-stock
            // check style/messaging.
            foreach ($order->details as $detail) {
                $product = $detail->product;

                if (!$product || !$product->is_track_stock) {
                    continue;
                }

                $available_qty = (float) (ProductVariationStock::where('business_id', $order->business_id)
                    ->where('warehouse_id', $order->warehouse_id)
                    ->where('product_id', $detail->product_id)
                    ->where('product_variation_id', $detail->product_variation_id)
                    ->value('quantity') ?? 0);

                if ((float) $detail->base_quantity > $available_qty) {
                    throw new Exception('Insufficient stock for "' . ($product->name ?? 'product') . '". Available: ' . $available_qty . ', required: ' . $detail->base_quantity . '.');
                }
            }

            // Per line: snapshot cost, decrement stock, write the stock
            // transaction, and accumulate the COGS total.
            $total_cost = 0;

            foreach ($order->details as $detail) {
                $stock = ProductVariationStock::where('business_id', $order->business_id)
                    ->where('warehouse_id', $order->warehouse_id)
                    ->where('product_id', $detail->product_id)
                    ->where('product_variation_id', $detail->product_variation_id)
                    ->first();

                $existing_qty = $stock->quantity ?? 0;
                $existing_avg = $stock->avg_price ?? 0;
                $new_qty = $existing_qty - $detail->base_quantity;
                $line_cost = round($detail->base_quantity * $existing_avg, 3);
                $total_cost += $line_cost;

                $detail->update(['cost_price' => $existing_avg]);

                if ($stock) {
                    $stock->update(['quantity' => $new_qty]);
                } else {
                    $stock = ProductVariationStock::create([
                        'product_variation_stock_id' => generateUuid(),
                        'business_id' => $order->business_id,
                        'warehouse_id' => $order->warehouse_id,
                        'product_id' => $detail->product_id,
                        'product_variation_id' => $detail->product_variation_id,
                        'quantity' => $new_qty,
                        'avg_price' => 0,
                        'status' => 'active',
                        'createdby_id' => Auth::id(),
                        'date_created' => now(),
                    ]);
                }

                ProductVariationStockTransaction::create([
                    'product_variation_stock_transaction_id' => generateUuid(),
                    'transaction_date' => now(),
                    'transaction_type' => TransactionType::SALE,
                    'business_id' => $order->business_id,
                    'product_id' => $detail->product_id,
                    'product_variation_id' => $detail->product_variation_id,
                    'warehouse_id' => $order->warehouse_id,
                    'unit_id' => $detail->unit_id,
                    'product_variation_unit_conversion_id' => $detail->product_variation_unit_conversion_id,
                    'conversion_factor' => $detail->conversion_factor,
                    'quantity' => $detail->quantity,
                    'base_quantity' => $detail->base_quantity,
                    'unit_price' => $detail->unit_price,
                    'total_price' => $line_cost,
                    'quantity_after' => $new_qty,
                    'avg_price_after' => $existing_avg,
                    'reference_id' => $order->order_id,
                    'reference_type' => ReferenceType::SALE,
                    'remarks' => 'Auto-created on posting of order #' . $order->daily_order_id,
                    'createdby_id' => Auth::id(),
                    'date_created' => now(),
                ]);
            }

            if ($total_cost > 0) {
                JournalEntryDetail::create([
                    'journal_entry_detail_id' => generateUuid(),
                    'journal_entry_id' => $journal_entry->journal_entry_id,
                    'account_id' => $accounting_setting->default_cogs_account_id,
                    'debit' => $total_cost,
                    'credit' => 0,
                    'description' => 'Order #' . $order->daily_order_id . ' - COGS',
                ]);

                JournalEntryDetail::create([
                    'journal_entry_detail_id' => generateUuid(),
                    'journal_entry_id' => $journal_entry->journal_entry_id,
                    'account_id' => $accounting_setting->default_inventory_account_id,
                    'debit' => 0,
                    'credit' => $total_cost,
                    'description' => 'Order #' . $order->daily_order_id . ' - Inventory',
                ]);
            }

            if (!empty($order->voucher_id)) {
                $this->voucher_service->redeem(
                    $order->voucher_id,
                    $order->order_id,
                    $order->user_id,
                    max(0, $order->voucher_discount_amount)
                );
            }

            $this->recordStatusHistory($order->order_id, $order->status, Status::POSTED, 'Order posted');

            $order->update([
                'paid_amount' => $paid_amount,
                'change_amount' => $change_amount,
                'status' => Status::POSTED,
                'updatedby_id' => Auth::id(),
                'date_updated' => now(),
            ]);

            DB::commit();

            return $this->getById($order->order_id);
        } catch (Exception $e) {
            DB::rollBack();

            throw $e;
        }
    }

    protected function validateCreditLimit(Order $order, $payments)
    {
        $credit_amount = (float) $payments->where('payment_method_id', '!=', null)
            ->filter(function ($payment) {
                $method = PaymentMethod::find($payment->payment_method_id);

                return $method && $method->type === 'credit';
            })
            ->sum('amount');

        if ($credit_amount <= 0 || empty($order->user_id)) {
            return;
        }

        $customer_setting = CustomerSetting::where('business_id', $order->business_id)->first();

        if (!$customer_setting || !$customer_setting->enable_credit_limit) {
            return;
        }

        $customer = CustomerProfile::where('user_id', $order->user_id)
            ->where('business_id', $order->business_id)
            ->first();

        if (!$customer || empty($customer->credit_limit)) {
            return;
        }

        $ledger = $this->customer_service->getCustomerLedger($order->user_id, $order->business_id);
        $outstanding = max(0, -1 * (float) $ledger['raw_balance']);

        if (($outstanding + $credit_amount) > (float) $customer->credit_limit) {
            throw new Exception('This sale would exceed the customer\'s available credit limit.');
        }
    }

    /**
     * Reverses a posted order exactly once: soft-deletes the linked
     * JournalEntry and ProductVariationStockTransaction rows, replays the
     * remaining ledger via ProductVariationStockService::recomputeLedger()
     * for every affected product/variation/warehouse, reverses any voucher
     * redemption, and marks the order void. Idempotent - if no active
     * JournalEntry/stock transactions remain, this is a safe no-op (mirrors
     * PurchaseReturnService::reversePurchaseReturnPosting()). Posted orders
     * are NEVER hard-deleted - void() is the only way to undo one.
     */
    public function void($obj)
    {
        DB::beginTransaction();

        try {
            $order = $this->model_order->getModel()::with(['details'])->findOrFail($obj['order_id']);

            if ($order->status !== Status::POSTED) {
                throw new Exception('Only a posted order can be voided.');
            }

            $journal_entry = JournalEntry::where('source_type', JournalSourceTypes::POS_SALE)
                ->where('source_id', $order->order_id)
                ->where('is_deleted', 0)
                ->first();

            if ($journal_entry) {
                $journal_entry->update([
                    'is_deleted' => 1,
                    'deletedby_id' => Auth::id(),
                    'date_deleted' => now(),
                ]);
            }

            $stock_transactions = ProductVariationStockTransaction::where('reference_type', ReferenceType::SALE)
                ->where('reference_id', $order->order_id)
                ->where('is_deleted', 0)
                ->get();

            if ($stock_transactions->isNotEmpty()) {
                $stock_transactions->each(function ($transaction) {
                    $transaction->update([
                        'is_deleted' => 1,
                        'deletedby_id' => Auth::id(),
                        'date_deleted' => now(),
                    ]);
                });

                $affected = $stock_transactions->unique(function ($transaction) {
                    return $transaction->business_id . '|' . $transaction->warehouse_id . '|' .
                        $transaction->product_id . '|' . $transaction->product_variation_id;
                });

                foreach ($affected as $transaction) {
                    $this->stock_service->recomputeLedger(
                        $transaction->business_id,
                        $transaction->warehouse_id,
                        $transaction->product_id,
                        $transaction->product_variation_id
                    );
                }
            }

            if (!empty($order->voucher_id)) {
                $this->voucher_service->reverseRedemption($order->order_id);
            }

            $this->recordStatusHistory($order->order_id, $order->status, 'void', $obj['reason'] ?? 'Order voided');

            $order->update([
                'status' => 'void',
                'updatedby_id' => Auth::id(),
                'date_updated' => now(),
            ]);

            DB::commit();

            return $this->getById($order->order_id);
        } catch (Exception $e) {
            DB::rollBack();

            throw $e;
        }
    }

    /**
     * Non-posted orders (draft/hold/cancelled) may be soft-deleted directly.
     * Posted orders can never be deleted - only void() may undo them.
     */
    public function delete($order_id)
    {
        $order = $this->model_order->getModel()::findOrFail($order_id);

        if (in_array($order->status, [Status::POSTED, 'void', 'returned'], true)) {
            throw new Exception('Posted orders cannot be deleted. Void the order instead.');
        }

        $result = $this->model_order->update([
            'is_deleted' => 1,
            'deletedby_id' => Auth::id(),
            'date_deleted' => now(),
        ], $order_id);

        $this->logActivity('order', $order_id, 'deleted', ['status' => $order->status], null, 'Order deleted');

        return $result;
    }

    public function searchProducts($obj)
    {
        $business_id = $obj['business_id'] ?? Auth::user()->business_id;
        $term = $obj['term'] ?? '';

        $query = ProductVariation::with(['product', 'unit', 'saleUnit', 'productVariationUnitConversion.toUnit'])
            ->where('business_id', $business_id)
            ->where('is_deleted', 0)
            ->where('status', Status::ACTIVE);

        if (!empty($term)) {
            $query->where(function ($q) use ($term) {
                $q->where('sku', $term)
                    ->orWhere('barcode', $term)
                    ->orWhere('name', 'like', '%' . $term . '%')
                    ->orWhereHas('product', function ($pq) use ($term) {
                        $pq->where('name', 'like', '%' . $term . '%');
                    });
            });
        }

        $variations = $query->limit(30)->get();

        $this->applyResolvedPricing($variations, $obj);

        return $variations;
    }

    /**
     * Re-resolves prices for an already-known list of variation IDs against a
     * given Sale Type - used by the POS screen to re-price the current cart
     * when the order-level Sale Type changes, or to price a single line when
     * the cashier overrides that line's Sale Type, without re-running product
     * search.
     */
    public function resolvePrices($obj)
    {
        return $this->pricing_engine->resolveBulk($obj['product_variation_ids'] ?? [], $obj['sale_type_id'] ?? null);
    }

    /**
     * Attaches resolved_price/resolved_discount_percentage/minimum_selling_price
     * onto each ProductVariation in the given collection, using
     * VariationPricingService so POS search/browse previews the exact same
     * price the eventual save() will stamp. Client (pos-screen.js) falls back
     * to sale_price if these are ever absent.
     */
    protected function applyResolvedPricing($variations, array $obj): void
    {
        if ($variations->isEmpty()) {
            return;
        }

        $sale_type_id = $obj['sale_type_id'] ?? null;

        $resolved = $this->pricing_engine->resolveBulk($variations->pluck('product_variation_id')->all(), $sale_type_id);

        foreach ($variations as $variation) {
            $r = $resolved[$variation->product_variation_id] ?? null;

            $variation->setAttribute('resolved_price', $r['price'] ?? (float) $variation->sale_price);
            $variation->setAttribute('resolved_discount_percentage', $r['discount_percentage'] ?? 0.0);
            $variation->setAttribute('minimum_selling_price', $r['minimum_selling_price'] ?? $variation->minimum_selling_price);
        }
    }

    /**
     * Category-wise product browsing for the POS screen - a separate,
     * product-grouped listing (image, nested variations/units) rather than
     * an extension of searchProducts()'s flat ProductVariation shape, which
     * is purpose-built for instant-add search/scan instead.
     */
    public function getProductsByCategory($obj)
    {
        $business_id = $obj['business_id'] ?? Auth::user()->business_id;
        $category_id = $obj['category_id'] ?? null;

        $query = Product::with([
                'productVariations.unit',
                'productVariations.saleUnit',
                'productVariations.productVariationUnitConversion.toUnit',
                'productImages' => function ($q) {
                    $q->orderBy('is_default', 'desc')->orderBy('sorting');
                },
            ])
            ->where('business_id', $business_id)
            ->where('is_deleted', 0)
            ->where('status', Status::ACTIVE)
            ->where('is_pos_visible', 1);

        if (!empty($category_id)) {
            $query->where('category_id', $category_id);
        }

        $products = $query->orderBy('name')->limit(60)->get();

        $all_variations = $products->flatMap(fn ($product) => $product->productVariations);
        $this->applyResolvedPricing($all_variations, $obj);

        return $products;
    }
}
