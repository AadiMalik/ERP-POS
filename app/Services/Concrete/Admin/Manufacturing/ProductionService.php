<?php

namespace App\Services\Concrete\Admin\Manufacturing;

use App\Enums\Filter;
use App\Enums\ManufacturingPlanStatus;
use App\Enums\ProductionStatus;
use App\Enums\ReferenceType;
use App\Enums\RoleNames;
use App\Enums\TransactionType;
use App\Models\InventorySetting;
use App\Models\ManufacturingPlan;
use App\Models\ManufacturingPlanMaterial;
use App\Models\Production;
use App\Models\ProductionConsumption;
use App\Models\ProductVariation;
use App\Models\ProductVariationStock;
use App\Models\ProductVariationStockTransaction;
use App\Repository\Repository;
use App\Services\Concrete\Admin\ProductVariationStockService;
use App\Traits\Auditable;
use App\Traits\ValidatesWarehouse;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

/**
 * One independent manufacturing batch/run against a parent Manufacturing
 * Plan. save() never touches stock. complete() is the only method that
 * writes product_variation_stock_transactions rows: it consumes raw
 * materials against the plan's own manufacturing_plan_materials reservation
 * snapshot (written once by ManufacturingPlanService::confirm(), never the
 * recipe's current live items - the recipe can be edited or have lines
 * removed at any point after a plan is confirmed against it, and that must
 * never affect what was already reserved), releasing the matching
 * reservation as it goes, receives the finished good into THIS production's
 * own warehouse/batch/expiry, and posts the accounting entries for any
 * labor/overhead/other cost. cancel() on an already-completed production
 * reverses all of it via the same shared reversal helpers GRN/Order use,
 * then re-reserves the raw materials it had consumed.
 */
class ProductionService
{
    use Auditable;
    use ValidatesWarehouse;

    protected $model_production;
    protected $with = ['business', 'branch', 'plan', 'recipe', 'warehouse', 'unit', 'operator', 'consumptions.productVariation', 'consumptions.batch', 'createdby'];

    public function __construct()
    {
        $this->model_production = new Repository(new Production());
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
        if (!empty($obj['warehouse_id'])) {
            $wh[] = ['warehouse_id', $obj['warehouse_id']];
        }
        if (!empty($obj['manufacturing_plan_id'])) {
            $wh[] = ['manufacturing_plan_id', $obj['manufacturing_plan_id']];
        }
        if (!empty($obj['status'])) {
            $wh[] = ['status', $obj['status']];
        }
        if (!empty($obj['batch_no'])) {
            $wh[] = ['batch_no', 'like', '%' . $obj['batch_no'] . '%'];
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

        $datatable = $this->model_production->getModel()::where($wh)
            ->with(['business', 'branch', 'plan.productVariation', 'warehouse'])
            ->orderBy('date_created', $orderBy);
        $datatable = applyRoleScope($datatable, $allow_roles);

        $labels = ProductionStatus::getOptions();

        return DataTables::of($datatable)
            ->addColumn('business', fn ($item) => $item->business?->name ?? '-')
            ->addColumn('branch', fn ($item) => $item->branch?->name ?? '-')
            ->addColumn('product', fn ($item) => $item->plan?->productVariation?->name ?? '-')
            ->addColumn('warehouse', fn ($item) => $item->warehouse?->name ?? '-')
            ->addColumn('quantity', fn ($item) => decimal($item->quantity))
            ->addColumn('batch_no', fn ($item) => $item->batch_no ?? '-')
            ->addColumn('expiry_date', fn ($item) => $item->expiry_date ? localDate($item->expiry_date) : '-')
            ->addColumn('unit_cost', fn ($item) => currency($item->unit_cost))
            ->addColumn('status', function ($item) use ($labels) {
                $class = match ($item->status) {
                    ProductionStatus::COMPLETED => 'success',
                    ProductionStatus::CANCELLED => 'danger',
                    ProductionStatus::DRAFT => 'secondary',
                    default => 'info',
                };
                return "<span class='badge bg-{$class}'>" . ($labels[$item->status] ?? $item->status) . '</span>';
            })
            ->addColumn('action', function ($item) {
                $id = $item->production_id;
                $btns = '';
                if (auth()->user()->can('production.view')) {
                    $btns .= "<a href='" . url('admin/production/show/' . $id) . "' class='btn btn-sm btn-icon btn-info' title='View'><i class='fa fa-eye'></i></a>";
                }
                if (auth()->user()->can('production.edit') && $item->status === ProductionStatus::DRAFT) {
                    $btns .= "<a href='" . url('admin/production/edit/' . $id) . "' class='btn btn-sm btn-icon btn-primary' title='Edit'><i class='fa fa-pencil'></i></a>";
                }
                return "<div class='d-flex gap-1 flex-wrap'>{$btns}</div>";
            })
            ->rawColumns(['status', 'action'])
            ->make(true);
    }

    public function getById($id)
    {
        return $this->model_production->getModel()::with($this->with)
            ->where('production_id', $id)
            ->where('is_deleted', 0)
            ->first();
    }

    public function save(array $obj)
    {
        if ((float) ($obj['quantity'] ?? 0) <= 0) {
            throw new Exception('Production quantity must be greater than zero.');
        }

        $plan = ManufacturingPlan::find($obj['manufacturing_plan_id'] ?? null);
        if (!$plan) {
            throw new Exception('Manufacturing plan not found.');
        }
        if ($plan->status !== ManufacturingPlanStatus::NOT_COMPLETE) {
            throw new Exception('The parent plan must be Approved (not complete) before a Production can be created against it.');
        }

        $requestedQty = (float) $obj['quantity'];
        $remaining = $plan->remaining_quantity;

        // Strict cap, no override: a production can never exceed what its
        // plan still has remaining.
        if ($requestedQty > $remaining + 0.0001) {
            throw new Exception('Production quantity (' . $requestedQty . ') exceeds the plan\'s remaining quantity (' . $remaining . ').');
        }

        $obj['business_id'] = $plan->business_id;
        $obj['branch_id'] = $obj['branch_id'] ?? $plan->branch_id;
        $obj['product_recipe_id'] = $obj['product_recipe_id'] ?? $plan->product_recipe_id;

        $this->assertValidWarehouse($obj['business_id'], $obj['branch_id'], $obj['warehouse_id'] ?? null);

        $isUpdate = !empty($obj['production_id']);

        if ($isUpdate) {
            $production = Production::find($obj['production_id']);
            if (!$production) {
                throw new Exception('Production not found.');
            }
            if ($production->status !== ProductionStatus::DRAFT) {
                throw new Exception('Only a Draft production can be edited.');
            }
            $obj['updatedby_id'] = Auth::id();
            $obj['date_updated'] = now();
            $production->update($obj);
        } else {
            $obj['production_id'] = generateUuid();
            $obj['production_no'] = $obj['production_no'] ?? ('PR-' . strtoupper(substr($obj['production_id'], 0, 8)));
            // Batch/lot number is always system-generated, never typed by
            // hand - it doubles as the production's own reference number.
            $obj['batch_no'] = $obj['production_no'];
            $obj['status'] = ProductionStatus::DRAFT;
            $obj['createdby_id'] = Auth::id();
            $obj['date_created'] = now();
            $production = Production::create($obj);
        }

        $this->logActivity('production', $production->production_id, $isUpdate ? 'update' : 'create', null, $production->fresh()->toArray());

        return $this->getById($production->production_id);
    }

    /**
     * Consume raw materials (per the plan's manufacturing_plan_materials
     * reservation snapshot, scaled to this production's quantity) from each
     * material's own reservation warehouse, release the matching
     * plan-material reservation, receive the finished good into this
     * production's own warehouse/batch/expiry, and post accounting for any
     * labor/overhead/other cost - all inside one DB transaction so stock,
     * reservation, production, and ledger data can never go
     * partially-updated.
     */
    public function complete($production_id)
    {
        DB::beginTransaction();
        try {
            $production = Production::where('production_id', $production_id)->where('is_deleted', 0)->lockForUpdate()->first();
            if (!$production) {
                throw new Exception('Production not found.');
            }
            if ($production->status !== ProductionStatus::DRAFT) {
                throw new Exception('Only a Draft production can be completed.');
            }

            $plan = ManufacturingPlan::where('manufacturing_plan_id', $production->manufacturing_plan_id)->lockForUpdate()->first();
            if (!$plan) {
                throw new Exception('Parent manufacturing plan not found.');
            }
            if (in_array($plan->status, ManufacturingPlanStatus::terminal(), true)) {
                throw new Exception('The parent plan is ' . $plan->status . ' and can no longer be produced against.');
            }

            $this->assertValidWarehouse($production->business_id, $production->branch_id, $production->warehouse_id);

            // Consume against the plan's own material reservation snapshot
            // (manufacturing_plan_materials, written once at confirm() time)
            // - never against the recipe's current live items. The recipe can
            // be edited (or have lines removed) at any time after a plan is
            // confirmed against it; what was actually reserved must still be
            // producible regardless of what the recipe looks like now.
            $planMaterials = ManufacturingPlanMaterial::where('manufacturing_plan_id', $plan->manufacturing_plan_id)->get();
            if ($planMaterials->isEmpty()) {
                throw new Exception('This plan has no reserved raw materials - confirm the plan first.');
            }

            $inventorySetting = InventorySetting::where('business_id', $production->business_id)->first();
            $stockService = app(ProductVariationStockService::class);

            $materialCost = 0.0;

            foreach ($planMaterials as $planMaterialRow) {
                $rawVariation = ProductVariation::find($planMaterialRow->product_variation_id);
                if (!$rawVariation) {
                    throw new Exception('A reserved raw material no longer exists.');
                }

                $planMaterial = ManufacturingPlanMaterial::where('manufacturing_plan_material_id', $planMaterialRow->manufacturing_plan_material_id)
                    ->lockForUpdate()
                    ->first();
                $materialWarehouseId = $planMaterial->warehouse_id;

                // required_base_quantity is the total reserved for the
                // plan's whole planned_quantity - scale it down to a
                // per-unit rate, then back up to this production's quantity.
                $perUnit = (float) $plan->planned_quantity > 0
                    ? (float) $planMaterial->required_base_quantity / (float) $plan->planned_quantity
                    : 0;
                $requiredBase = $perUnit * (float) $production->quantity;

                $isBatchTracked = $rawVariation->track_batch || $rawVariation->track_expiry;
                $picks = null;

                if ($isBatchTracked) {
                    $picks = $stockService->pickBatchesForSale(
                        $production->business_id,
                        $materialWarehouseId,
                        $rawVariation->product_id,
                        $rawVariation->product_variation_id,
                        $requiredBase,
                        $inventorySetting
                    );
                    if ($picks === null) {
                        throw new Exception('Insufficient available (non-expired) batch stock for raw material "' . $rawVariation->name . '".');
                    }
                }

                $stock = ProductVariationStock::where('business_id', $production->business_id)
                    ->where('warehouse_id', $materialWarehouseId)
                    ->where('product_id', $rawVariation->product_id)
                    ->where('product_variation_id', $rawVariation->product_variation_id)
                    ->lockForUpdate()
                    ->first();

                $existingQty = (float) ($stock->quantity ?? 0);
                $existingAvg = (float) ($stock->avg_price ?? 0);
                $existingReserved = (float) ($stock->reserved_quantity ?? 0);

                if ($requiredBase > $existingQty + 0.0001) {
                    throw new Exception('Insufficient raw material stock for "' . $rawVariation->name . '". Available: ' . round($existingQty, 4) . ', required: ' . round($requiredBase, 4) . '.');
                }

                $lineCost = round($requiredBase * $existingAvg, 4);
                $materialCost += $lineCost;

                $stock->update([
                    'quantity' => $existingQty - $requiredBase,
                    'reserved_quantity' => max(0, $existingReserved - $requiredBase),
                ]);

                if (empty($picks)) {
                    $txn = ProductVariationStockTransaction::create([
                        'product_variation_stock_transaction_id' => generateUuid(),
                        'transaction_date' => now(),
                        'transaction_type' => TransactionType::PRODUCTION_OUT,
                        'business_id' => $production->business_id,
                        'product_id' => $rawVariation->product_id,
                        'product_variation_id' => $rawVariation->product_variation_id,
                        'warehouse_id' => $materialWarehouseId,
                        'unit_id' => $rawVariation->base_unit_id,
                        'quantity' => $requiredBase,
                        'base_quantity' => $requiredBase,
                        'unit_price' => $existingAvg,
                        'total_price' => $lineCost,
                        'quantity_after' => $existingQty - $requiredBase,
                        'avg_price_after' => $existingAvg,
                        'reference_id' => $production->production_id,
                        'reference_type' => ReferenceType::CONSUMPTION,
                        'remarks' => 'Consumed by Production ' . ($production->production_no ?? $production->production_id),
                        'createdby_id' => Auth::id(),
                        'date_created' => now(),
                    ]);

                    ProductionConsumption::create([
                        'production_consumption_id' => generateUuid(),
                        'production_id' => $production->production_id,
                        'product_id' => $rawVariation->product_id,
                        'product_variation_id' => $rawVariation->product_variation_id,
                        'warehouse_id' => $materialWarehouseId,
                        'base_quantity' => $requiredBase,
                        'unit_cost' => $existingAvg,
                        'total_cost' => $lineCost,
                        'product_variation_stock_transaction_id' => $txn->product_variation_stock_transaction_id,
                        'date_created' => now(),
                    ]);
                } else {
                    $runningQty = $existingQty;
                    foreach ($picks as $pick) {
                        $pickQty = $pick['base_quantity'];
                        $pickCost = round($pickQty * $existingAvg, 4);
                        $runningQty -= $pickQty;

                        $stockService->adjustBatchQuantity($pick['batch']->product_variation_batch_id, -$pickQty);

                        $txn = ProductVariationStockTransaction::create([
                            'product_variation_stock_transaction_id' => generateUuid(),
                            'transaction_date' => now(),
                            'transaction_type' => TransactionType::PRODUCTION_OUT,
                            'business_id' => $production->business_id,
                            'product_id' => $rawVariation->product_id,
                            'product_variation_id' => $rawVariation->product_variation_id,
                            'warehouse_id' => $materialWarehouseId,
                            'unit_id' => $rawVariation->base_unit_id,
                            'quantity' => $pickQty,
                            'base_quantity' => $pickQty,
                            'unit_price' => $existingAvg,
                            'total_price' => $pickCost,
                            'quantity_after' => $runningQty,
                            'avg_price_after' => $existingAvg,
                            'reference_id' => $production->production_id,
                            'reference_type' => ReferenceType::CONSUMPTION,
                            'remarks' => 'Consumed by Production ' . ($production->production_no ?? $production->production_id),
                            'product_variation_batch_id' => $pick['batch']->product_variation_batch_id,
                            'createdby_id' => Auth::id(),
                            'date_created' => now(),
                        ]);

                        ProductionConsumption::create([
                            'production_consumption_id' => generateUuid(),
                            'production_id' => $production->production_id,
                            'product_id' => $rawVariation->product_id,
                            'product_variation_id' => $rawVariation->product_variation_id,
                            'product_variation_batch_id' => $pick['batch']->product_variation_batch_id,
                            'warehouse_id' => $materialWarehouseId,
                            'base_quantity' => $pickQty,
                            'unit_cost' => $existingAvg,
                            'total_cost' => $pickCost,
                            'product_variation_stock_transaction_id' => $txn->product_variation_stock_transaction_id,
                            'date_created' => now(),
                        ]);
                    }
                }

                $planMaterial->update(['consumed_quantity' => (float) $planMaterial->consumed_quantity + $requiredBase]);
            }

            $totalCost = $materialCost + (float) $production->labor_cost + (float) $production->overhead_cost + (float) $production->other_cost;
            $unitCost = (float) $production->quantity > 0 ? $totalCost / (float) $production->quantity : 0;

            $this->receiveOutput(
                $production,
                $plan->product_id,
                $plan->product_variation_id,
                (float) $production->quantity,
                $totalCost
            );

            $production->update([
                'material_cost' => $materialCost,
                'total_cost' => $totalCost,
                'unit_cost' => $unitCost,
                'status' => ProductionStatus::COMPLETED,
                'completed_at' => now(),
                'updatedby_id' => Auth::id(),
                'date_updated' => now(),
            ]);

            app(ManufacturingAccountingService::class)->postProductionCost($production->fresh());
            app(ManufacturingPlanService::class)->recomputeProgress($plan->manufacturing_plan_id);

            $this->logActivity('production', $production_id, 'complete', null, $production->fresh()->toArray());

            DB::commit();
            return $this->getById($production_id);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Upsert the finished-good receipt into this production's warehouse
     * (weighted-average cost, batch/expiry from the production itself) and
     * write its product_variation_stock_transactions row.
     */
    protected function receiveOutput(Production $production, string $productId, string $productVariationId, float $quantity, float $totalCost): ProductVariationStockTransaction
    {
        $stock = ProductVariationStock::where('business_id', $production->business_id)
            ->where('warehouse_id', $production->warehouse_id)
            ->where('product_id', $productId)
            ->where('product_variation_id', $productVariationId)
            ->lockForUpdate()
            ->first();

        $existingQty = (float) ($stock->quantity ?? 0);
        $existingAvg = (float) ($stock->avg_price ?? 0);
        $newQty = $existingQty + $quantity;
        $newAvg = $newQty > 0 ? ((($existingQty * $existingAvg) + $totalCost) / $newQty) : 0;

        if ($stock) {
            $stock->update(['quantity' => $newQty, 'avg_price' => $newAvg]);
        } else {
            $stock = ProductVariationStock::create([
                'product_variation_stock_id' => generateUuid(),
                'business_id' => $production->business_id,
                'warehouse_id' => $production->warehouse_id,
                'product_id' => $productId,
                'product_variation_id' => $productVariationId,
                'quantity' => $newQty,
                'avg_price' => $newAvg,
                'status' => 'active',
                'createdby_id' => Auth::id(),
                'date_created' => now(),
            ]);
        }

        $variation = ProductVariation::find($productVariationId);
        // product_variation_batches.expiry_date is NOT NULL (has a DB
        // default of now(), but Eloquent inserts an explicit NULL rather
        // than omitting the column) - fall back the same way the column's
        // own default would when this production has no expiry set.
        $batchId = app(ProductVariationStockService::class)->upsertReceiptBatch(
            $production->business_id,
            $production->warehouse_id,
            $productId,
            $productVariationId,
            $production->batch_no,
            $production->manufacturing_date ?: now(),
            $production->expiry_date ?: now(),
            $quantity,
            $totalCost
        );

        return ProductVariationStockTransaction::create([
            'product_variation_stock_transaction_id' => generateUuid(),
            'transaction_date' => now(),
            'transaction_type' => TransactionType::PRODUCTION_IN,
            'business_id' => $production->business_id,
            'product_id' => $productId,
            'product_variation_id' => $productVariationId,
            'warehouse_id' => $production->warehouse_id,
            'unit_id' => $variation->base_unit_id ?? null,
            'quantity' => $quantity,
            'base_quantity' => $quantity,
            'unit_price' => $quantity > 0 ? round($totalCost / $quantity, 4) : 0,
            'total_price' => $totalCost,
            'quantity_after' => $newQty,
            'avg_price_after' => $newAvg,
            'reference_id' => $production->production_id,
            'reference_type' => ReferenceType::PRODUCTION,
            'remarks' => 'Finished goods from Production ' . ($production->production_no ?? $production->production_id),
            'product_variation_batch_id' => $batchId,
            'createdby_id' => Auth::id(),
            'date_created' => now(),
        ]);
    }

    /**
     * Draft: nothing to reverse (no stock ever moved). Completed: reverse
     * every stock transaction this production wrote via the same shared
     * helper GRN/Order reversal uses, then re-reserve the raw materials it
     * had consumed against the parent plan.
     */
    public function cancel($production_id, $reason = null)
    {
        DB::beginTransaction();
        try {
            $production = Production::where('production_id', $production_id)->where('is_deleted', 0)->lockForUpdate()->first();
            if (!$production) {
                throw new Exception('Production not found.');
            }
            if ($production->status === ProductionStatus::CANCELLED) {
                throw new Exception('This production is already cancelled.');
            }

            if ($production->status === ProductionStatus::COMPLETED) {
                $transactions = ProductVariationStockTransaction::where('reference_id', $production->production_id)
                    ->whereIn('reference_type', [ReferenceType::CONSUMPTION, ReferenceType::PRODUCTION])
                    ->where('is_deleted', 0)
                    ->get();

                app(ProductVariationStockService::class)->reverseStockTransactions($transactions);

                foreach ($production->consumptions as $consumption) {
                    $planMaterial = ManufacturingPlanMaterial::where('manufacturing_plan_id', $production->manufacturing_plan_id)
                        ->where('product_variation_id', $consumption->product_variation_id)
                        ->lockForUpdate()
                        ->first();
                    if (!$planMaterial) {
                        continue;
                    }

                    $planMaterial->update(['consumed_quantity' => max(0, (float) $planMaterial->consumed_quantity - (float) $consumption->base_quantity)]);

                    $stock = ProductVariationStock::where('business_id', $production->business_id)
                        ->where('warehouse_id', $consumption->warehouse_id)
                        ->where('product_id', $consumption->product_id)
                        ->where('product_variation_id', $consumption->product_variation_id)
                        ->lockForUpdate()
                        ->first();
                    if ($stock) {
                        $stock->update(['reserved_quantity' => (float) $stock->reserved_quantity + (float) $consumption->base_quantity]);
                    }
                }
            }

            $production->update([
                'status' => ProductionStatus::CANCELLED,
                'cancelled_at' => now(),
                'cancel_reason' => $reason,
                'updatedby_id' => Auth::id(),
                'date_updated' => now(),
            ]);

            app(ManufacturingPlanService::class)->recomputeProgress($production->manufacturing_plan_id);

            $this->logActivity('production', $production_id, 'cancel', null, $production->fresh()->toArray());

            DB::commit();
            return $this->getById($production_id);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
