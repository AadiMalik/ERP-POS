<?php

namespace App\Services\Concrete\Admin\Manufacturing;

use App\Models\ManufacturingPlan;
use App\Models\Production;
use App\Models\ProductRecipe;
use App\Models\ProductRecipeItem;
use App\Models\ProductVariation;
use App\Models\Warehouse;
use App\Repository\Repository;
use App\Traits\Auditable;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Exactly one Recipe/BOM per finished-good product variation, edited
 * directly in place - no list page, no versioning. save() upserts by
 * product_variation_id: finds the existing recipe for that variation (if
 * any), replaces its raw-material lines, or creates a new recipe if none
 * exists yet. See ProductRecipeController - the single page loads the
 * existing recipe via getForVariation() when a variation is selected.
 */
class ProductRecipeService
{
    use Auditable;

    protected $model_recipe;
    protected $with = ['business', 'product', 'productVariation', 'items.rawMaterialProduct', 'items.rawMaterialVariation', 'items.unit', 'items.warehouse'];

    public function __construct()
    {
        $this->model_recipe = new Repository(new ProductRecipe());
    }

    /**
     * The recipe for a finished-good variation, if one exists - the page's
     * JS uses this to populate itself in place when a variation is selected.
     */
    public function getForVariation($product_variation_id)
    {
        return $this->model_recipe->getModel()::with($this->with)
            ->where('product_variation_id', $product_variation_id)
            ->where('is_deleted', 0)
            ->first();
    }

    public function getById($id)
    {
        return $this->model_recipe->getModel()::with($this->with)
            ->where('product_recipe_id', $id)
            ->where('is_deleted', 0)
            ->first();
    }

    /**
     * Whether this recipe is locked onto a Manufacturing Plan or Production
     * - such a plan/production already has its own immutable
     * manufacturing_plan_materials snapshot from confirm-time, so editing the
     * recipe afterwards is safe (it only affects future plans); this is
     * purely informational, not a save() gate.
     */
    public function isInUse($product_recipe_id): bool
    {
        return ManufacturingPlan::where('product_recipe_id', $product_recipe_id)->where('is_deleted', 0)->exists()
            || Production::where('product_recipe_id', $product_recipe_id)->where('is_deleted', 0)->exists();
    }

    /**
     * Add exactly one raw-material line, saved immediately (the Recipe page
     * has no separate "Save Recipe" step - each Add Raw Material popup
     * persists on its own). Lazily creates the recipe header the first time
     * a line is added for a given finished-good variation.
     *
     * @param array $obj product_id/product_variation_id (finished good) +
     *                   raw_material_product_id/raw_material_product_variation_id/
     *                   quantity/warehouse_id (the new line) + business_id
     */
    public function addItem(array $obj)
    {
        DB::beginTransaction();
        try {
            if ((float) ($obj['quantity'] ?? 0) <= 0) {
                throw new Exception('Quantity must be greater than zero.');
            }
            if (($obj['raw_material_product_variation_id'] ?? null) === ($obj['product_variation_id'] ?? null)) {
                throw new Exception('A recipe cannot consume the same product/variation it manufactures.');
            }

            $variation = ProductVariation::find($obj['product_variation_id'] ?? null);
            if (!$variation) {
                throw new Exception('The finished-good product variation was not found.');
            }

            $rawVariation = ProductVariation::find($obj['raw_material_product_variation_id'] ?? null);
            if (!$rawVariation) {
                throw new Exception('The selected raw material variation was not found.');
            }
            if (empty($obj['warehouse_id'])) {
                throw new Exception('Select the warehouse this raw material is consumed from.');
            }
            if (!Warehouse::where('warehouse_id', $obj['warehouse_id'])->where('business_id', $obj['business_id'])->where('is_deleted', 0)->exists()) {
                throw new Exception('The selected warehouse does not belong to this business.');
            }

            $recipe = ProductRecipe::where('product_variation_id', $obj['product_variation_id'])
                ->where('is_deleted', 0)
                ->first();

            if (!$recipe) {
                $recipe = ProductRecipe::create([
                    'product_recipe_id' => generateUuid(),
                    'business_id' => $obj['business_id'],
                    'product_id' => $obj['product_id'],
                    'product_variation_id' => $obj['product_variation_id'],
                    'createdby_id' => Auth::id(),
                    'date_created' => now(),
                ]);
            } elseif (ProductRecipeItem::where('product_recipe_id', $recipe->product_recipe_id)
                ->where('raw_material_product_variation_id', $obj['raw_material_product_variation_id'])
                ->exists()) {
                throw new Exception('"' . $rawVariation->name . '" is already added to this recipe.');
            }

            $item = ProductRecipeItem::create([
                'product_recipe_item_id' => generateUuid(),
                'product_recipe_id' => $recipe->product_recipe_id,
                'raw_material_product_id' => $obj['raw_material_product_id'],
                'raw_material_product_variation_id' => $obj['raw_material_product_variation_id'],
                'quantity' => $obj['quantity'],
                // Recipe quantities are always in the raw material's own
                // base unit - store it explicitly, no unit conversion needed.
                'unit_id' => $rawVariation->base_unit_id,
                'warehouse_id' => $obj['warehouse_id'],
                'date_created' => now(),
            ]);

            $this->logActivity('recipe', $recipe->product_recipe_id, 'add_item', null, $item->fresh()->toArray());

            DB::commit();
            return ProductRecipeItem::with(['rawMaterialProduct', 'rawMaterialVariation', 'unit', 'warehouse'])
                ->find($item->product_recipe_item_id);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Remove exactly one raw-material line - deleted immediately from the
     * table, no separate save step.
     */
    public function removeItem($product_recipe_item_id)
    {
        $item = ProductRecipeItem::find($product_recipe_item_id);
        if (!$item) {
            throw new Exception('Recipe line not found.');
        }

        $recipeId = $item->product_recipe_id;
        $item->delete();

        $this->logActivity('recipe', $recipeId, 'remove_item', $item->toArray(), null);

        return true;
    }
}
