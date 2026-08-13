<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RoleNames;
use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Models\BusinessSetting;
use App\Models\PosRegister;
use App\Models\PosSetting;
use App\Services\Concrete\Admin\BranchService;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\CustomerService;
use App\Services\Concrete\Admin\DiscountService;
use App\Services\Concrete\Admin\OrderSourceService;
use App\Services\Concrete\Admin\OrderTypeService;
use App\Services\Concrete\Admin\PaymentMethodService;
use App\Services\Concrete\Admin\WarehouseService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PosScreenController extends Controller
{
    // Permission keys the POS screen's UI needs to know about - mirrors the
    // plain DB-backed permission records seeded by
    // database/migrations/2026_08_13_090020_seed_order_pos_permissions.php.
    // No dedicated enum class - this codebase manages permissions as normal
    // `permissions` table rows everywhere else too.
    protected $permission_keys = [
        'pos.access',
        'pos.register.close',
        'pos.register.report.view',
        'order.create',
        'order.edit',
        'order.discount.apply',
        'order.coupon.apply',
        'order.price.change',
        'order.hold',
        'order.cancel_void',
        'order.refund.process',
        'order.payment.credit',
        'order.customer.change',
        'order.reopen',
    ];

    // OT/POSM users are fixed to their own business/branch and go straight to
    // the screen. Every other role that can reach POS (Admins, managers, ...)
    // is treated as an "Admin entry" and must pick a Business/Branch/Warehouse
    // first, since they may be authorized for more than one.
    protected $fixed_context_roles = [
        RoleNames::ORDERTAKER,
        RoleNames::POSMANAGER,
    ];

    protected $customer_service;
    protected $order_type_service;
    protected $order_source_service;
    protected $payment_method_service;
    protected $discount_service;
    protected $business_service;
    protected $branch_service;
    protected $warehouse_service;

    public function __construct(
        CustomerService $customer_service,
        OrderTypeService $order_type_service,
        OrderSourceService $order_source_service,
        PaymentMethodService $payment_method_service,
        DiscountService $discount_service,
        BusinessService $business_service,
        BranchService $branch_service,
        WarehouseService $warehouse_service
    ) {
        $this->customer_service = $customer_service;
        $this->order_type_service = $order_type_service;
        $this->order_source_service = $order_source_service;
        $this->payment_method_service = $payment_method_service;
        $this->discount_service = $discount_service;
        $this->business_service = $business_service;
        $this->branch_service = $branch_service;
        $this->warehouse_service = $warehouse_service;
    }

    /**
     * Gathers everything the cashier-facing POS screen's initial render
     * needs into one Blade view. All interactive behaviour after this
     * (register bootstrap, product search, cart, checkout) runs via AJAX
     * calls to the already-built order/pos-register-session endpoints -
     * see public/assets/js/admin/pos-screen.js.
     *
     * Admin-type users (anyone other than Order Taker/POS Manager) must pick
     * a Business/Branch/Warehouse before the screen renders, since they may
     * be authorized for more than one - resolveContext() returns null and
     * this shows the picker instead when that choice hasn't been made yet.
     */
    public function index()
    {
        $user = Auth::user();

        $context = $this->resolveContext($user);

        if (empty($context)) {
            return $this->showContextPicker($user);
        }

        [$business_id, $branch_id, $warehouse_id] = $context;

        $pos_setting = PosSetting::firstOrCreate(['business_id' => $business_id]);
        $business_setting = BusinessSetting::firstOrCreate(['business_id' => $business_id]);

        $order_types = $this->order_type_service->getAllActive($business_id);
        $order_sources = $this->order_source_service->getAllActive($business_id);
        $payment_methods = $this->payment_method_service->getAllActive($business_id);
        $customers = $this->customer_service->getAllActive($business_id);
        $discounts = $this->discount_service->getAllActive($business_id);

        $registers = PosRegister::where('business_id', $business_id)
            ->where('branch_id', $branch_id)
            ->where('status', Status::ACTIVE)
            ->where('is_deleted', 0)
            ->get();

        $permissions = [];
        foreach ($this->permission_keys as $key) {
            $permissions[$key] = $user->can($key);
        }

        $is_fixed_context = in_array(getRoleName(), $this->fixed_context_roles, true);

        return view('admin.pos.screen.index', compact(
            'pos_setting',
            'business_setting',
            'business_id',
            'branch_id',
            'warehouse_id',
            'order_types',
            'order_sources',
            'payment_methods',
            'customers',
            'discounts',
            'registers',
            'permissions',
            'is_fixed_context'
        ));
    }

    /**
     * @return array{0: string, 1: string, 2: ?string}|null [business_id, branch_id, warehouse_id]
     */
    protected function resolveContext($user)
    {
        if (in_array(getRoleName(), $this->fixed_context_roles, true)) {
            return [$user->business_id, $user->branch_id, null];
        }

        $business_id = session('pos_context_business_id') ?? (
            RoleNames::SUPERADMIN == getRoleName() ? null : $user->business_id
        );
        $branch_id = session('pos_context_branch_id');
        $warehouse_id = session('pos_context_warehouse_id');

        if (empty($business_id) || empty($branch_id)) {
            return null;
        }

        return [$business_id, $branch_id, $warehouse_id];
    }

    protected function showContextPicker($user)
    {
        $is_superadmin = RoleNames::SUPERADMIN == getRoleName();

        $businesses = $is_superadmin ? $this->business_service->getAllActive() : collect();
        $branches = $is_superadmin
            ? collect()
            : $this->branch_service->getAllActive();
        $warehouses = $is_superadmin
            ? collect()
            : $this->warehouse_service->getByBusiness($user->business_id);

        return view('admin.pos.screen.select-context', compact('businesses', 'branches', 'warehouses', 'is_superadmin'));
    }

    /**
     * Cascading branch/warehouse options for a chosen business - used by the
     * context picker when a superadmin selects a business.
     */
    public function contextOptions($business_id)
    {
        try {
            return response()->json([
                'Status' => true,
                'Data' => [
                    'branches' => $this->branch_service->getByBusiness($business_id),
                    'warehouses' => $this->warehouse_service->getByBusiness($business_id),
                ],
            ]);
        } catch (Exception $e) {
            return response()->json(['Status' => false, 'Message' => $e->getMessage()], 422);
        }
    }

    public function selectContext(Request $request)
    {
        $is_superadmin = RoleNames::SUPERADMIN == getRoleName();

        $rules = [
            'branch_id' => ['required', 'string'],
            'warehouse_id' => ['nullable', 'string'],
        ];

        if ($is_superadmin) {
            $rules['business_id'] = ['required', 'string'];
        }

        $validate = Validator::make($request->all(), $rules);

        if ($validate->fails()) {
            return redirect()->back()->withErrors($validate)->withInput();
        }

        session([
            'pos_context_business_id' => $is_superadmin ? $request->business_id : Auth::user()->business_id,
            'pos_context_branch_id' => $request->branch_id,
            'pos_context_warehouse_id' => $request->warehouse_id,
        ]);

        return redirect()->route('pos-screen');
    }

    /**
     * Lets an Admin-type user switch to a different Business/Branch/Warehouse
     * without logging out - clears the stored context so index() shows the
     * picker again.
     */
    public function changeContext()
    {
        session()->forget(['pos_context_business_id', 'pos_context_branch_id', 'pos_context_warehouse_id']);

        return redirect()->route('pos-screen');
    }
}
