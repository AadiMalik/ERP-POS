<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RoleNames;
use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Models\BusinessSetting;
use App\Models\PosRegister;
use App\Models\PosSetting;
use App\Models\Role;
use App\Services\Concrete\Admin\BranchService;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\CategoryService;
use App\Services\Concrete\Admin\CustomerService;
use App\Services\Concrete\Admin\DiscountService;
use App\Services\Concrete\Admin\ExpenseCategoryService;
use App\Services\Concrete\Admin\ExpenseService;
use App\Services\Concrete\Admin\OrderSourceService;
use App\Services\Concrete\Admin\OrderTypeService;
use App\Services\Concrete\Admin\PaymentMethodService;
use App\Services\Concrete\Admin\PosRegisterSessionService;
use App\Services\Concrete\Admin\UserService;
use App\Services\Concrete\Admin\WarehouseService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PosScreenController extends Controller
{
    use ResponseAPI;

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
        'expense.access',
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
    protected $category_service;
    protected $business_service;
    protected $branch_service;
    protected $warehouse_service;
    protected $user_service;
    protected $expense_category_service;
    protected $expense_service;
    protected $pos_register_session_service;

    public function __construct(
        CustomerService $customer_service,
        OrderTypeService $order_type_service,
        OrderSourceService $order_source_service,
        PaymentMethodService $payment_method_service,
        DiscountService $discount_service,
        CategoryService $category_service,
        BusinessService $business_service,
        BranchService $branch_service,
        WarehouseService $warehouse_service,
        UserService $user_service,
        ExpenseCategoryService $expense_category_service,
        ExpenseService $expense_service,
        PosRegisterSessionService $pos_register_session_service
    ) {
        $this->customer_service = $customer_service;
        $this->order_type_service = $order_type_service;
        $this->order_source_service = $order_source_service;
        $this->payment_method_service = $payment_method_service;
        $this->discount_service = $discount_service;
        $this->category_service = $category_service;
        $this->business_service = $business_service;
        $this->branch_service = $branch_service;
        $this->warehouse_service = $warehouse_service;
        $this->user_service = $user_service;
        $this->expense_category_service = $expense_category_service;
        $this->expense_service = $expense_service;
        $this->pos_register_session_service = $pos_register_session_service;
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
        $categories = $this->category_service->getByBusiness($business_id);
        $expense_categories = $this->expense_category_service->getActiveByBusiness($business_id);

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
        $show_pos_actions = true;

        $pos_order_source_id = $this->resolvePosOrderSourceId($order_sources);
        $branch_name = optional($this->branch_service->getById($branch_id))->name;

        // Reorder entry point - order.show's Reorder button links here with
        // this query param; pos-screen.js reads it from POS_CONFIG on load
        // to hydrate the cart from that order's line items (see
        // reorderFromOrder() there). Never trusted as-is beyond an ID -
        // OrderController::details() re-checks access when it's fetched.
        $reorder_from = request()->query('reorder_from');

        // Change-Branch modal data - only needed for roles that are allowed to
        // switch (see $fixed_context_roles); OT/PosManager stay fixed to their
        // own branch, so skip the extra queries entirely for them.
        $is_superadmin = RoleNames::SUPERADMIN == getRoleName();
        $context_businesses = collect();
        $context_branches = collect();
        $context_warehouses = collect();

        if (!$is_fixed_context) {
            $context_businesses = $is_superadmin ? $this->business_service->getAllActive() : collect();
            $context_branches = $this->branch_service->getByBusiness($business_id);
            $context_warehouses = $this->warehouse_service->getByBusiness($business_id);
        }

        return view('admin.pos.screen.index', compact(
            'pos_setting',
            'business_setting',
            'business_id',
            'branch_id',
            'warehouse_id',
            'order_types',
            'payment_methods',
            'customers',
            'discounts',
            'categories',
            'expense_categories',
            'registers',
            'permissions',
            'is_fixed_context',
            'show_pos_actions',
            'pos_order_source_id',
            'branch_name',
            'is_superadmin',
            'context_businesses',
            'context_branches',
            'context_warehouses',
            'reorder_from'
        ));
    }

    /**
     * POS-created orders always use the business's seeded 'POS' order
     * source (see OrderSourceService::$default_sources) - the control is
     * removed from the POS UI entirely, index.blade.php renders a hidden
     * field instead of pills. Falls back to whichever source is flagged
     * is_default, then simply the first, in case an admin renamed the
     * seeded 'POS' code via the Order Source admin CRUD
     * (OrderSourceController). Deliberately independent of
     * pos_setting->default_order_source_id, which OrderService::save()
     * still falls back to for the non-POS Order module and may be unset.
     */
    protected function resolvePosOrderSourceId($order_sources)
    {
        $source = $order_sources->firstWhere('code', 'POS')
            ?? $order_sources->firstWhere('is_default', true)
            ?? $order_sources->first();

        return optional($source)->order_source_id;
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

    /**
     * Quick "Add Customer" popup on the POS screen - a minimal-field
     * (Name/Email/Phone) alternative to the full admin Users create form, so
     * a cashier never has to leave the screen. Reuses UserService::save()
     * as-is (same "reuse existing account by email" rule + CustomerProfile
     * creation via CustomerService::upsertProfile()) instead of duplicating
     * that logic here.
     */
    public function quickCreateCustomer(Request $request)
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => 'nullable|string|max:50',
        ];

        $validate = Validator::make($request->all(), $rules);

        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        try {
            $user = Auth::user();
            $context = $this->resolveContext($user);
            $business_id = $context[0] ?? $user->business_id;

            $role_id = Role::where('name', RoleNames::USER)->whereNull('business_id')->value('id');

            if (empty($role_id)) {
                return $this->error('Customer role is not configured.');
            }

            $customer = $this->user_service->save([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'role_id' => $role_id,
                'business_id' => $business_id,
                'status' => 'active',
            ]);

            if (!$customer) {
                return $this->error('Unable to create customer.');
            }

            $profile = $this->customer_service->getProfile($customer->id, $business_id);

            return $this->success('Customer added.', [
                'user_id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'email' => $customer->email,
                'credit_limit' => $profile->credit_limit ?? 0,
                'is_walkin' => $profile->is_walkin ?? 0,
            ]);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Quick "Add Expense" popup on the POS screen - lets an Order Taker
     * record a cash expense against their currently active register session
     * without leaving the screen. Saves and posts (generating its JV) in one
     * step via ExpenseService::save()'s auto_post flag, since - like a Cash
     * Out movement - the till cash changes the moment it's logged; the
     * Admin-side Expense Detail CRUD can still unpost/edit/delete it later.
     */
    public function quickCreateExpense(Request $request)
    {
        $rules = [
            'pos_register_session_id' => ['required', 'string'],
            'expense_category_id' => ['required', 'string'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'description' => ['nullable', 'string'],
        ];

        $validate = Validator::make($request->all(), $rules);

        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        try {
            $expense = $this->expense_service->save([
                'pos_register_session_id' => $request->pos_register_session_id,
                'expense_category_id' => $request->expense_category_id,
                'amount' => $request->amount,
                'payment_method' => 'cash',
                'expense_date' => now(),
                'description' => $request->description,
                'source' => 'pos',
                'auto_post' => true,
            ]);

            return $this->success('Expense recorded.', [
                'expense_id' => $expense->expense_id,
                'expense_no' => $expense->expense_no,
                'amount' => $expense->amount,
            ]);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
