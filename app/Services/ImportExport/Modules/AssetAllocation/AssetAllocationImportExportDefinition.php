<?php

namespace App\Services\ImportExport\Modules\AssetAllocation;

use App\Models\Asset;
use App\Models\AssetAllocation;
use App\Models\Employee;
use App\Services\Concrete\Admin\Hrm\AssetService;
use App\Services\ImportExport\Support\AbstractImportExportDefinition;
use App\Services\ImportExport\Support\ColumnDefinition;
use App\Services\ImportExport\Support\ImportContext;
use App\Services\ImportExport\Support\RelationSpec;
use App\Services\ImportExport\Support\ResolvedRow;
use Illuminate\Database\Eloquent\Builder;

/**
 * Modeled as a simple standalone module even though Asset Allocation is
 * conceptually a child of Asset - it has its own CRUD/listing via
 * AssetAllocationController, so it gets its own Definition rather than a
 * ChildTableDefinition under Asset.
 *
 * The asset_allocations table has no is_deleted and no branch_id column
 * (see the create_assets_table migration) - isBranchScoped() therefore
 * stays false (the default) and exportQuery()/DuplicateDetectorService
 * never touch a branch_id column that doesn't exist. The Asset model
 * (asset_id's related table) has business_id/branch_id columns but no
 * business()/branch() Eloquent relation methods, so exportEagerLoads()
 * below only ever eager-loads relations that actually exist
 * (asset, employee.user) - eager-loading 'asset.business' or
 * 'asset.branch' would throw an undefined-relationship error.
 *
 * AssetAllocationService::issue()/returnAsset() additionally keep the
 * parent Asset's own `status` column in sync as a side effect (issued ->
 * allocated, returned/damaged -> available, lost -> retired), via
 * AssetService::setStatus(). afterSave() below replicates that one side
 * effect for imported rows so an Asset's status doesn't go stale after a
 * bulk import. The Service's other guardrails (e.g. "asset not currently
 * available", "allocation already closed") are sequential-workflow checks
 * that don't apply to a bulk import/correction tool, so they are
 * deliberately not replicated here - a bulk import can set a row directly
 * to any status.
 */
class AssetAllocationImportExportDefinition extends AbstractImportExportDefinition
{
    public function moduleKey(): string
    {
        return 'asset-allocation';
    }

    public function label(): string
    {
        return 'Asset Allocation';
    }

    public function modelClass(): string
    {
        return AssetAllocation::class;
    }

    public function primaryKey(): string
    {
        return 'asset_allocation_id';
    }

    public function columns(): array
    {
        return [
            new ColumnDefinition(
                key: 'Asset Tag',
                attribute: 'asset_id',
                type: 'relation',
                required: true,
                relation: new RelationSpec(Asset::class, 'asset', 'asset_tag', scopeToBusiness: true),
                sampleValues: ['AST-0001', 'AST-0002'],
                exportAccessor: fn ($m) => $m->asset->asset_tag ?? '',
                notes: 'Copy the exact Asset Tag from the Assets list.',
            ),
            new ColumnDefinition(
                key: 'Employee Code',
                attribute: 'employee_id',
                type: 'relation',
                required: true,
                relation: new RelationSpec(Employee::class, 'employee', 'employee_code', scopeToBusiness: true),
                sampleValues: ['EMP-0001', 'EMP-0002'],
                exportAccessor: fn ($m) => $m->employee->employee_code ?? '',
                notes: 'Copy the exact Employee Code from the Employees list.',
            ),
            new ColumnDefinition(
                key: 'Issue Date',
                attribute: 'issue_date',
                type: 'date',
                required: true,
                sampleValues: ['2026-08-01', '2026-08-05'],
                exportAccessor: 'issue_date',
            ),
            new ColumnDefinition(
                key: 'Status',
                attribute: 'status',
                type: 'enum',
                required: false,
                enumValues: ['issued', 'returned', 'lost', 'damaged'],
                sampleValues: ['issued', ''],
                exportAccessor: 'status',
                notes: 'Defaults to "issued" if left blank.',
            ),
        ];
    }

    public function uniqueKeyColumns(): array
    {
        return ['asset_id', 'issue_date'];
    }

    protected function additionalCreateAttributes(ImportContext $ctx): array
    {
        return ['status' => 'issued'];
    }

    /**
     * Keep the parent Asset's status column consistent with the allocation
     * status just written, mirroring the side effect of
     * AssetAllocationService::issue()/returnAsset() (see class docblock).
     */
    protected function afterSave($model, ResolvedRow $row, bool $created, ImportContext $ctx): void
    {
        $map = [
            'issued' => 'allocated',
            'returned' => 'available',
            'damaged' => 'available',
            'lost' => 'retired',
        ];

        $assetStatus = $map[$model->status] ?? null;

        if ($assetStatus) {
            app(AssetService::class)->setStatus($model->asset_id, $assetStatus);
        }
    }

    public function exportQuery(array $filters, ImportContext $ctx): Builder
    {
        $query = AssetAllocation::query();

        if (!empty($filters['business_id'])) {
            $query->where('business_id', $filters['business_id']);
        }

        return $query->orderBy('issue_date', 'desc');
    }

    public function exportEagerLoads(): array
    {
        return ['asset', 'employee.user'];
    }
}
