<?php

namespace App\Services\Concrete\Admin;

use App\Enums\DepreciationAdjustmentModes;
use App\Enums\DepreciationFrequencies;
use App\Enums\Filter;
use App\Enums\FixedAssetDisposalTypes;
use App\Enums\FixedAssetStatuses;
use App\Enums\FixedAssetTransactionTypes;
use App\Enums\RoleNames;
use App\Models\FixedAsset;
use App\Models\FixedAssetDepreciation;
use App\Models\FixedAssetTransaction;
use App\Repository\Repository;
use App\Services\Concrete\Admin\FixedAsset\FixedAssetAccountingService;
use App\Services\Concrete\Admin\FixedAsset\FixedAssetCalculator;
use App\Traits\Auditable;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

class FixedAssetService
{
    use Auditable;

    protected $model;
    protected $calculator;
    protected $accounting;
    protected $with = [
        'business',
        'branch',
        'category',
        'supplier',
        'purchase',
        'acquisitionJournalEntry',
        'disposalJournalEntry',
        'createdby',
    ];

    public function __construct(FixedAssetCalculator $calculator, FixedAssetAccountingService $accounting)
    {
        $this->model = new Repository(new FixedAsset());
        $this->calculator = $calculator;
        $this->accounting = $accounting;
    }

    public function getData($obj)
    {
        $wh = [];
        $orderBy = Filter::ORDERBY;

        if (!empty($obj['business_id'])) {
            $wh[] = ['business_id', $obj['business_id']];
        }
        if (!empty($obj['branch_id'])) {
            $wh[] = ['branch_id', $obj['branch_id']];
        }
        if (!empty($obj['fixed_asset_category_id'])) {
            $wh[] = ['fixed_asset_category_id', $obj['fixed_asset_category_id']];
        }
        if (!empty($obj['depreciation_status'])) {
            $wh[] = ['depreciation_status', $obj['depreciation_status']];
        }
        if (!empty($obj['start_date'])) {
            $wh[] = ['purchase_date', '>=', Carbon::parse($obj['start_date'])->startOfDay()];
        }
        if (!empty($obj['end_date'])) {
            $wh[] = ['purchase_date', '<=', Carbon::parse($obj['end_date'])->endOfDay()];
        }

        $allow_roles = [
            RoleNames::SUPERADMIN,
            RoleNames::BUSINESSADMIN,
            RoleNames::FINANCEMANAGER,
            RoleNames::ACCOUNTANT,
            RoleNames::BRANCHADMIN,
        ];

        $datatable = $this->model->getModel()::with($this->with)
            ->where($wh)
            ->where('is_deleted', 0)
            ->orderBy('purchase_date', $orderBy);
        $datatable = applyRoleScope($datatable, $allow_roles);

        $labels = FixedAssetStatuses::labels();

        return DataTables::of($datatable)
            ->addColumn('category', fn ($item) => $item->category->name ?? '')
            ->addColumn('branch', fn ($item) => $item->branch->name ?? '')
            ->addColumn('business', fn ($item) => $item->business->name ?? '')
            ->addColumn('purchase_date', fn ($item) => $item->purchase_date ? localDate($item->purchase_date) : '')
            ->addColumn('purchase_cost', fn ($item) => currency($item->purchase_cost))
            ->addColumn('current_book_value', fn ($item) => currency($item->current_book_value))
            ->addColumn('previous_book_value', fn ($item) => currency($item->previous_book_value))
            ->addColumn('last_depreciation_amount', fn ($item) => currency($item->last_depreciation_amount))
            ->addColumn('accumulated_depreciation', fn ($item) => currency($item->accumulated_depreciation))
            ->addColumn('residual_value', fn ($item) => currency($item->residual_value))
            ->addColumn('depreciation_frequency', fn ($item) => DepreciationFrequencies::labels()[$item->depreciation_frequency] ?? $item->depreciation_frequency)
            ->addColumn('next_depreciation_date', fn ($item) => $item->next_depreciation_date ? localDate($item->next_depreciation_date) : '')
            ->addColumn('depreciation_status', function ($item) use ($labels) {
                $label = $labels[$item->depreciation_status] ?? $item->depreciation_status;
                $class = match ($item->depreciation_status) {
                    FixedAssetStatuses::ACTIVE => 'success',
                    FixedAssetStatuses::PAUSED => 'warning',
                    FixedAssetStatuses::FULLY_DEPRECIATED => 'info',
                    FixedAssetStatuses::SOLD, FixedAssetStatuses::DISPOSED => 'secondary',
                    default => 'danger',
                };
                return "<span class='badge bg-{$class}'>{$label}</span>";
            })
            ->addColumn('action', function ($item) {
                $id = $item->fixed_asset_id;
                $btns = '';
                if (auth()->user()->can('fixed-asset.view')) {
                    $btns .= "<a href='" . url('admin/fixed-asset/show/' . $id) . "' class='btn btn-sm btn-icon btn-info' title='View'><i class='bx bx-show'></i></a>";
                }
                if (auth()->user()->can('fixed-asset.edit') && !in_array($item->depreciation_status, FixedAssetStatuses::terminal())) {
                    $btns .= "<a href='" . url('admin/fixed-asset/edit/' . $id) . "' class='btn btn-sm btn-icon btn-primary' title='Edit'><i class='bx bx-edit'></i></a>";
                }
                if (auth()->user()->can('fixed-asset.delete') && $item->accumulated_depreciation <= 0 && !in_array($item->depreciation_status, FixedAssetStatuses::terminal())) {
                    $btns .= "<button type='button' class='btn btn-sm btn-icon btn-danger delete' data-id='{$id}' title='Delete'><i class='bx bx-trash'></i></button>";
                }
                return "<div class='d-flex gap-1 flex-wrap'>{$btns}</div>";
            })
            ->rawColumns(['depreciation_status', 'action'])
            ->make(true);
    }

    public function getById($id)
    {
        return $this->model->getModel()::with(array_merge($this->with, [
            'depreciations.journalEntry',
            'transactions.journalEntry',
            'transactions.fromBranch',
            'transactions.toBranch',
        ]))
            ->where('fixed_asset_id', $id)
            ->where('is_deleted', 0)
            ->first();
    }

    public function save(array $obj)
    {
        DB::beginTransaction();
        try {
            $isUpdate = !empty($obj['fixed_asset_id']);
            $purchaseCost = (float) ($obj['purchase_cost'] ?? 0);
            if ($purchaseCost < 0) {
                throw new Exception('Purchase cost cannot be negative.');
            }

            $residualPercent = (float) ($obj['residual_percent'] ?? 0);
            $residualValue = $this->calculator->resolveResidualValue(
                $purchaseCost,
                isset($obj['residual_value']) ? (float) $obj['residual_value'] : null,
                $residualPercent
            );

            if ($residualValue > $purchaseCost) {
                throw new Exception('Residual value cannot exceed purchase cost.');
            }

            $obj['residual_value'] = $residualValue;
            $obj['residual_percent'] = $residualPercent;
            $obj['min_book_value_percent'] = (float) ($obj['min_book_value_percent'] ?? 0);
            $obj['useful_life_years'] = max((int) ($obj['useful_life_years'] ?? 1), 1);
            $obj['depreciation_method'] = $obj['depreciation_method'] ?? 'straight_line';
            $obj['depreciation_frequency'] = $obj['depreciation_frequency'] ?? DepreciationFrequencies::MONTHLY;
            $obj['depreciation_adjustment_mode'] = $obj['depreciation_adjustment_mode'] ?? DepreciationAdjustmentModes::NONE;
            $obj['depreciation_adjustment_rate'] = (float) ($obj['depreciation_adjustment_rate'] ?? 0);
            // Skip acquisition JV only when explicitly flagged or purchase already posted a JV.
            $skipAcquisitionJv = !empty($obj['accounting_from_purchase'])
                || $this->accounting->purchaseAlreadyPosted($obj['purchase_id'] ?? null);
            $obj['accounting_from_purchase'] = $skipAcquisitionJv;

            if ($isUpdate) {
                $asset = $this->getById($obj['fixed_asset_id']);
                if (!$asset) {
                    throw new Exception('Fixed asset not found.');
                }
                if (in_array($asset->depreciation_status, FixedAssetStatuses::terminal())) {
                    throw new Exception('Cannot edit a sold/disposed/written-off asset.');
                }
                if ((float) $asset->accumulated_depreciation > 0) {
                    // Preserve historical purchase cost once depreciation has started
                    unset($obj['purchase_cost'], $obj['purchase_date']);
                }

                $old = $asset->toArray();
                $obj['updatedby_id'] = Auth::id();
                $obj['date_updated'] = now();
                unset($obj['current_book_value'], $obj['accumulated_depreciation'], $obj['previous_book_value']);

                $asset->update($obj);
                $asset = $asset->fresh($this->with);

                if (!empty($obj['location']) && ($old['location'] ?? null) !== $obj['location']) {
                    $this->recordTransaction($asset, FixedAssetTransactionTypes::ALLOCATION, [
                        'description' => 'Location updated',
                        'from_location' => $old['location'] ?? null,
                        'to_location' => $obj['location'],
                    ]);
                }

                $this->logActivity('fixed-asset', $asset->fixed_asset_id, 'update', $old, $asset->toArray());
            } else {
                $obj['fixed_asset_id'] = generateUuid();
                $obj['current_book_value'] = $purchaseCost;
                $obj['previous_book_value'] = $purchaseCost;
                $obj['accumulated_depreciation'] = 0;
                $obj['last_depreciation_amount'] = 0;
                $obj['depreciation_status'] = FixedAssetStatuses::ACTIVE;
                $obj['createdby_id'] = Auth::id();
                $obj['date_created'] = now();

                $asset = $this->model->getModel()::create($obj);
                $asset->next_depreciation_date = $this->calculator->firstDepreciationDate($asset)->toDateString();
                $asset->save();

                if (!empty($obj['purchase_id']) || $this->accounting->purchaseAlreadyPosted($obj['purchase_id'] ?? null)) {
                    $this->accounting->linkPurchaseReference($asset->fresh());
                    $asset = $asset->fresh();
                }

                $je = null;
                if (!$asset->accounting_from_purchase) {
                    $je = $this->accounting->postAcquisition($asset);
                    if ($je) {
                        $asset->acquisition_journal_entry_id = $je->journal_entry_id;
                        $asset->save();
                    }
                }

                $this->recordTransaction($asset, FixedAssetTransactionTypes::PURCHASE, [
                    'description' => 'Asset acquired' . ($asset->accounting_from_purchase ? ' (linked to purchase — no duplicate JV)' : ''),
                    'amount' => $purchaseCost,
                    'journal_entry_id' => $asset->acquisition_journal_entry_id,
                    'reference_type' => $asset->purchase_id ? 'purchase' : null,
                    'reference_id' => $asset->purchase_id,
                    'to_location' => $asset->location,
                    'to_branch_id' => $asset->branch_id,
                ]);

                if ($asset->location) {
                    $this->recordTransaction($asset, FixedAssetTransactionTypes::ALLOCATION, [
                        'description' => 'Initial location assignment',
                        'to_location' => $asset->location,
                        'to_branch_id' => $asset->branch_id,
                    ]);
                }

                $this->logActivity('fixed-asset', $asset->fixed_asset_id, 'create', null, $asset->fresh()->toArray());
            }

            DB::commit();
            return $asset->fresh($this->with);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function delete($id)
    {
        DB::beginTransaction();
        try {
            $asset = $this->getById($id);
            if (!$asset) {
                throw new Exception('Fixed asset not found.');
            }
            if ((float) $asset->accumulated_depreciation > 0) {
                throw new Exception('Cannot delete an asset that has depreciation history. Dispose it instead.');
            }
            if (in_array($asset->depreciation_status, FixedAssetStatuses::terminal())) {
                throw new Exception('Cannot delete a disposed/sold asset.');
            }

            $asset->update([
                'is_deleted' => 1,
                'deletedby_id' => Auth::id(),
                'date_deleted' => now(),
            ]);
            $this->logActivity('fixed-asset', $id, 'delete', $asset->toArray(), null);
            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function pause($id, ?string $reason = null)
    {
        return $this->toggleDepreciation($id, FixedAssetStatuses::PAUSED, FixedAssetTransactionTypes::PAUSE, $reason ?: 'Depreciation paused');
    }

    public function resume($id, ?string $reason = null)
    {
        DB::beginTransaction();
        try {
            $asset = $this->getById($id);
            if (!$asset) {
                throw new Exception('Fixed asset not found.');
            }
            if ($asset->depreciation_status !== FixedAssetStatuses::PAUSED) {
                throw new Exception('Only paused assets can be resumed.');
            }
            if ($this->calculator->remainingDepreciable($asset) <= 0) {
                throw new Exception('Asset has no remaining depreciable value.');
            }

            $old = $asset->toArray();
            $from = Carbon::today();
            if ($asset->next_depreciation_date && Carbon::parse($asset->next_depreciation_date)->gt($from)) {
                // keep future next date
            } else {
                $asset->next_depreciation_date = $this->calculator->nextDepreciationDate($asset, $from->copy()->subDay())->toDateString();
                if (Carbon::parse($asset->next_depreciation_date)->lt($from)) {
                    $asset->next_depreciation_date = $from->toDateString();
                }
            }
            $asset->depreciation_status = FixedAssetStatuses::ACTIVE;
            $asset->updatedby_id = Auth::id();
            $asset->date_updated = now();
            $asset->save();

            $this->recordTransaction($asset, FixedAssetTransactionTypes::RESUME, [
                'description' => $reason ?: 'Depreciation resumed',
            ]);
            $this->logActivity('fixed-asset', $id, 'resume', $old, $asset->fresh()->toArray());
            DB::commit();
            return $asset->fresh($this->with);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    protected function toggleDepreciation($id, string $status, string $txType, string $description)
    {
        DB::beginTransaction();
        try {
            $asset = $this->getById($id);
            if (!$asset) {
                throw new Exception('Fixed asset not found.');
            }
            if ($asset->depreciation_status !== FixedAssetStatuses::ACTIVE) {
                throw new Exception('Only active assets can be paused.');
            }
            $old = $asset->toArray();
            $asset->depreciation_status = $status;
            $asset->updatedby_id = Auth::id();
            $asset->date_updated = now();
            $asset->save();
            $this->recordTransaction($asset, $txType, ['description' => $description]);
            $this->logActivity('fixed-asset', $id, 'pause', $old, $asset->fresh()->toArray());
            DB::commit();
            return $asset->fresh($this->with);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function transfer($id, array $obj)
    {
        DB::beginTransaction();
        try {
            $asset = $this->getById($id);
            if (!$asset) {
                throw new Exception('Fixed asset not found.');
            }
            if (in_array($asset->depreciation_status, [FixedAssetStatuses::SOLD, FixedAssetStatuses::DISPOSED, FixedAssetStatuses::WRITTEN_OFF, FixedAssetStatuses::DAMAGED])) {
                throw new Exception('Cannot transfer a disposed asset.');
            }

            $oldBranch = $asset->branch_id;
            $oldLocation = $asset->location;
            $newBranch = $obj['branch_id'] ?? $asset->branch_id;
            $newLocation = $obj['location'] ?? $asset->location;

            $asset->branch_id = $newBranch;
            $asset->location = $newLocation;
            $asset->updatedby_id = Auth::id();
            $asset->date_updated = now();
            $asset->save();

            $this->recordTransaction($asset, FixedAssetTransactionTypes::TRANSFER, [
                'description' => $obj['remarks'] ?? 'Branch/location transfer',
                'from_branch_id' => $oldBranch,
                'to_branch_id' => $newBranch,
                'from_location' => $oldLocation,
                'to_location' => $newLocation,
            ]);

            $this->logActivity('fixed-asset', $id, 'transfer', [
                'branch_id' => $oldBranch,
                'location' => $oldLocation,
            ], [
                'branch_id' => $newBranch,
                'location' => $newLocation,
            ]);

            DB::commit();
            return $asset->fresh($this->with);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Post one depreciation period for an asset. Idempotent on period_key.
     */
    public function postDepreciationForAsset(FixedAsset $asset, ?Carbon $asOf = null, string $source = 'manual'): ?FixedAssetDepreciation
    {
        $asOf = ($asOf ?: Carbon::today())->copy()->startOfDay();

        if ($asset->depreciation_status !== FixedAssetStatuses::ACTIVE) {
            return null;
        }
        if ($asset->next_depreciation_date && Carbon::parse($asset->next_depreciation_date)->gt($asOf)) {
            return null;
        }

        $periodKey = $this->calculator->periodKey($asset, $asOf);

        $existing = FixedAssetDepreciation::where('fixed_asset_id', $asset->fixed_asset_id)
            ->where('period_key', $periodKey)
            ->where('is_deleted', 0)
            ->first();
        if ($existing) {
            return $existing;
        }

        $postedCount = FixedAssetDepreciation::where('fixed_asset_id', $asset->fixed_asset_id)
            ->where('is_deleted', 0)
            ->where('status', 'posted')
            ->count();

        $calc = $this->calculator->computeDepreciation($asset, $postedCount);
        if (!$calc) {
            $asset->depreciation_status = FixedAssetStatuses::FULLY_DEPRECIATED;
            $asset->next_depreciation_date = null;
            $asset->save();
            return null;
        }

        DB::beginTransaction();
        try {
            // Re-check inside transaction
            $existing = FixedAssetDepreciation::where('fixed_asset_id', $asset->fixed_asset_id)
                ->where('period_key', $periodKey)
                ->where('is_deleted', 0)
                ->lockForUpdate()
                ->first();
            if ($existing) {
                DB::commit();
                return $existing;
            }

            $dep = FixedAssetDepreciation::create([
                'fixed_asset_depreciation_id' => generateUuid(),
                'fixed_asset_id' => $asset->fixed_asset_id,
                'business_id' => $asset->business_id,
                'branch_id' => $asset->branch_id,
                'period_key' => $periodKey,
                'depreciation_date' => $asOf->toDateString(),
                'previous_value' => $calc['previous'],
                'depreciation_amount' => $calc['amount'],
                'new_value' => $calc['new'],
                'accumulated_depreciation' => $calc['accumulated'],
                'status' => 'posted',
                'source' => $source,
                'createdby_id' => Auth::id(),
                'date_created' => now(),
            ]);

            $je = $this->accounting->postDepreciation($asset, $dep);
            $dep->journal_entry_id = $je->journal_entry_id;
            $dep->save();

            $asset->previous_book_value = $calc['previous'];
            $asset->current_book_value = $calc['new'];
            $asset->accumulated_depreciation = $calc['accumulated'];
            $asset->last_depreciation_amount = $calc['amount'];
            $asset->last_depreciation_date = $asOf->toDateString();
            $asset->next_depreciation_date = $this->calculator->nextDepreciationDate($asset, $asOf)->toDateString();
            $asset->updatedby_id = Auth::id();
            $asset->date_updated = now();

            if ($calc['fully_depreciated']) {
                $asset->depreciation_status = FixedAssetStatuses::FULLY_DEPRECIATED;
                $asset->next_depreciation_date = null;
            }
            $asset->save();

            $this->recordTransaction($asset, FixedAssetTransactionTypes::DEPRECIATION, [
                'description' => 'Depreciation posted for period ' . $periodKey,
                'amount' => $calc['amount'],
                'journal_entry_id' => $je->journal_entry_id,
                'reference_type' => 'fixed_asset_depreciation',
                'reference_id' => $dep->fixed_asset_depreciation_id,
                'meta' => [
                    'previous_value' => $calc['previous'],
                    'new_value' => $calc['new'],
                    'period_key' => $periodKey,
                ],
            ]);

            $this->logActivity('fixed-asset', $asset->fixed_asset_id, 'depreciate', null, [
                'period_key' => $periodKey,
                'amount' => $calc['amount'],
                'journal_entry_id' => $je->journal_entry_id,
            ]);

            DB::commit();
            return $dep->fresh(['journalEntry']);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function depreciateNow($id)
    {
        $asset = $this->getById($id);
        if (!$asset) {
            throw new Exception('Fixed asset not found.');
        }
        if ($asset->depreciation_status !== FixedAssetStatuses::ACTIVE) {
            throw new Exception('Asset is not active for depreciation.');
        }

        // Allow manual run even if next date is in the future by using next_depreciation_date
        $asOf = $asset->next_depreciation_date
            ? Carbon::parse($asset->next_depreciation_date)
            : Carbon::today();

        $dep = $this->postDepreciationForAsset($asset->fresh(), $asOf, 'manual');
        if (!$dep) {
            throw new Exception('No depreciation due (already fully depreciated or already posted for this period).');
        }
        return $dep;
    }

    public function dispose($id, array $obj)
    {
        DB::beginTransaction();
        try {
            $asset = $this->getById($id);
            if (!$asset) {
                throw new Exception('Fixed asset not found.');
            }
            if (in_array($asset->depreciation_status, [FixedAssetStatuses::SOLD, FixedAssetStatuses::DISPOSED, FixedAssetStatuses::WRITTEN_OFF, FixedAssetStatuses::DAMAGED])) {
                throw new Exception('Asset is already disposed/sold.');
            }

            $type = $obj['disposal_type'] ?? FixedAssetDisposalTypes::WRITE_OFF;
            if (!in_array($type, FixedAssetDisposalTypes::all())) {
                throw new Exception('Invalid disposal type.');
            }

            $salePrice = FixedAssetDisposalTypes::requiresSalePrice($type)
                ? (float) ($obj['sale_price'] ?? 0)
                : (float) ($obj['sale_price'] ?? 0);

            if (FixedAssetDisposalTypes::requiresSalePrice($type) && $salePrice < 0) {
                throw new Exception('Sale price cannot be negative.');
            }
            if (!FixedAssetDisposalTypes::requiresSalePrice($type)) {
                $salePrice = 0;
            }

            $old = $asset->toArray();
            $asset->disposal_date = $obj['disposal_date'] ?? Carbon::today()->toDateString();
            $asset->disposal_type = $type;
            $asset->disposal_reason = $obj['disposal_reason'] ?? null;
            $asset->sale_price = $salePrice;
            $asset->disposal_proceeds_account_id = $obj['disposal_proceeds_account_id'] ?? null;
            $asset->next_depreciation_date = null;

            $statusMap = [
                FixedAssetDisposalTypes::SALE => FixedAssetStatuses::SOLD,
                FixedAssetDisposalTypes::DAMAGE => FixedAssetStatuses::DAMAGED,
                FixedAssetDisposalTypes::WRITE_OFF => FixedAssetStatuses::WRITTEN_OFF,
                FixedAssetDisposalTypes::THEFT => FixedAssetStatuses::WRITTEN_OFF,
                FixedAssetDisposalTypes::WASTE => FixedAssetStatuses::DISPOSED,
                FixedAssetDisposalTypes::OTHER => FixedAssetStatuses::DISPOSED,
            ];
            $asset->depreciation_status = $statusMap[$type] ?? FixedAssetStatuses::DISPOSED;
            $asset->updatedby_id = Auth::id();
            $asset->date_updated = now();
            $asset->save();

            $je = $this->accounting->postDisposal(
                $asset,
                $salePrice,
                $type,
                $asset->disposal_proceeds_account_id
            );
            $asset->disposal_journal_entry_id = $je->journal_entry_id;
            // After disposal, book value is cleared in accounting; reflect 0 carrying value
            $asset->previous_book_value = $asset->current_book_value;
            $asset->current_book_value = 0;
            $asset->save();

            $txType = match ($type) {
                FixedAssetDisposalTypes::SALE => FixedAssetTransactionTypes::SALE,
                FixedAssetDisposalTypes::WASTE => FixedAssetTransactionTypes::WASTE,
                FixedAssetDisposalTypes::DAMAGE => FixedAssetTransactionTypes::DAMAGE,
                FixedAssetDisposalTypes::WRITE_OFF, FixedAssetDisposalTypes::THEFT => FixedAssetTransactionTypes::WRITE_OFF,
                default => FixedAssetTransactionTypes::DISPOSAL,
            };

            $this->recordTransaction($asset, $txType, [
                'description' => ($obj['disposal_reason'] ?? ('Asset ' . $type)) . ' @ ' . $salePrice,
                'amount' => $salePrice,
                'journal_entry_id' => $je->journal_entry_id,
                'meta' => [
                    'disposal_type' => $type,
                    'book_value_at_disposal' => $old['current_book_value'],
                    'gain_loss' => round($salePrice - (float) $old['current_book_value'], 2),
                ],
            ]);

            $this->logActivity('fixed-asset', $id, 'dispose', $old, $asset->fresh()->toArray());
            DB::commit();
            return $asset->fresh($this->with);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function adjust($id, array $obj)
    {
        DB::beginTransaction();
        try {
            $asset = $this->getById($id);
            if (!$asset) {
                throw new Exception('Fixed asset not found.');
            }
            if (in_array($asset->depreciation_status, [FixedAssetStatuses::SOLD, FixedAssetStatuses::DISPOSED, FixedAssetStatuses::WRITTEN_OFF, FixedAssetStatuses::DAMAGED])) {
                throw new Exception('Cannot adjust a disposed asset.');
            }

            $old = $asset->toArray();
            if (isset($obj['depreciation_adjustment_mode'])) {
                $asset->depreciation_adjustment_mode = $obj['depreciation_adjustment_mode'];
            }
            if (isset($obj['depreciation_adjustment_rate'])) {
                $asset->depreciation_adjustment_rate = (float) $obj['depreciation_adjustment_rate'];
            }
            if (isset($obj['min_book_value_percent'])) {
                $asset->min_book_value_percent = (float) $obj['min_book_value_percent'];
            }
            if (isset($obj['residual_value'])) {
                $rv = (float) $obj['residual_value'];
                if ($rv < 0 || $rv > (float) $asset->purchase_cost) {
                    throw new Exception('Invalid residual value.');
                }
                if ($rv > (float) $asset->current_book_value) {
                    throw new Exception('Residual value cannot exceed current book value.');
                }
                $asset->residual_value = $rv;
            }
            if (isset($obj['depreciation_frequency']) && in_array($obj['depreciation_frequency'], DepreciationFrequencies::all())) {
                $asset->depreciation_frequency = $obj['depreciation_frequency'];
            }
            if (!empty($obj['useful_life_years'])) {
                $asset->useful_life_years = max((int) $obj['useful_life_years'], 1);
            }

            $asset->updatedby_id = Auth::id();
            $asset->date_updated = now();
            $asset->save();

            $this->recordTransaction($asset, FixedAssetTransactionTypes::ADJUSTMENT, [
                'description' => $obj['remarks'] ?? 'Depreciation configuration adjusted',
                'meta' => [
                    'before' => [
                        'depreciation_adjustment_mode' => $old['depreciation_adjustment_mode'],
                        'depreciation_adjustment_rate' => $old['depreciation_adjustment_rate'],
                        'min_book_value_percent' => $old['min_book_value_percent'],
                        'residual_value' => $old['residual_value'],
                    ],
                    'after' => [
                        'depreciation_adjustment_mode' => $asset->depreciation_adjustment_mode,
                        'depreciation_adjustment_rate' => $asset->depreciation_adjustment_rate,
                        'min_book_value_percent' => $asset->min_book_value_percent,
                        'residual_value' => $asset->residual_value,
                    ],
                ],
            ]);

            $this->logActivity('fixed-asset', $id, 'adjust', $old, $asset->fresh()->toArray());
            DB::commit();
            return $asset->fresh($this->with);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Cron entry: due active assets whose next_depreciation_date <= $asOf.
     */
    public function processDueDepreciations(?Carbon $asOf = null, ?string $businessId = null): array
    {
        $asOf = ($asOf ?: Carbon::today())->copy()->startOfDay();
        $stats = ['processed' => 0, 'skipped' => 0, 'errors' => 0];

        $query = FixedAsset::where('is_deleted', 0)
            ->where('depreciation_status', FixedAssetStatuses::ACTIVE)
            ->whereNotNull('next_depreciation_date')
            ->whereDate('next_depreciation_date', '<=', $asOf->toDateString())
            ->when($businessId, fn ($q) => $q->where('business_id', $businessId))
            ->orderBy('next_depreciation_date');

        foreach ($query->cursor() as $asset) {
            try {
                // Catch up periods if multiple due (e.g. cron missed days for daily assets)
                $guard = 0;
                while (
                    $asset->depreciation_status === FixedAssetStatuses::ACTIVE
                    && $asset->next_depreciation_date
                    && Carbon::parse($asset->next_depreciation_date)->lte($asOf)
                    && $guard < 400
                ) {
                    $due = Carbon::parse($asset->next_depreciation_date);
                    $dep = $this->postDepreciationForAsset($asset->fresh(), $due, 'scheduler');
                    $asset = $asset->fresh();
                    if (!$dep) {
                        $stats['skipped']++;
                        break;
                    }
                    $stats['processed']++;
                    $guard++;
                }
            } catch (Exception $e) {
                $stats['errors']++;
                report($e);
            }
        }

        return $stats;
    }

    public function recordTransaction(FixedAsset $asset, string $type, array $data = []): FixedAssetTransaction
    {
        return FixedAssetTransaction::create([
            'fixed_asset_transaction_id' => generateUuid(),
            'fixed_asset_id' => $asset->fixed_asset_id,
            'business_id' => $asset->business_id,
            'branch_id' => $asset->branch_id,
            'transaction_type' => $type,
            'transaction_date' => $data['transaction_date'] ?? Carbon::today()->toDateString(),
            'description' => $data['description'] ?? null,
            'amount' => $data['amount'] ?? null,
            'from_branch_id' => $data['from_branch_id'] ?? null,
            'to_branch_id' => $data['to_branch_id'] ?? null,
            'from_location' => $data['from_location'] ?? null,
            'to_location' => $data['to_location'] ?? null,
            'journal_entry_id' => $data['journal_entry_id'] ?? null,
            'reference_type' => $data['reference_type'] ?? null,
            'reference_id' => $data['reference_id'] ?? null,
            'meta' => $data['meta'] ?? null,
            'createdby_id' => Auth::id(),
            'date_created' => now(),
        ]);
    }
}
