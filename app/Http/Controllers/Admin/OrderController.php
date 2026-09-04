<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RoleNames;
use App\Enums\Message;
use App\Enums\Status;
use App\Http\Controllers\Concerns\HandlesImportExport;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\PosRegister;
use App\Models\User;
use App\Services\Concrete\Admin\BranchService;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\CustomerService;
use App\Services\Concrete\Admin\DocumentSendLogService;
use App\Services\Concrete\Admin\OrderService;
use App\Services\Concrete\Admin\OrderSourceService;
use App\Services\Concrete\Admin\OrderTypeService;
use App\Services\Concrete\Admin\PaymentMethodService;
use App\Services\Concrete\Admin\ThermalPrintSettingResolverService;
use App\Services\Concrete\Admin\VoucherService;
use App\Services\Concrete\Admin\WarehouseService;
use App\Services\Concrete\Api\WebsiteCheckoutService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    use ResponseAPI;
    use HandlesImportExport;

    protected $order_service;
    protected $business_service;
    protected $branch_service;
    protected $warehouse_service;
    protected $customer_service;
    protected $order_type_service;
    protected $order_source_service;
    protected $payment_method_service;
    protected $document_send_log_service;
    protected $thermal_print_setting_resolver;
    protected $voucher_service;
    protected $checkout_service;

    public function __construct(
        OrderService $order_service,
        BusinessService $business_service,
        BranchService $branch_service,
        WarehouseService $warehouse_service,
        CustomerService $customer_service,
        OrderTypeService $order_type_service,
        OrderSourceService $order_source_service,
        PaymentMethodService $payment_method_service,
        DocumentSendLogService $document_send_log_service,
        ThermalPrintSettingResolverService $thermal_print_setting_resolver,
        VoucherService $voucher_service,
        WebsiteCheckoutService $checkout_service
    ) {
        $this->middleware('module:order');
        $this->middleware('permission:order.export')->only(['export']);
        $this->middleware('permission:order.delete')->only(['destroy']);

        $this->order_service = $order_service;
        $this->business_service = $business_service;
        $this->branch_service = $branch_service;
        $this->warehouse_service = $warehouse_service;
        $this->customer_service = $customer_service;
        $this->order_type_service = $order_type_service;
        $this->order_source_service = $order_source_service;
        $this->payment_method_service = $payment_method_service;
        $this->document_send_log_service = $document_send_log_service;
        $this->thermal_print_setting_resolver = $thermal_print_setting_resolver;
        $this->voucher_service = $voucher_service;
        $this->checkout_service = $checkout_service;
    }

    protected function importExportModuleKey(): string
    {
        return 'order';
    }

    public function index()
    {
        $is_superadmin = RoleNames::SUPERADMIN == getRoleName();
        $business_id = Auth::user()->business_id;

        $business = $is_superadmin ? $this->business_service->getAll() : collect();
        $branches = $is_superadmin ? collect() : $this->branch_service->getAllActive();
        $warehouses = $is_superadmin ? collect() : $this->warehouse_service->getAllActive();
        $registers = $is_superadmin ? collect() : PosRegister::where('business_id', $business_id)
            ->where('status', Status::ACTIVE)
            ->where('is_deleted', 0)
            ->get();
        $cashiers = $is_superadmin ? collect() : User::where('business_id', $business_id)
            ->where('is_deleted', 0)
            ->get();
        $customers = $this->customer_service->getAllActive($is_superadmin ? null : $business_id);
        $order_types = $this->order_type_service->getAllActive($is_superadmin ? null : $business_id);
        $order_sources = $this->order_source_service->getAllActive($is_superadmin ? null : $business_id);
        $payment_methods = $this->payment_method_service->getAllActive($is_superadmin ? null : $business_id);

        $statuses = [
            'draft' => 'Draft',
            'hold' => 'Hold',
            'posted' => 'Posted',
            'shipped' => 'Shipped',
            'out_for_delivery' => 'Out for Delivery',
            'delivered' => 'Delivered',
            'cancelled' => 'Cancelled',
            'void' => 'Void',
            'returned' => 'Returned',
        ];

        return view('admin.order.index', compact(
            'business',
            'branches',
            'warehouses',
            'registers',
            'cashiers',
            'customers',
            'order_types',
            'order_sources',
            'payment_methods',
            'statuses'
        ));
    }

    /**
     * Feeds the filter-bar dropdowns for superadmin users after they pick a
     * business - mirrors the branch/warehouse `by-business` cascading routes
     * already used on the Purchase filter bar, bundled into one call since
     * Orders have far more dependent dropdowns than Purchase does.
     */
    public function filterOptions($business_id)
    {
        try {
            $data = [
                'branches' => $this->branch_service->getByBusiness($business_id),
                'warehouses' => $this->warehouse_service->getByBusiness($business_id),
                'registers' => PosRegister::where('business_id', $business_id)
                    ->where('status', Status::ACTIVE)
                    ->where('is_deleted', 0)
                    ->get(),
                'cashiers' => User::where('business_id', $business_id)
                    ->where('is_deleted', 0)
                    ->get(),
                'customers' => $this->customer_service->getAllActive($business_id),
                'order_types' => $this->order_type_service->getAllActive($business_id),
                'order_sources' => $this->order_source_service->getAllActive($business_id),
                'payment_methods' => $this->payment_method_service->getAllActive($business_id),
            ];

            return $this->success(Message::FETCH, $data);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function getData(Request $request)
    {
        return $this->order_service->getData($request->all());
    }

    public function show($order_id)
    {
        $this->assertOrderAccessible($order_id);

        $order = $this->order_service->getById($order_id);

        if (!$order) {
            abort(404);
        }

        $sale_type_badge = $this->order_service->formatSaleTypeBadge($order->details);
        $payment_method_label = $this->order_service->resolvePaymentMethodLabel($order);
        $can_correct = Auth::user()->can('order.correct') && $this->order_service->canCorrect($order);

        foreach ($order->details as $detail) {
            $detail->setAttribute('sale_type_label', $this->order_service->resolveSaleTypeLabel($detail));
        }

        return view('admin.order.show', compact('order', 'sale_type_badge', 'payment_method_label', 'can_correct'));
    }

    /**
     * Confirm bank-transfer payment for a website order after reviewing the
     * uploaded receipt. Does not post stock/GL - only marks payment as paid.
     */
    public function confirmPayment(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'order_id' => 'required|string|exists:orders,order_id',
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        $this->assertOrderAccessible($request->order_id);

        try {
            $order = $this->checkout_service->confirmPayment($request->order_id, Auth::id());
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }

        return $this->success(Message::UPDATE, $order);
    }

    /**
     * order/data has no dedicated authorization of its own - it relies on
     * applyRoleScope() (App\Helpers\CommonFunctions) to only ever return
     * rows the acting user's business/branch is allowed to see. show(),
     * details() and print() fetch by order_id directly though, so without
     * this check any pos.access user could view/print/reorder-source any
     * order in the system just by knowing its UUID. Reuses the exact same
     * role set and scoping rule getData() already applies, via a single-row
     * existence check against the same helper.
     */
    protected function assertOrderAccessible($order_id)
    {
        $allow_roles = [
            RoleNames::SUPERADMIN,
            RoleNames::BUSINESSADMIN,
            RoleNames::BRANCHADMIN,
            RoleNames::POSMANAGER,
            RoleNames::ORDERTAKER,
        ];

        $accessible = applyRoleScope(Order::where('order_id', $order_id), $allow_roles)->exists();

        if (!$accessible) {
            abort(403, 'You are not authorized to access this order.');
        }
    }

    /**
     * Handles both create and update (mirrors PurchaseController::store()) -
     * the service decides create vs update from the presence of `order_id`
     * in the payload, so this single action backs both the resource's
     * `store` route and the (unused, but registered for consistency with the
     * rest of the codebase) `update` route.
     *
     * Create-vs-edit permission can only be resolved after inspecting the
     * payload, so it is checked here rather than via route middleware. The
     * discount/voucher/price/customer fields are likewise re-validated
     * against the acting user's permissions and stripped if the client sent
     * something they are not allowed to set - the service itself performs no
     * authorization checks, so this is the only place that enforcement can
     * happen.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'products' => ['required', 'array', 'min:1'],
        ]);

        if ($validator->fails()) {
            return $this->validationResponse($validator->errors()->first());
        }

        $is_update = !empty($request->order_id);
        $required_permission = $is_update ? 'order.edit' : 'order.create';

        if (!Auth::user()->can($required_permission)) {
            return $this->error('You do not have permission to ' . ($is_update ? 'edit' : 'create') . ' orders.', 403);
        }

        // This endpoint is only ever reached via the session-authenticated
        // admin/POS web surface (no Website/Mobile/API channel exists yet -
        // see OrderService's class docblock), so register_session_id is
        // always required here. OrderService::save()'s business_id/branch_id/
        // warehouse_id fallback path is reserved for a genuine future
        // non-POS channel and must not be reachable from this controller.
        if (empty($request->register_session_id)) {
            return $this->error('An open register session is required to place orders.', 422);
        }

        $obj = $request->all();

        if (!empty($obj['discount_id']) && !Auth::user()->can('order.discount.apply')) {
            unset($obj['discount_id']);
        }

        if ((!empty($obj['voucher_code']) || !empty($obj['voucher_id'])) && !Auth::user()->can('order.coupon.apply')) {
            unset($obj['voucher_code']);
            unset($obj['voucher_id']);
        }

        if (!empty($obj['use_loyalty_points']) && !Auth::user()->can('order.loyalty.apply')) {
            unset($obj['use_loyalty_points']);
        }

        if (!empty($obj['products']) && !Auth::user()->can('order.price.change')) {
            // Only strip the manual price override here - picking a per-line
            // Sale Type is independently gated by pos_setting.allow_mixed_sale_types
            // (enforced in OrderService::saveLinesAndComputeTotals()), not by
            // this permission, so a cashier without price-change rights must
            // still be able to mark a single item Wholesale/etc.
            foreach ($obj['products'] as $index => $line) {
                unset($obj['products'][$index]['unit_price']);
            }
        }

        if (!empty($obj['customer_id']) && !Auth::user()->can('order.customer.change')) {
            $obj['customer_id'] = null;
        }

        if (!empty($obj['override_minimum_price']) && !Auth::user()->can('order.price.override-minimum')) {
            $obj['override_minimum_price'] = false;
        }

        try {
            $order = $this->order_service->save($obj);
            return $this->success($is_update ? Message::UPDATE : Message::SAVE, $order);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function hold(Request $request)
    {
        try {
            $order = $this->order_service->hold($request->order_id);
            return $this->success(Message::UPDATE, $order);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function resume(Request $request)
    {
        try {
            $order = $this->order_service->resume($request->order_id);
            return $this->success(Message::UPDATE, $order);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function reopen(Request $request)
    {
        try {
            $order = $this->order_service->reopen($request->order_id);
            return $this->success(Message::UPDATE, $order);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function cancel(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,order_id',
            'reason' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return $this->validationResponse($validator->errors()->first());
        }

        try {
            $order = $this->order_service->cancel($request->all());
            return $this->success(Message::UPDATE, $order);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function complete(Request $request)
    {
        // Defense in depth: store() already requires register_session_id on
        // every order created through this controller, so this should never
        // actually be empty - but post() silently skips its open-session
        // check when it is, so this closes that gap explicitly rather than
        // relying on it being an unreachable state.
        $order = \App\Models\Order::find($request->order_id);

        if (!$order) {
            return $this->error('Order not found.');
        }

        if (empty($order->register_session_id)) {
            $order->loadMissing('orderSource');

            // Website/API orders are created without a register session; they
            // may still be posted from Admin or POS once payments are in place.
            if (($order->orderSource->code ?? null) !== 'WEBSITE') {
                return $this->error('This order has no register session and cannot be completed from POS.', 422);
            }
        }

        try {
            $order = $this->order_service->post($request->all());
            return $this->success(Message::SUCCESS, $order);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Records the optional due_date/note captured by the POS Credit Payment
     * popup (shown after a Credit-type sale completes) onto the order. Kept
     * deliberately separate from complete()/post() - JV generation for
     * credit sales already happens there unconditionally, and this endpoint
     * must never touch that transaction.
     */
    public function updateCreditInfo(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,order_id',
            'due_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        if ($validate->fails()) {
            return $this->error($validate->errors()->first());
        }

        try {
            $order = $this->order_service->updateCreditInfo($request->all());
            return $this->success(Message::UPDATE, $order);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function void(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,order_id',
            'reason' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return $this->validationResponse($validator->errors()->first());
        }

        try {
            $order = $this->order_service->void($request->all());
            return $this->success(Message::UPDATE, $order);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Same-day manager correction of a posted POS order. Reverses stock/JV,
     * rebuilds cart lines/payments, and reposts on the same order_id.
     * Sub-permissions (discount/coupon/loyalty/price/customer) are stripped
     * here the same way as store() - the service does not authorize them.
     */
    public function correct(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,order_id',
            'reason' => 'required|string|max:1000',
            'products' => 'required|array|min:1',
            'payments' => 'required|array|min:1',
        ]);

        if ($validator->fails()) {
            return $this->validationResponse($validator->errors()->first());
        }

        $this->assertOrderAccessible($request->order_id);

        $obj = $request->all();

        if (!empty($obj['discount_id']) && !Auth::user()->can('order.discount.apply')) {
            unset($obj['discount_id']);
        }

        if ((!empty($obj['voucher_code']) || !empty($obj['voucher_id'])) && !Auth::user()->can('order.coupon.apply')) {
            unset($obj['voucher_code']);
            unset($obj['voucher_id']);
        }

        if (!empty($obj['use_loyalty_points']) && !Auth::user()->can('order.loyalty.apply')) {
            unset($obj['use_loyalty_points']);
        }

        if (!empty($obj['products']) && !Auth::user()->can('order.price.change')) {
            foreach ($obj['products'] as $index => $line) {
                unset($obj['products'][$index]['unit_price']);
            }
        }

        if (array_key_exists('customer_id', $obj) && !Auth::user()->can('order.customer.change')) {
            unset($obj['customer_id']);
        }

        if (!empty($obj['override_minimum_price']) && !Auth::user()->can('order.price.override-minimum')) {
            $obj['override_minimum_price'] = false;
        }

        try {
            $order = $this->order_service->correct($obj);
            return $this->success(Message::UPDATE, $order);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Update order status from the Admin Order Detail page (complete, cancel,
     * void, or delivery fulfilment steps).
     */
    public function changeStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,order_id',
            'status' => 'required|string|in:posted,cancelled,void,shipped,out_for_delivery,delivered',
            'reason' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return $this->validationResponse($validator->errors()->first());
        }

        $this->assertOrderAccessible($request->order_id);

        $status = strtolower($request->status);

        if (in_array($status, ['cancelled', 'void'], true) && !trim((string) $request->reason)) {
            return $this->validationResponse('A reason is required for this status change.');
        }

        $permission_map = [
            'posted' => 'order.complete',
            'cancelled' => 'order.cancel',
            'void' => 'order.void',
            'shipped' => 'order.complete',
            'out_for_delivery' => 'order.complete',
            'delivered' => 'order.complete',
        ];

        if (!Auth::user()->can($permission_map[$status] ?? 'order.complete')) {
            abort(403, 'You are not authorized to perform this action.');
        }

        try {
            $order = $this->order_service->changeStatus($request->all());
            return $this->success(Message::UPDATE, $order);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function destroy($order_id)
    {
        try {
            $this->order_service->delete($order_id);
            return $this->success(Message::DELETE, []);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function print($order_id)
    {
        $this->assertOrderAccessible($order_id);

        $order = $this->order_service->getById($order_id);

        if (!$order) {
            abort(404);
        }

        try {
            $this->document_send_log_service->log(
                $order->business_id,
                'order',
                $order_id,
                'print',
                null,
                'sent',
                null,
                Auth::id()
            );
        } catch (Exception $e) {
            Log::warning('Print audit log failed: ' . $e->getMessage());
        }

        // Distinct from $order->sale_date/order_date - this is the actual
        // moment of *this* print/reprint, so a reprinted receipt is always
        // identifiable from the original one.
        $printed_at = now();

        $thermal_config = $this->thermal_print_setting_resolver->resolve($order->business_id, $order->branch_id);

        if ($thermal_config->isEnabled()) {
            return view('admin.order.print.thermal', compact('order', 'thermal_config', 'printed_at'));
        }

        return view('admin.order.print.print', compact('order', 'printed_at'));
    }

    /**
     * Explicit Thermal Print action - available in addition to print() (which
     * only renders the thermal layout when the business has "Enable Thermal
     * Receipt Printing" switched on). This action always renders the same
     * centralized admin.order.print.thermal view/ThermalPrintConfig used by
     * print() and the POS screen, so POS Order History and the Admin Orders
     * page share one thermal-printing implementation regardless of the
     * order's originating source (POS, Website, Mobile App, API, ...).
     */
    public function thermalPrint($order_id)
    {
        $this->assertOrderAccessible($order_id);

        $order = $this->order_service->getById($order_id);

        if (!$order) {
            abort(404);
        }

        try {
            $this->document_send_log_service->log(
                $order->business_id,
                'order',
                $order_id,
                'print',
                null,
                'sent',
                null,
                Auth::id()
            );
        } catch (Exception $e) {
            Log::warning('Thermal print audit log failed: ' . $e->getMessage());
        }

        $printed_at = now();
        $thermal_config = $this->thermal_print_setting_resolver->resolve($order->business_id, $order->branch_id);

        return view('admin.order.print.thermal', compact('order', 'thermal_config', 'printed_at'));
    }

    public function searchProducts(Request $request)
    {
        try {
            $products = $this->order_service->searchProducts($request->all());
            return $this->success(Message::FETCH, $products);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function availableSerials(Request $request)
    {
        try {
            $serials = $this->order_service->getAvailableSerials($request->all());
            return $this->success(Message::FETCH, $serials);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function searchVouchers(Request $request)
    {
        $term = trim((string) $request->input('term'));

        if ($term === '') {
            return $this->success(Message::FETCH, []);
        }

        try {
            $business_id = $request->business_id ?? Auth::user()->business_id;
            $vouchers = $this->voucher_service->searchActive($term, $business_id);
            return $this->success(Message::FETCH, $vouchers);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * "Available vouchers for this cart" list for the POS screen, so the
     * cashier can browse eligible vouchers instead of only entering a known
     * code. Only checks what's knowable pre-payment (business, schedule,
     * branch, customer, order-type, order-source, sale-type) - final
     * per-item/BOGO/payment-method eligibility is still resolved when a
     * specific voucher is actually applied via store()/saveLinesAndComputeTotals().
     */
    public function eligibleVouchers(Request $request)
    {
        try {
            $vouchers = $this->voucher_service->eligibleForCart([
                'business_id' => $request->business_id ?? Auth::user()->business_id,
                'branch_id' => $request->branch_id,
                'user_id' => $request->customer_id,
                'order_type_id' => $request->order_type_id,
                'order_source_id' => $request->order_source_id,
                'sale_type_id' => $request->sale_type_id,
            ]);

            return $this->success(Message::FETCH, $vouchers->map(function ($voucher) {
                return [
                    'voucher_id' => $voucher->voucher_id,
                    'code' => $voucher->code,
                    'name' => $voucher->name,
                    'type' => $voucher->type,
                    'value' => $voucher->value,
                    'rule' => $voucher->describeRule(),
                ];
            })->values());
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Real-time, non-persisting preview for the POS "Apply Voucher" button -
     * runs the exact same server-side eligibility/calculation OrderService
     * uses when actually saving an order, so the cashier gets authoritative
     * feedback (amount, matched items, BOGO free units, or the precise
     * rejection reason) before anything is saved. Same discount_id/voucher_id/
     * use_loyalty_points permission stripping as store(), since this reveals what a voucher
     * would apply.
     */
    public function previewVoucher(Request $request)
    {
        $obj = $request->all();

        if (!empty($obj['discount_id']) && !Auth::user()->can('order.discount.apply')) {
            unset($obj['discount_id']);
        }

        if ((!empty($obj['voucher_code']) || !empty($obj['voucher_id'])) && !Auth::user()->can('order.coupon.apply')) {
            unset($obj['voucher_code']);
            unset($obj['voucher_id']);
        }

        if (!empty($obj['use_loyalty_points']) && !Auth::user()->can('order.loyalty.apply')) {
            unset($obj['use_loyalty_points']);
        }

        try {
            $preview = $this->order_service->previewVoucher($obj);
            return $this->success(Message::FETCH, $preview);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function productsByCategory(Request $request)
    {
        try {
            $products = $this->order_service->getProductsByCategory($request->all());
            return $this->success(Message::FETCH, $products);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Re-prices an already-known set of cart lines against a given Sale Type -
     * used by the POS screen when the order-level Sale Type changes, or a
     * line's own Sale Type is overridden, without re-running product search.
     */
    public function resolvePrices(Request $request)
    {
        try {
            $resolved = $this->order_service->resolvePrices($request->all());
            return $this->success(Message::FETCH, $resolved);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function details($order_id)
    {
        $this->assertOrderAccessible($order_id);

        try {
            $data = $this->order_service->getDetails($order_id);

            // OrderService::getDetails()'s header doesn't carry these two -
            // POS needs them to restore the "Use Loyalty Points" checkbox
            // when a held/draft order is resumed (see loadCartFromDetails()
            // in pos-screen.js), the same way header.voucher_code already
            // restores the voucher field.
            $loyalty = Order::where('order_id', $order_id)->first(['loyalty_points_used', 'loyalty_discount_amount']);
            $data['header']['loyalty_points_used'] = $loyalty->loyalty_points_used ?? 0;
            $data['header']['loyalty_discount_amount'] = $loyalty->loyalty_discount_amount ?? 0;

            return $this->success(Message::FETCH, $data);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Dedicated POS Order History page - full-featured listing (filters +
     * pagination) that supersedes the old header offcanvas stub. Reuses the
     * same order/data endpoint and the same filter-dropdown data index()
     * already gathers for the full Admin Order List.
     */
    public function history()
    {
        $is_superadmin = RoleNames::SUPERADMIN == getRoleName();
        $business_id = Auth::user()->business_id;
        $business = $this->business_service->getById($business_id);

        $branches = $is_superadmin ? collect() : $this->branch_service->getAllActive();
        $cashiers = $is_superadmin ? collect() : User::where('business_id', $business_id)
            ->where('is_deleted', 0)
            ->get();
        $customers = $this->customer_service->getAllActive($is_superadmin ? null : $business_id);
        $order_types = $this->order_type_service->getAllActive($is_superadmin ? null : $business_id);
        $order_sources = $this->order_source_service->getAllActive($is_superadmin ? null : $business_id);
        $payment_methods = $this->payment_method_service->getAllActive($is_superadmin ? null : $business_id);

        $statuses = [
            'draft' => 'Draft',
            'hold' => 'Hold',
            'posted' => 'Posted',
            'shipped' => 'Shipped',
            'out_for_delivery' => 'Out for Delivery',
            'delivered' => 'Delivered',
            'cancelled' => 'Cancelled',
            'void' => 'Void',
            'returned' => 'Returned',
        ];

        $payment_statuses = [
            'paid' => 'Paid',
            'partially_paid' => 'Partially Paid',
            'unpaid' => 'Unpaid',
        ];

        // Order Takers/POS Managers are fixed to their own branch (mirrors
        // PosScreenController::$fixed_context_roles) - the Branch filter is
        // hidden for them, and Order Takers additionally have their date
        // range locked to today client-side here, purely to match what the
        // backend (OrderService::getData()) will enforce regardless.
        $role = getRoleName();
        $is_fixed_context = in_array($role, [RoleNames::ORDERTAKER, RoleNames::POSMANAGER], true);
        $is_order_taker = $role === RoleNames::ORDERTAKER;

        // Header Branch/Warehouse chips - mirrors PosScreenController::resolveContext()
        // so the shared POS header (layouts/pos-header.blade.php) shows the same
        // context here as on the POS screen itself. Purely for display; this page's
        // own Branch filter dropdown above is unaffected.
        $branch_name = null;
        $warehouse_name = null;
        if ($is_fixed_context) {
            $branch_name = optional($this->branch_service->getById(Auth::user()->branch_id))->name;
        } else {
            $context_branch_id = session('pos_context_branch_id');
            $context_warehouse_id = session('pos_context_warehouse_id');
            $branch_name = $context_branch_id ? optional($this->branch_service->getById($context_branch_id))->name : null;
            $warehouse_name = $context_warehouse_id ? optional($this->warehouse_service->getById($context_warehouse_id))->name : null;
        }

        // The POS header's live register-session actions (Cash In/Out, Close
        // Register, Reports offcanvas, Hold Orders) are only wired up by
        // pos-screen.js on the POS screen itself - keeping them off here
        // avoids dead buttons on this page. This page still gets the POS
        // brand/user-menu/"Switch to Admin" chrome from the same header.
        $show_pos_actions = false;

        return view('admin.pos.order-history.index', compact(
            'branches',
            'cashiers',
            'customers',
            'order_types',
            'order_sources',
            'payment_methods',
            'statuses',
            'payment_statuses',
            'is_superadmin',
            'is_fixed_context',
            'is_order_taker',
            'show_pos_actions',
            'business',
            'branch_name',
            'warehouse_name'
        ));
    }

    /**
     * Aggregate totals for the currently filtered POS Order History view
     * (order count, sales/paid/due, breakdown by status and payment method).
     * Reuses the exact same filter/scope rules as getData() via
     * OrderService::getHistorySummary() so the numbers always match what the
     * table above is showing.
     */
    public function historySummary(Request $request)
    {
        try {
            return $this->success(Message::FETCH, $this->order_service->getHistorySummary($request->all()));
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Thermal-formatted print of the same filtered summary - standalone
     * print layout (no admin/POS chrome), same as order print/reprint, so it
     * never leaves the POS interface.
     */
    public function historySummaryPrint(Request $request)
    {
        $business_id = RoleNames::SUPERADMIN == getRoleName()
            ? ($request->query('business_id') ?: Auth::user()->business_id)
            : Auth::user()->business_id;

        $filters = $request->query();
        $summary = $this->order_service->getHistorySummary($filters);
        $thermal_config = $this->thermal_print_setting_resolver->resolve($business_id, $filters['branch_id'] ?? null);
        $business = $this->business_service->getById($business_id);
        $printed_at = now();

        return view('admin.order.print.thermal-sales-summary', compact('summary', 'thermal_config', 'business', 'filters', 'printed_at'));
    }
}
