<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Message;
use App\Http\Controllers\Concerns\HandlesImportExport;
use App\Http\Controllers\Controller;
use App\Models\ProductVariation;
use App\Services\Concrete\Admin\BranchService;
use App\Services\Concrete\Admin\BrandService;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\CategoryService;
use App\Services\Concrete\Admin\CustomerService;
use App\Services\Concrete\Admin\OrderSourceService;
use App\Services\Concrete\Admin\OrderTypeService;
use App\Services\Concrete\Admin\PaymentMethodService;
use App\Services\Concrete\Admin\SaleTypeService;
use App\Services\Concrete\Admin\VoucherService;
use App\Services\Concrete\Admin\ProductService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class VoucherController extends Controller
{
    use ResponseAPI;
    use HandlesImportExport;

    protected $voucher_service;
    protected $business_service;
    protected $product_service;
    protected $category_service;
    protected $customer_service;
    protected $order_type_service;
    protected $branch_service;
    protected $brand_service;
    protected $sale_type_service;
    protected $order_source_service;
    protected $payment_method_service;

    // Request keys for the scope-pivot dimensions, mapped to the array key the
    // service expects.
    protected $scope_keys = [
        'product_ids', 'category_ids', 'customer_ids', 'order_type_ids', 'branch_ids',
        'brand_ids', 'variation_ids', 'sale_type_ids', 'order_source_ids', 'payment_method_ids',
        'get_product_ids', 'get_category_ids',
    ];

    public function __construct(
        VoucherService $voucher_service,
        BusinessService $business_service,
        ProductService $product_service,
        CategoryService $category_service,
        CustomerService $customer_service,
        OrderTypeService $order_type_service,
        BranchService $branch_service,
        BrandService $brand_service,
        SaleTypeService $sale_type_service,
        OrderSourceService $order_source_service,
        PaymentMethodService $payment_method_service
    ) {
        $this->middleware('permission:voucher.view')->only(['index', 'getData', 'byBusiness']);
        $this->middleware('permission:voucher.create|voucher.edit')->only(['store']);
        $this->middleware('permission:voucher.edit')->only(['edit']);
        $this->middleware('permission:voucher.delete')->only(['destroy']);
        $this->middleware('permission:voucher.status')->only(['status']);
        $this->middleware('permission:voucher.import')->only(['importSample', 'importPreview', 'importConfirm']);
        $this->middleware('permission:voucher.export')->only(['export']);
        $this->middleware('module:voucher');

        $this->voucher_service = $voucher_service;
        $this->business_service = $business_service;
        $this->product_service = $product_service;
        $this->category_service = $category_service;
        $this->customer_service = $customer_service;
        $this->order_type_service = $order_type_service;
        $this->branch_service = $branch_service;
        $this->brand_service = $brand_service;
        $this->sale_type_service = $sale_type_service;
        $this->order_source_service = $order_source_service;
        $this->payment_method_service = $payment_method_service;
    }

    protected function importExportModuleKey(): string
    {
        return 'voucher';
    }

    public function index()
    {
        $business = $this->business_service->getAllActive();
        $picker_data = $this->scopePickerData(Auth::user()->business_id);

        return view('admin.voucher.index', array_merge(compact('business'), $picker_data));
    }

    /**
     * Repopulates every scope-picker dropdown for the given business - a Super
     * Admin (business_id = null) has no products/categories/etc. of their own,
     * so index() alone leaves every picker empty for that role until a
     * specific business is chosen from the Business select. Mirrors the
     * by-business reload pattern already used by Warehouse/Product/Category/
     * Brand/Branch/Customer (see WarehouseController::byBusiness()).
     */
    public function byBusiness($business_id)
    {
        try {
            return $this->success(Message::FETCH, $this->scopePickerData($business_id));
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    protected function scopePickerData($business_id): array
    {
        return [
            'products' => $this->product_service->getByBusiness($business_id),
            'categories' => $this->category_service->getByBusiness($business_id),
            'customers' => $this->customer_service->getAllActive($business_id),
            'order_types' => $this->order_type_service->getAllActive($business_id),
            'branches' => $this->branch_service->getByBusiness($business_id),
            'brands' => $this->brand_service->getByBusiness($business_id),
            'sale_types' => $this->sale_type_service->getAllActive($business_id),
            'order_sources' => $this->order_source_service->getAllActive($business_id),
            'payment_methods' => $this->payment_method_service->getAllActive($business_id),
            'variations' => ProductVariation::with('product')->where('business_id', $business_id)->get(),
        ];
    }

    public function getData(Request $request)
    {
        return $this->voucher_service->getData($request->all());
    }

    public function store(Request $request)
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('vouchers', 'code')
                    ->where(function ($query) use ($request) {
                        return $query->where('business_id', $request->business_id ?? Auth::user()->business_id)
                            ->where('is_deleted', 0);
                    })
                    ->ignore($request->voucher_id, 'voucher_id')
            ],
            'type' => ['required', 'in:percent,fixed'],
            'promo_type' => ['nullable', 'in:discount,bogo,buy_x_get_y'],
            'value' => ['required_unless:promo_type,bogo,buy_x_get_y', 'nullable', 'numeric', 'min:0'],
            'valid_from' => ['nullable', 'date'],
            'valid_to' => ['nullable', 'date'],
            'days_of_week' => ['nullable', 'array'],
            'days_of_week.*' => ['integer', 'between:0,6'],
            'time_start' => ['nullable', 'date_format:H:i'],
            'time_end' => ['nullable', 'date_format:H:i', 'required_with:time_start'],
            'usage_limit_total' => ['nullable', 'integer', 'min:0'],
            'usage_limit_per_customer' => ['nullable', 'integer', 'min:0'],
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'max_discount_amount' => ['nullable', 'numeric', 'min:0'],
            'is_exclusive' => ['nullable', 'boolean'],
            'buy_quantity' => ['required_if:promo_type,bogo,buy_x_get_y', 'nullable', 'integer', 'min:1'],
            'get_quantity' => ['required_if:promo_type,bogo,buy_x_get_y', 'nullable', 'integer', 'min:1'],
            'get_discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        $obj = $request->only([
            'voucher_id',
            'code',
            'name',
            'type',
            'promo_type',
            'value',
            'valid_from',
            'valid_to',
            'days_of_week',
            'time_start',
            'time_end',
            'usage_limit_total',
            'usage_limit_per_customer',
            'min_order_amount',
            'max_discount_amount',
            'is_exclusive',
            'buy_quantity',
            'get_quantity',
            'get_discount_percent',
        ]);
        $obj['business_id'] = $request->business_id ?? Auth::user()->business_id;
        $obj['status'] = $request->status ?? 'active';
        $obj['is_exclusive'] = $request->boolean('is_exclusive');
        // BOGO/buy-X-get-Y vouchers don't use type/value - the columns stay
        // NOT NULL at the DB level, so default them rather than reject the save.
        $obj['value'] = $obj['value'] ?? 0;
        $obj['type'] = $obj['type'] ?? 'fixed';

        foreach ($this->scope_keys as $key) {
            if ($request->has($key)) {
                $obj[$key] = array_values(array_filter((array) $request->input($key, []), function ($id) {
                    return $id !== null && $id !== '';
                }));
            }
        }

        try {
            $voucher = $this->voucher_service->save($obj);
            return $this->success(
                empty($request->voucher_id) ? Message::SAVE : Message::UPDATE,
                $voucher
            );
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function edit($voucher_id)
    {
        try {
            $voucher = $this->voucher_service->getById($voucher_id);
            return $this->success(
                Message::FETCH,
                $voucher
            );
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function status($voucher_id)
    {
        try {
            $this->voucher_service->status($voucher_id);
            return $this->success(
                Message::STATUS,
                []
            );
        } catch (Exception $e) {
            return $this->error(
                Message::ERROR
            );
        }
    }

    public function destroy($voucher_id)
    {
        try {

            $this->voucher_service->delete($voucher_id);

            return $this->success(
                Message::DELETE,
                []
            );
        } catch (Exception $e) {

            return $this->error(
                Message::ERROR
            );
        }
    }
}
