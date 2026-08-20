<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Message;
use App\Http\Controllers\Concerns\HandlesImportExport;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\BranchService;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\CategoryService;
use App\Services\Concrete\Admin\CustomerService;
use App\Services\Concrete\Admin\OrderTypeService;
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

    // Request keys for the 5 scope-pivot dimensions, mapped to the array key the
    // service expects.
    protected $scope_keys = ['product_ids', 'category_ids', 'customer_ids', 'order_type_ids', 'branch_ids'];

    public function __construct(
        VoucherService $voucher_service,
        BusinessService $business_service,
        ProductService $product_service,
        CategoryService $category_service,
        CustomerService $customer_service,
        OrderTypeService $order_type_service,
        BranchService $branch_service
    ) {
        $this->middleware('permission:voucher.view')->only(['index', 'getData']);
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
    }

    protected function importExportModuleKey(): string
    {
        return 'voucher';
    }

    public function index()
    {
        $business = $this->business_service->getAllActive();
        $products = $this->product_service->getAllActive();
        $categories = $this->category_service->getAllActive();
        $customers = $this->customer_service->getAllActive();
        $order_types = $this->order_type_service->getAllActive();
        $branches = $this->branch_service->getAllActive();

        return view('admin.voucher.index', compact(
            'business',
            'products',
            'categories',
            'customers',
            'order_types',
            'branches'
        ));
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
            'value' => ['required', 'numeric', 'min:0'],
            'valid_from' => ['nullable', 'date'],
            'valid_to' => ['nullable', 'date'],
            'usage_limit_total' => ['nullable', 'integer', 'min:0'],
            'usage_limit_per_customer' => ['nullable', 'integer', 'min:0'],
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
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
            'value',
            'valid_from',
            'valid_to',
            'usage_limit_total',
            'usage_limit_per_customer',
            'min_order_amount',
        ]);
        $obj['business_id'] = $request->business_id ?? Auth::user()->business_id;
        $obj['status'] = $request->status ?? 'active';

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
