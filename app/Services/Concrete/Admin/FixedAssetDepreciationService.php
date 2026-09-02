<?php

namespace App\Services\Concrete\Admin;

use App\Enums\Filter;
use App\Enums\FixedAssetStatuses;
use App\Enums\FixedAssetTransactionTypes;
use App\Enums\JournalSourceTypes;
use App\Enums\RoleNames;
use App\Models\FixedAsset;
use App\Models\FixedAssetDepreciation;
use App\Models\JournalEntry;
use App\Repository\Repository;
use App\Services\Concrete\Admin\FixedAsset\FixedAssetCalculator;
use App\Traits\Auditable;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

class FixedAssetDepreciationService
{
    use Auditable;

    protected $model;
    protected $fixed_asset_service;
    protected $calculator;
    protected $with = [
        'fixedAsset',
        'business',
        'branch',
        'journalEntry',
    ];

    public function __construct(FixedAssetService $fixed_asset_service, FixedAssetCalculator $calculator)
    {
        $this->model = new Repository(new FixedAssetDepreciation());
        $this->fixed_asset_service = $fixed_asset_service;
        $this->calculator = $calculator;
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
        if (!empty($obj['fixed_asset_id'])) {
            $wh[] = ['fixed_asset_id', $obj['fixed_asset_id']];
        }
        if (!empty($obj['status'])) {
            $wh[] = ['status', $obj['status']];
        }
        if (!empty($obj['start_date'])) {
            $wh[] = ['depreciation_date', '>=', Carbon::parse($obj['start_date'])->startOfDay()];
        }
        if (!empty($obj['end_date'])) {
            $wh[] = ['depreciation_date', '<=', Carbon::parse($obj['end_date'])->endOfDay()];
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
            ->orderBy('depreciation_date', $orderBy);
        $datatable = applyRoleScope($datatable, $allow_roles);

        return DataTables::of($datatable)
            ->addColumn('business', fn ($item) => $item->business->name ?? '')
            ->addColumn('branch', fn ($item) => $item->branch->name ?? '')
            ->addColumn('asset_code', fn ($item) => $item->fixedAsset->asset_code ?? '')
            ->addColumn('asset_name', fn ($item) => $item->fixedAsset->name ?? '')
            ->addColumn('depreciation_date', fn ($item) => $item->depreciation_date ? localDate($item->depreciation_date) : '')
            ->addColumn('previous_value', fn ($item) => currency($item->previous_value))
            ->addColumn('depreciation_amount', fn ($item) => currency($item->depreciation_amount))
            ->addColumn('new_value', fn ($item) => currency($item->new_value))
            ->addColumn('accumulated_depreciation', fn ($item) => currency($item->accumulated_depreciation))
            ->addColumn('journal_entry', fn ($item) => $item->journalEntry->entry_no ?? '')
            ->addColumn('status', function ($item) {
                $class = $item->status === 'posted' ? 'success' : 'secondary';
                return "<span class='badge bg-{$class}'>" . ucfirst($item->status) . '</span>';
            })
            ->addColumn('action', function ($item) {
                $id = $item->fixed_asset_depreciation_id;
                $btns = '';
                if (auth()->user()->can('fixed-asset-depreciation.view')) {
                    $btns .= "<a href='" . url('admin/fixed-asset-depreciation/show/' . $id) . "' class='btn btn-sm btn-icon btn-info' title='View'><i class='bx bx-show'></i></a>";
                }
                if (auth()->user()->can('fixed-asset-depreciation.delete')) {
                    $btns .= "<button type='button' class='btn btn-sm btn-icon btn-danger delete' data-id='{$id}' title='Reverse'><i class='bx bx-trash'></i></button>";
                }
                return "<div class='d-flex gap-1'>{$btns}</div>";
            })
            ->rawColumns(['status', 'action'])
            ->make(true);
    }

    public function getById($id)
    {
        return $this->model->getModel()::with($this->with)
            ->where('fixed_asset_depreciation_id', $id)
            ->where('is_deleted', 0)
            ->first();
    }

    /**
     * Manual post for a selected asset (business-scoped).
     */
    public function create(array $obj)
    {
        $asset = FixedAsset::where('fixed_asset_id', $obj['fixed_asset_id'] ?? null)
            ->where('is_deleted', 0)
            ->first();

        if (!$asset) {
            throw new Exception('Fixed asset not found.');
        }

        $businessId = $obj['business_id'] ?? Auth::user()->business_id;
        if ($businessId && $asset->business_id !== $businessId && getRoleName() !== RoleNames::SUPERADMIN) {
            throw new Exception('Asset does not belong to this business.');
        }

        if ($asset->depreciation_status !== FixedAssetStatuses::ACTIVE) {
            throw new Exception('Only active assets can be depreciated.');
        }

        $asOf = !empty($obj['depreciation_date'])
            ? Carbon::parse($obj['depreciation_date'])->startOfDay()
            : ($asset->next_depreciation_date
                ? Carbon::parse($asset->next_depreciation_date)->startOfDay()
                : Carbon::today());

        $dep = $this->fixed_asset_service->postDepreciationForAsset($asset->fresh(), $asOf, 'manual');
        if (!$dep) {
            throw new Exception('No depreciation due for this asset/period (already posted or fully depreciated).');
        }

        return $dep;
    }

    /**
     * Reverse only the latest posted depreciation for an asset (business-safe).
     */
    public function reverse($id)
    {
        DB::beginTransaction();
        try {
            $dep = $this->getById($id);
            if (!$dep) {
                throw new Exception('Depreciation record not found.');
            }

            $latest = FixedAssetDepreciation::where('fixed_asset_id', $dep->fixed_asset_id)
                ->where('is_deleted', 0)
                ->where('status', 'posted')
                ->orderByDesc('depreciation_date')
                ->orderByDesc('date_created')
                ->first();

            if (!$latest || $latest->fixed_asset_depreciation_id !== $dep->fixed_asset_depreciation_id) {
                throw new Exception('Only the latest depreciation entry for an asset can be reversed.');
            }

            $asset = FixedAsset::where('fixed_asset_id', $dep->fixed_asset_id)
                ->where('is_deleted', 0)
                ->lockForUpdate()
                ->first();

            if (!$asset) {
                throw new Exception('Fixed asset not found.');
            }

            if (in_array($asset->depreciation_status, [
                FixedAssetStatuses::SOLD,
                FixedAssetStatuses::DISPOSED,
                FixedAssetStatuses::WRITTEN_OFF,
                FixedAssetStatuses::DAMAGED,
            ])) {
                throw new Exception('Cannot reverse depreciation on a disposed/sold asset.');
            }

            $old = $dep->toArray();

            if ($dep->journal_entry_id) {
                $je = JournalEntry::where('journal_entry_id', $dep->journal_entry_id)
                    ->where('source_type', JournalSourceTypes::ASSET_DEPRECIATION)
                    ->where('is_deleted', 0)
                    ->first();
                if ($je) {
                    app(AccountingPeriodService::class)->assertPostable($je->business_id, $je->entry_date);
                    $je->update([
                        'is_deleted' => 1,
                        'deletedby_id' => Auth::id(),
                        'date_deleted' => now(),
                    ]);
                }
            }

            $amount = (float) $dep->depreciation_amount;
            $asset->current_book_value = round((float) $asset->current_book_value + $amount, 2);
            $asset->previous_book_value = (float) $dep->previous_value;
            $asset->accumulated_depreciation = max(round((float) $asset->accumulated_depreciation - $amount, 2), 0);
            $asset->last_depreciation_amount = 0;
            $asset->last_depreciation_date = null;

            $prior = FixedAssetDepreciation::where('fixed_asset_id', $asset->fixed_asset_id)
                ->where('fixed_asset_depreciation_id', '!=', $dep->fixed_asset_depreciation_id)
                ->where('is_deleted', 0)
                ->where('status', 'posted')
                ->orderByDesc('depreciation_date')
                ->orderByDesc('date_created')
                ->first();

            if ($prior) {
                $asset->last_depreciation_amount = $prior->depreciation_amount;
                $asset->last_depreciation_date = $prior->depreciation_date;
                $asset->previous_book_value = $prior->previous_value;
            }

            $asset->next_depreciation_date = Carbon::parse($dep->depreciation_date)->toDateString();
            if ($asset->depreciation_status === FixedAssetStatuses::FULLY_DEPRECIATED) {
                $asset->depreciation_status = FixedAssetStatuses::ACTIVE;
            }
            $asset->updatedby_id = Auth::id();
            $asset->date_updated = now();
            $asset->save();

            $dep->update([
                'status' => 'reversed',
                'is_deleted' => 1,
                'deletedby_id' => Auth::id(),
                'date_deleted' => now(),
            ]);

            $this->fixed_asset_service->recordTransaction($asset, FixedAssetTransactionTypes::ADJUSTMENT, [
                'description' => 'Depreciation reversed for period ' . $dep->period_key,
                'amount' => $amount,
                'reference_type' => 'fixed_asset_depreciation',
                'reference_id' => $dep->fixed_asset_depreciation_id,
                'meta' => ['reversed_period_key' => $dep->period_key],
            ]);

            $this->logActivity('fixed-asset-depreciation', $id, 'reverse', $old, null);
            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
