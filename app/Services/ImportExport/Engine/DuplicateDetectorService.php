<?php

namespace App\Services\ImportExport\Engine;

use App\Services\ImportExport\Contracts\ImportExportDefinitionContract;
use App\Services\ImportExport\Support\ImportContext;

/**
 * Decides CREATE vs UPDATE for a resolved row's attributes, replicating the
 * exact business_id + is_deleted=0 scoping every module's
 * Rule::unique(...)->where('business_id', ...)->where('is_deleted', 0)
 * validation already uses.
 */
class DuplicateDetectorService
{
    public function detect(array $attributes, ImportExportDefinitionContract $def, ImportContext $ctx): array
    {
        $keys = $def->uniqueKeyColumns();

        if (empty($keys)) {
            return ['action' => 'create', 'matchedId' => null];
        }

        $modelClass = $def->modelClass();
        $fillable = (new $modelClass())->getFillable();
        $query = $modelClass::query();

        if (in_array('is_deleted', $fillable, true)) {
            $query->where('is_deleted', 0);
        }
        if (!$def->uniqueKeyIsGlobal()) {
            if ($def->isBusinessScoped() && $ctx->businessId && in_array('business_id', $fillable, true)) {
                $query->where('business_id', $ctx->businessId);
            }
            if ($def->isBranchScoped() && $ctx->branchId && in_array('branch_id', $fillable, true)) {
                $query->where('branch_id', $ctx->branchId);
            }
        }

        foreach ($keys as $key) {
            $value = $attributes[$key] ?? null;

            if ($value === null || $value === '') {
                return ['action' => 'create', 'matchedId' => null];
            }

            if (is_string($value)) {
                $query->whereRaw('LOWER(' . $key . ') = ?', [mb_strtolower($value)]);
            } else {
                $query->where($key, $value);
            }
        }

        $match = $query->first();

        return $match
            ? ['action' => 'update', 'matchedId' => $match->getKey()]
            : ['action' => 'create', 'matchedId' => null];
    }
}
