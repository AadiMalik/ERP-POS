<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RoleNames;
use App\Enums\Message;
use App\Enums\Status;
use App\Http\Controllers\Controller;
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
use App\Services\Concrete\Admin\WarehouseService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    use ResponseAPI;

    protected $order_service;
    protected $business_service;
    protected $branch_service;
    protected $warehouse_service;
    protected $customer_service;
    protected $order_type_service;
    protected $order_source_service;
    protected $payment_method_service;
    protected $document_send_log_service;

    public function __construct(
        OrderService $order_service,
        BusinessService $business_service,
        BranchService $branch_service,
        WarehouseService $warehouse_service,
        CustomerService $customer_service,
        OrderTypeService $order_type_service,
        OrderSourceService $order_source_service,
        PaymentMethodService $payment_method_service,
        DocumentSendLogService $document_send_log_service
    ) {
        $this->order_service = $order_service;
        $this->business_service = $business_service;
        $this->branch_service = $branch_service;
        $this->warehouse_service = $warehouse_service;
        $this->customer_service = $customer_service;
        $this->order_type_service = $order_type_service;
        $this->order_source_service = $order_source_service;
        $this->payment_method_service = $payment_method_service;
        $this->document_send_log_service = $document_send_log_service;
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
        $order = $this->order_service->getById($order_id);

        if (!$order) {
            abort(404);
        }

        return view('admin.order.show', compact('order'));
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

        if (!empty($obj['products']) && !Auth::user()->can('order.price.change')) {
            foreach ($obj['products'] as $index => $line) {
                unset($obj['products'][$index]['unit_price']);
            }
        }

        if (!empty($obj['customer_id']) && !Auth::user()->can('order.customer.change')) {
            $obj['customer_id'] = null;
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
        try {
            $order = $this->order_service->cancel($request->order_id);
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
            return $this->error('This order has no register session and cannot be completed from POS.', 422);
        }

        try {
            $order = $this->order_service->post($request->all());
            return $this->success(Message::SUCCESS, $order);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function void(Request $request)
    {
        try {
            $order = $this->order_service->void($request->all());
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

        return view('admin.order.print.print', compact('order'));
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

    public function productsByCategory(Request $request)
    {
        try {
            $products = $this->order_service->getProductsByCategory($request->all());
            return $this->success(Message::FETCH, $products);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function details($order_id)
    {
        try {
            return $this->success(Message::FETCH, $this->order_service->getDetails($order_id));
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
