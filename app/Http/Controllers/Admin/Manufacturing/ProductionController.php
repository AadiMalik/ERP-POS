<?php

namespace App\Http\Controllers\Admin\Manufacturing;

use App\Enums\Message;
use App\Enums\ProductionStatus;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\BranchService;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\Manufacturing\ManufacturingPlanService;
use App\Services\Concrete\Admin\Manufacturing\ProductionService;
use App\Services\Concrete\Admin\WarehouseService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ProductionController extends Controller
{
    use ResponseAPI;

    protected $production_service;
    protected $plan_service;
    protected $warehouse_service;
    protected $business_service;
    protected $branch_service;

    public function __construct(
        ProductionService $production_service,
        ManufacturingPlanService $plan_service,
        WarehouseService $warehouse_service,
        BusinessService $business_service,
        BranchService $branch_service
    ) {
        $this->middleware('permission:production.view')->only(['index', 'getData', 'show']);
        $this->middleware('permission:production.create')->only(['create']);
        $this->middleware('permission:production.create|production.edit')->only(['store']);
        $this->middleware('permission:production.edit')->only(['edit']);
        $this->middleware('permission:production.complete')->only(['complete']);
        $this->middleware('permission:production.cancel')->only(['cancel']);
        $this->middleware('module:production');

        $this->production_service = $production_service;
        $this->plan_service = $plan_service;
        $this->warehouse_service = $warehouse_service;
        $this->business_service = $business_service;
        $this->branch_service = $branch_service;
    }

    public function index()
    {
        $business = $this->business_service->getAll();
        $branches = $this->branch_service->getAllActive();
        $statuses = ProductionStatus::getOptions();
        return view('admin.manufacturing.productions.index', compact('business', 'branches', 'statuses'));
    }

    public function getData(Request $request)
    {
        return $this->production_service->getData($request->all());
    }

    public function create(Request $request)
    {
        return $this->formView(null, $request->query('manufacturing_plan_id'));
    }

    public function edit($production_id)
    {
        $production = $this->production_service->getById($production_id);
        if (!$production) {
            return redirect('admin/production')->with('error', Message::NOTFOUND);
        }
        return $this->formView($production);
    }

    public function show($production_id)
    {
        $production = $this->production_service->getById($production_id);
        if (!$production) {
            return redirect('admin/production')->with('error', Message::NOTFOUND);
        }
        return view('admin.manufacturing.productions.show', compact('production'));
    }

    protected function formView($production = null, $preselect_plan_id = null)
    {
        $business = $this->business_service->getAll();
        $branches = $this->branch_service->getAllActive();
        $warehouses = $this->warehouse_service->getAllForCurrentUser();
        $plan = $preselect_plan_id ? $this->plan_service->getById($preselect_plan_id) : null;
        return view('admin.manufacturing.productions.create', compact('production', 'business', 'branches', 'warehouses', 'plan'));
    }

    public function store(Request $request)
    {
        $rules = [
            'manufacturing_plan_id' => 'required|exists:manufacturing_plans,manufacturing_plan_id',
            'warehouse_id' => 'required|exists:warehouses,warehouse_id',
            'quantity' => 'required|numeric|min:0.0001',
            'manufacturing_date' => 'required|date',
            'expiry_date' => 'nullable|date|after_or_equal:manufacturing_date',
            'labor_cost' => 'nullable|numeric|min:0',
            'overhead_cost' => 'nullable|numeric|min:0',
            'other_cost' => 'nullable|numeric|min:0',
        ];

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return redirect()->back()->withErrors($validate)->withInput();
        }

        // batch_no is always system-generated (see ProductionService::save())
        // - never accepted from the form.
        $obj = $request->only([
            'production_id', 'manufacturing_plan_id', 'warehouse_id', 'quantity',
            'manufacturing_date', 'expiry_date', 'notes', 'labor_cost', 'overhead_cost',
            'other_cost', 'operator_user_id',
        ]);
        $obj['branch_id'] = $request->branch_id ?? Auth::user()->branch_id;
        $obj['labor_cost'] = $obj['labor_cost'] ?? 0;
        $obj['overhead_cost'] = $obj['overhead_cost'] ?? 0;
        $obj['other_cost'] = $obj['other_cost'] ?? 0;
        $obj['operator_user_id'] = $obj['operator_user_id'] ?? Auth::id();

        try {
            $this->production_service->save($obj);
            return redirect('admin/production')
                ->with('success', empty($request->production_id) ? Message::SAVE : Message::UPDATE);
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function complete($production_id)
    {
        try {
            $this->production_service->complete($production_id);
            return $this->success('Production completed - finished goods added to stock.', null);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function cancel(Request $request, $production_id)
    {
        try {
            $this->production_service->cancel($production_id, $request->cancel_reason);
            return $this->success(Message::UPDATE, null);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
