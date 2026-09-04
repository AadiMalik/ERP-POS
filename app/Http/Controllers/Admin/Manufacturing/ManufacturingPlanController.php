<?php

namespace App\Http\Controllers\Admin\Manufacturing;

use App\Enums\Message;
use App\Enums\ManufacturingPlanStatus;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\BranchService;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\Manufacturing\ManufacturingPlanService;
use App\Services\Concrete\Admin\Manufacturing\ProductRecipeService;
use App\Services\Concrete\Admin\ProductService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ManufacturingPlanController extends Controller
{
    use ResponseAPI;

    protected $plan_service;
    protected $recipe_service;
    protected $product_service;
    protected $business_service;
    protected $branch_service;

    public function __construct(
        ManufacturingPlanService $plan_service,
        ProductRecipeService $recipe_service,
        ProductService $product_service,
        BusinessService $business_service,
        BranchService $branch_service
    ) {
        $this->middleware('permission:manufacturing-plan.view')->only(['index', 'getData', 'show']);
        $this->middleware('permission:manufacturing-plan.create')->only(['create']);
        $this->middleware('permission:manufacturing-plan.create|manufacturing-plan.edit')->only(['store']);
        $this->middleware('permission:manufacturing-plan.edit')->only(['edit']);
        $this->middleware('permission:manufacturing-plan.delete')->only(['destroy']);
        $this->middleware('permission:manufacturing-plan.confirm')->only(['confirm']);
        $this->middleware('permission:manufacturing-plan.cancel')->only(['cancel']);
        $this->middleware('module:manufacturing-plan');

        $this->plan_service = $plan_service;
        $this->recipe_service = $recipe_service;
        $this->product_service = $product_service;
        $this->business_service = $business_service;
        $this->branch_service = $branch_service;
    }

    public function index()
    {
        $business = $this->business_service->getAll();
        $branches = $this->branch_service->getAllActive();
        $statuses = ManufacturingPlanStatus::getOptions();
        return view('admin.manufacturing.plans.index', compact('business', 'branches', 'statuses'));
    }

    public function getData(Request $request)
    {
        return $this->plan_service->getData($request->all());
    }

    public function create()
    {
        return $this->formView();
    }

    public function edit($manufacturing_plan_id)
    {
        $plan = $this->plan_service->getById($manufacturing_plan_id);
        if (!$plan) {
            return redirect('admin/manufacturing-plan')->with('error', Message::NOTFOUND);
        }
        return $this->formView($plan);
    }

    public function show($manufacturing_plan_id)
    {
        $plan = $this->plan_service->getById($manufacturing_plan_id);
        if (!$plan) {
            return redirect('admin/manufacturing-plan')->with('error', Message::NOTFOUND);
        }
        return view('admin.manufacturing.plans.show', compact('plan'));
    }

    protected function formView($plan = null)
    {
        $business = $this->business_service->getAll();
        $branches = $this->branch_service->getAllActive();
        $products = $this->product_service->getAllActive();
        return view('admin.manufacturing.plans.create', compact('plan', 'business', 'branches', 'products'));
    }

    /**
     * The single recipe a finished-good variation auto-loads (with its
     * component lines + each line's consumption warehouse) - the create
     * form's live "quantity x recipe" table renders straight from this.
     */
    public function recipeForVariation($product_variation_id)
    {
        $recipe = $this->recipe_service->getForVariation($product_variation_id);
        if (!$recipe) {
            return $this->error('No recipe found for this product. Create one first.');
        }
        return $this->success(Message::FETCH, $recipe);
    }

    /**
     * Plans a Production can be created against - materials already
     * reserved, not yet fully produced. Used by the Production create form's
     * plan dropdown.
     */
    public function eligible(Request $request)
    {
        return $this->success(Message::FETCH, $this->plan_service->getEligibleForProduction($request->all()));
    }

    public function store(Request $request)
    {
        $rules = [
            'product_id' => 'required|exists:products,product_id',
            'product_variation_id' => 'required|exists:product_variations,product_variation_id',
            'product_recipe_id' => 'required|exists:product_recipes,product_recipe_id',
            'planned_quantity' => 'required|numeric|min:0.0001',
            'planned_unit_id' => 'nullable|exists:units,unit_id',
            'plan_date' => 'nullable|date',
        ];

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return redirect()->back()->withErrors($validate)->withInput();
        }

        $obj = $request->only([
            'manufacturing_plan_id', 'product_id', 'product_variation_id', 'product_recipe_id',
            'planned_quantity', 'planned_unit_id', 'plan_date',
        ]);
        $obj['business_id'] = $request->business_id ?? Auth::user()->business_id;
        $obj['branch_id'] = $request->branch_id ?? Auth::user()->branch_id;

        try {
            $this->plan_service->save($obj);
            return redirect('admin/manufacturing-plan')
                ->with('success', empty($request->manufacturing_plan_id) ? Message::SAVE : Message::UPDATE);
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function destroy($manufacturing_plan_id)
    {
        try {
            $plan = $this->plan_service->getById($manufacturing_plan_id);
            if ($plan && $plan->status !== ManufacturingPlanStatus::DRAFT) {
                throw new Exception('Only a Draft plan can be deleted - cancel it instead.');
            }
            $this->plan_service->cancel($manufacturing_plan_id, 'Deleted while still Draft');
            return $this->success(Message::DELETE, null);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function confirm($manufacturing_plan_id)
    {
        try {
            $this->plan_service->confirm($manufacturing_plan_id);
            return $this->success('Materials reserved successfully.', null);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function cancel(Request $request, $manufacturing_plan_id)
    {
        try {
            $this->plan_service->cancel($manufacturing_plan_id, $request->cancel_reason);
            return $this->success(Message::UPDATE, null);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
