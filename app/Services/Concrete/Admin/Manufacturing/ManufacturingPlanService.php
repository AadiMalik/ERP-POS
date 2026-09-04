<?php

namespace App\Services\Concrete\Admin\Manufacturing;

use App\Enums\Filter;
use App\Enums\ManufacturingPlanStatus;
use App\Enums\RoleNames;
use App\Models\ManufacturingPlan;
use App\Models\ManufacturingPlanMaterial;
use App\Models\ProductRecipe;
use App\Models\ProductVariation;
use App\Models\ProductVariationStock;
use App\Repository\Repository;
use App\Traits\Auditable;
use App\Traits\ValidatesWarehouse;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

/**
 * A Manufacturing Plan is intent + reservation only. confirm() is the only
 * method that reserves stock (increments ProductVariationStock.
 * reserved_quantity for every recipe raw material, scaled to
 * planned_quantity) - it never writes a product_variation_stock_transactions
 * row, because no physical movement happened yet. Finished stock only
 * increases when a linked Production completes (ProductionService::complete()).
 */
class ManufacturingPlanService
{
    use Auditable;
    use ValidatesWarehouse;

    protected $model_plan;
    protected $with = ['business', 'branch', 'product', 'productVariation', 'recipe.items', 'plannedUnit', 'materials.productVariation', 'materials.unit', 'materials.warehouse', 'productions', 'createdby', 'approvedby'];

    public function __construct()
    {
        $this->model_plan = new Repository(new ManufacturingPlan());
    }

    public function getData($obj)
    {
        $wh = [['is_deleted', 0]];
        $orderBy = Filter::ORDERBY;

        if (isset($obj['orderBy']) && $obj['orderBy'] !== 0 && $obj['orderBy'] !== '') {
            $orderBy = $obj['orderBy'];
        }
        if (!empty($obj['business_id'])) {
            $wh[] = ['business_id', $obj['business_id']];
        }
        if (!empty($obj['branch_id'])) {
            $wh[] = ['branch_id', $obj['branch_id']];
        }
        if (!empty($obj['product_id'])) {
            $wh[] = ['product_id', $obj['product_id']];
        }
        if (!empty($obj['status'])) {
            $wh[] = ['status', $obj['status']];
        }
        if (!empty($obj['start_date'])) {
            $wh[] = ['date_created', '>=', Carbon::parse($obj['start_date'])->startOfDay()];
        }
        if (!empty($obj['end_date'])) {
            $wh[] = ['date_created', '<=', Carbon::parse($obj['end_date'])->endOfDay()];
        }

        $allow_roles = [
            RoleNames::SUPERADMIN,
            RoleNames::BUSINESSADMIN,
            RoleNames::INVENTORYMANAGER,
            RoleNames::BRANCHADMIN,
        ];

        $datatable = $this->model_plan->getModel()::where($wh)
            ->with(['business', 'branch', 'product', 'productVariation', 'recipe'])
            ->orderBy('date_created', $orderBy);
        $datatable = applyRoleScope($datatable, $allow_roles);

        $labels = ManufacturingPlanStatus::getOptions();

        return DataTables::of($datatable)
            ->addColumn('business', fn ($item) => $item->business?->name ?? '-')
            ->addColumn('branch', fn ($item) => $item->branch?->name ?? '-')
            ->addColumn('product', fn ($item) => $item->productVariation?->name ?? $item->product?->name ?? '-')
            ->addColumn('plan_date', fn ($item) => $item->plan_date ? localDate($item->plan_date) : '-')
            ->addColumn('planned_quantity', fn ($item) => decimal($item->planned_quantity))
            ->addColumn('produced_quantity', fn ($item) => decimal($item->produced_quantity))
            ->addColumn('progress', fn ($item) => $item->progress_percentage . '%')
            ->addColumn('status', function ($item) use ($labels) {
                $class = match ($item->status) {
                    ManufacturingPlanStatus::COMPLETED => 'success',
                    ManufacturingPlanStatus::CANCELLED => 'danger',
                    ManufacturingPlanStatus::DRAFT => 'secondary',
                    default => 'info',
                };
                return "<span class='badge bg-{$class}'>" . ($labels[$item->status] ?? $item->status) . '</span>';
            })
            ->addColumn('action', function ($item) {
                $id = $item->manufacturing_plan_id;
                $btns = '';
                if (auth()->user()->can('manufacturing-plan.view')) {
                    $btns .= "<a href='" . url('admin/manufacturing-plan/show/' . $id) . "' class='btn btn-sm btn-icon btn-info' title='View'><i class='fa fa-eye'></i></a>";
                }
                if (auth()->user()->can('manufacturing-plan.edit') && $item->status === ManufacturingPlanStatus::DRAFT) {
                    $btns .= "<a href='" . url('admin/manufacturing-plan/edit/' . $id) . "' class='btn btn-sm btn-icon btn-primary' title='Edit'><i class='fa fa-pencil'></i></a>";
                }
                if (auth()->user()->can('manufacturing-plan.delete') && $item->status === ManufacturingPlanStatus::DRAFT) {
                    $btns .= "<button type='button' class='btn btn-sm btn-icon btn-danger delete' data-id='{$id}' title='Delete'><i class='fa fa-trash'></i></button>";
                }
                return "<div class='d-flex gap-1 flex-wrap'>{$btns}</div>";
            })
            ->rawColumns(['status', 'action'])
            ->make(true);
    }

    public function getById($id)
    {
        return $this->model_plan->getModel()::with($this->with)
            ->where('manufacturing_plan_id', $id)
            ->where('is_deleted', 0)
            ->first();
    }

    /**
     * Plans eligible for a new Production against them - materials already
     * reserved (confirmed), not yet fully produced. Powers the Production
     * create form's plan dropdown, which shows each plan's remaining
     * quantity so the user can't type more than what's left.
     */
    public function getEligibleForProduction(array $filters = [])
    {
        $allow_roles = [
            RoleNames::SUPERADMIN,
            RoleNames::BUSINESSADMIN,
            RoleNames::INVENTORYMANAGER,
            RoleNames::BRANCHADMIN,
        ];

        $query = ManufacturingPlan::with(['productVariation'])
            ->where('is_deleted', 0)
            ->where('status', ManufacturingPlanStatus::NOT_COMPLETE);

        if (!empty($filters['business_id'])) {
            $query->where('business_id', $filters['business_id']);
        }

        $query = applyRoleScope($query, $allow_roles);

        return $query->orderByDesc('date_created')->get()->map(function ($plan) {
            return [
                'manufacturing_plan_id' => $plan->manufacturing_plan_id,
                'plan_no' => $plan->plan_no,
                'product_name' => $plan->productVariation->name ?? '-',
                'planned_quantity' => (float) $plan->planned_quantity,
                'produced_quantity' => (float) $plan->produced_quantity,
                'remaining_quantity' => $plan->remaining_quantity,
            ];
        });
    }

    public function save(array $obj)
    {
        if ((float) ($obj['planned_quantity'] ?? 0) <= 0) {
            throw new Exception('Planned quantity must be greater than zero.');
        }

        $isUpdate = !empty($obj['manufacturing_plan_id']);

        if ($isUpdate) {
            $plan = $this->getById($obj['manufacturing_plan_id']);
            if (!$plan) {
                throw new Exception('Manufacturing plan not found.');
            }
            if ($plan->status !== ManufacturingPlanStatus::DRAFT) {
                throw new Exception('Only a Draft plan can be edited - cancel and re-create it instead.');
            }
            $obj['updatedby_id'] = Auth::id();
            $obj['date_updated'] = now();
            $plan->update($obj);
        } else {
            $obj['manufacturing_plan_id'] = generateUuid();
            $obj['plan_no'] = $obj['plan_no'] ?? ('MP-' . strtoupper(substr($obj['manufacturing_plan_id'], 0, 8)));
            $obj['plan_date'] = $obj['plan_date'] ?? now()->toDateString();
            $obj['status'] = ManufacturingPlanStatus::DRAFT;
            $obj['is_complete'] = false;
            $obj['produced_quantity'] = 0;
            $obj['createdby_id'] = Auth::id();
            $obj['date_created'] = now();
            $plan = ManufacturingPlan::create($obj);
        }

        $this->logActivity('manufacturing-plan', $plan->manufacturing_plan_id, $isUpdate ? 'update' : 'create', null, $plan->fresh()->toArray());

        return $this->getById($plan->manufacturing_plan_id);
    }

    /**
     * Explode the plan's locked recipe x planned_quantity into
     * manufacturing_plan_materials, then reserve that exact quantity against
     * each raw material's ProductVariationStock at the plan's warehouse
     * (locked, additive - never touches quantity itself). Idempotent: calling
     * confirm() twice on an already-confirmed plan is rejected outright.
     */
    public function confirm($manufacturing_plan_id)
    {
        DB::beginTransaction();
        try {
            $plan = ManufacturingPlan::where('manufacturing_plan_id', $manufacturing_plan_id)
                ->where('is_deleted', 0)
                ->lockForUpdate()
                ->first();

            if (!$plan) {
                throw new Exception('Manufacturing plan not found.');
            }
            if ($plan->status !== ManufacturingPlanStatus::DRAFT) {
                throw new Exception('Only a Draft plan can be confirmed.');
            }

            $recipe = ProductRecipe::with('items')->find($plan->product_recipe_id);
            if (!$recipe || $recipe->items->isEmpty()) {
                throw new Exception('The selected recipe has no raw material components.');
            }

            foreach ($recipe->items as $item) {
                $rawVariation = ProductVariation::find($item->raw_material_product_variation_id);
                if (!$rawVariation) {
                    throw new Exception('A recipe component references a raw material that no longer exists.');
                }
                if (empty($item->warehouse_id)) {
                    throw new Exception('Raw material "' . $rawVariation->name . '" has no consumption warehouse set on the recipe - edit the recipe first.');
                }

                $this->assertValidWarehouse($plan->business_id, $plan->branch_id, $item->warehouse_id);

                // Recipe quantities are always expressed in the raw
                // material's own base unit and per one finished unit - a
                // straight multiply, no yield/conversion/wastage math.
                $required = (float) $item->quantity * (float) $plan->planned_quantity;

                $stock = ProductVariationStock::where('business_id', $plan->business_id)
                    ->where('warehouse_id', $item->warehouse_id)
                    ->where('product_id', $item->raw_material_product_id)
                    ->where('product_variation_id', $item->raw_material_product_variation_id)
                    ->lockForUpdate()
                    ->first();

                $available = ((float) ($stock->quantity ?? 0)) - ((float) ($stock->reserved_quantity ?? 0));
                if ($required > $available) {
                    throw new Exception('Insufficient available stock for raw material "' . ($rawVariation->name ?? 'item') . '" to reserve this plan. Available: ' . round($available, 4) . ', required: ' . round($required, 4) . '.');
                }

                if ($stock) {
                    $stock->update(['reserved_quantity' => (float) $stock->reserved_quantity + $required]);
                }

                ManufacturingPlanMaterial::create([
                    'manufacturing_plan_material_id' => generateUuid(),
                    'manufacturing_plan_id' => $plan->manufacturing_plan_id,
                    'product_id' => $item->raw_material_product_id,
                    'product_variation_id' => $item->raw_material_product_variation_id,
                    'unit_id' => $rawVariation->base_unit_id,
                    'warehouse_id' => $item->warehouse_id,
                    'required_base_quantity' => $required,
                    'reserved_quantity' => $required,
                    'consumed_quantity' => 0,
                    'date_created' => now(),
                ]);
            }

            $plan->update([
                'status' => ManufacturingPlanStatus::NOT_COMPLETE,
                'confirmed_at' => now(),
                'approvedby_id' => Auth::id(),
                'updatedby_id' => Auth::id(),
                'date_updated' => now(),
            ]);

            $this->logActivity('manufacturing-plan', $plan->manufacturing_plan_id, 'confirm', null, $plan->fresh()->toArray());

            DB::commit();
            return $this->getById($plan->manufacturing_plan_id);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Release every material this plan still holds reserved (whatever wasn't
     * already consumed by a completed Production) and mark it cancelled.
     */
    public function cancel($manufacturing_plan_id, $reason = null)
    {
        DB::beginTransaction();
        try {
            $plan = ManufacturingPlan::where('manufacturing_plan_id', $manufacturing_plan_id)
                ->where('is_deleted', 0)
                ->lockForUpdate()
                ->first();

            if (!$plan) {
                throw new Exception('Manufacturing plan not found.');
            }
            if (in_array($plan->status, ManufacturingPlanStatus::terminal(), true)) {
                throw new Exception('This plan is already ' . $plan->status . '.');
            }

            $this->releaseReservations($plan);

            $plan->update([
                'status' => ManufacturingPlanStatus::CANCELLED,
                'cancelled_at' => now(),
                'cancel_reason' => $reason,
                'updatedby_id' => Auth::id(),
                'date_updated' => now(),
            ]);

            $this->logActivity('manufacturing-plan', $plan->manufacturing_plan_id, 'cancel', null, $plan->fresh()->toArray());

            DB::commit();
            return $this->getById($plan->manufacturing_plan_id);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Release the outstanding (reserved - consumed) quantity for every
     * material row back onto ProductVariationStock.reserved_quantity, locked.
     * Called by cancel() and, per-material, whenever a Production that
     * consumed against this plan is itself cancelled before completion.
     */
    public function releaseReservations(ManufacturingPlan $plan): void
    {
        $materials = ManufacturingPlanMaterial::where('manufacturing_plan_id', $plan->manufacturing_plan_id)->get();

        foreach ($materials as $material) {
            $outstanding = (float) $material->reserved_quantity - (float) $material->consumed_quantity;
            if ($outstanding <= 0.0001) {
                continue;
            }

            $stock = ProductVariationStock::where('business_id', $plan->business_id)
                ->where('warehouse_id', $material->warehouse_id)
                ->where('product_id', $material->product_id)
                ->where('product_variation_id', $material->product_variation_id)
                ->lockForUpdate()
                ->first();

            if ($stock) {
                $stock->update(['reserved_quantity' => max(0, (float) $stock->reserved_quantity - $outstanding)]);
            }

            $material->update(['reserved_quantity' => (float) $material->consumed_quantity]);
        }
    }

    /**
     * Recompute produced_quantity/status from this plan's non-cancelled
     * Productions. Called after every Production complete()/cancel().
     */
    public function recomputeProgress($manufacturing_plan_id): void
    {
        $plan = ManufacturingPlan::where('manufacturing_plan_id', $manufacturing_plan_id)->lockForUpdate()->first();
        if (!$plan) {
            return;
        }

        $produced = $plan->productions()->where('status', 'completed')->sum('quantity');
        $status = $plan->status;

        // CANCELLED is the only status a production revert/complete must
        // never override; COMPLETED must still be able to drop back to
        // NOT_COMPLETE if a completed production is later voided.
        if ($status !== ManufacturingPlanStatus::CANCELLED) {
            $status = $produced >= (float) $plan->planned_quantity
                ? ManufacturingPlanStatus::COMPLETED
                : ManufacturingPlanStatus::NOT_COMPLETE;
        }

        $plan->update([
            'produced_quantity' => $produced,
            'status' => $status,
            'is_complete' => $status === ManufacturingPlanStatus::COMPLETED,
            'updatedby_id' => Auth::id(),
            'date_updated' => now(),
        ]);
    }
}
