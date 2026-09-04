<?php

namespace App\Http\Controllers\Admin\Manufacturing;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\Manufacturing\ProductRecipeService;
use App\Services\Concrete\Admin\ProductService;
use App\Services\Concrete\Admin\UnitService;
use App\Services\Concrete\Admin\WarehouseService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * Recipe/BOM has a single page, not a list+create+edit+show set: pick a
 * product/variation and its existing recipe (if any) loads in place for
 * editing - add/remove/edit a line and Save right there. See
 * ProductRecipeService::getForVariation().
 */
class ProductRecipeController extends Controller
{
    use ResponseAPI;

    protected $recipe_service;
    protected $product_service;
    protected $unit_service;
    protected $business_service;
    protected $warehouse_service;

    public function __construct(
        ProductRecipeService $recipe_service,
        ProductService $product_service,
        UnitService $unit_service,
        BusinessService $business_service,
        WarehouseService $warehouse_service
    ) {
        $this->middleware('permission:recipe.view')->only(['create', 'forVariation']);
        $this->middleware('permission:recipe.create|recipe.edit')->only(['storeItem', 'destroyItem']);
        $this->middleware('module:recipe');

        $this->recipe_service = $recipe_service;
        $this->product_service = $product_service;
        $this->unit_service = $unit_service;
        $this->business_service = $business_service;
        $this->warehouse_service = $warehouse_service;
    }

    public function create()
    {
        $business = $this->business_service->getAll();
        $units = $this->unit_service->getAllActive();
        $products = $this->product_service->getAllActive();
        $warehouses = $this->warehouse_service->getAllForCurrentUser();
        return view('admin.manufacturing.recipes.create', compact('business', 'units', 'products', 'warehouses'));
    }

    /**
     * The existing recipe (any non-archived status) for a finished-good
     * variation, if one exists - the page's JS uses this to populate itself
     * in place when a variation is selected.
     */
    public function forVariation($product_variation_id)
    {
        $recipe = $this->recipe_service->getForVariation($product_variation_id);
        if (!$recipe) {
            return $this->error('No recipe found - you can create one now.');
        }
        return $this->success(Message::FETCH, $recipe);
    }

    /**
     * Add one raw-material line via AJAX from the "Add Raw Material" popup -
     * saved immediately, no separate "Save Recipe" step. Lazily creates the
     * recipe header the first time a line is added for this variation.
     */
    public function storeItem(Request $request)
    {
        $rules = [
            'product_id' => 'required|exists:products,product_id',
            'product_variation_id' => 'required|exists:product_variations,product_variation_id',
            'raw_material_product_id' => 'required|exists:products,product_id',
            'raw_material_product_variation_id' => 'required|exists:product_variations,product_variation_id',
            'quantity' => 'required|numeric|min:0.0001',
            'warehouse_id' => 'required|exists:warehouses,warehouse_id',
        ];

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return $this->error($validate->errors()->first());
        }

        $obj = $request->only([
            'product_id', 'product_variation_id', 'raw_material_product_id',
            'raw_material_product_variation_id', 'quantity', 'warehouse_id',
        ]);
        $obj['business_id'] = $request->business_id ?? Auth::user()->business_id;

        try {
            $item = $this->recipe_service->addItem($obj);
            return $this->success(Message::SAVE, $item);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Remove one raw-material line via AJAX from the items table - deleted
     * immediately, no separate "Save Recipe" step.
     */
    public function destroyItem($product_recipe_item_id)
    {
        try {
            $this->recipe_service->removeItem($product_recipe_item_id);
            return $this->success(Message::DELETE, null);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
