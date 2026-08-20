<?php

namespace App\Services\ImportExport\Engine;

use App\Services\ImportExport\Support\ImportContext;
use App\Services\ImportExport\Support\RelationSpec;
use Illuminate\Support\Str;

/**
 * Resolves a relational Excel cell value (e.g. a Category name) to the
 * related model's ID, scoped to the current business/branch - the single
 * mechanism every relational column across every module uses.
 */
class RelationResolverService
{
    public function resolve(RelationSpec $spec, $value, ImportContext $ctx): array
    {
        if ($value === null || trim((string) $value) === '') {
            return ['id' => null, 'ids' => [], 'error' => null];
        }

        if ($spec->allowMultiple) {
            $names = array_values(array_filter(array_map('trim', explode(',', (string) $value)), fn ($n) => $n !== ''));
            $ids = [];
            $notFound = [];

            foreach ($names as $name) {
                $found = $this->findOne($spec, $name, $ctx);
                if ($found) {
                    $ids[] = $found->getKey();
                } else {
                    $notFound[] = $name;
                }
            }

            if (!empty($notFound)) {
                $label = $spec->label();
                $list = implode('", "', $notFound);

                return ['id' => null, 'ids' => $ids, 'error' => "No {$label} named \"{$list}\" found for this business. Copy the exact {$spec->relatedLabelColumn} value(s) from the {$label} list."];
            }

            return ['id' => null, 'ids' => $ids, 'error' => null];
        }

        $found = $this->findOne($spec, (string) $value, $ctx);

        if (!$found) {
            return ['id' => null, 'ids' => [], 'error' => $spec->notFoundMessage($value)];
        }

        return ['id' => $found->getKey(), 'ids' => [$found->getKey()], 'error' => null];
    }

    protected function findOne(RelationSpec $spec, string $value, ImportContext $ctx)
    {
        $modelClass = $spec->relatedModel;
        $fillable = (new $modelClass())->getFillable();
        $query = $modelClass::query();

        if (in_array('is_deleted', $fillable, true)) {
            $query->where('is_deleted', 0);
        }
        // Some referenced tables (e.g. Unit) are global, not business-scoped -
        // only apply the scope when the column actually exists, so a
        // Definition declaring scopeToBusiness=true against such a table
        // degrades to an unscoped lookup instead of a SQL error.
        if ($spec->scopeToBusiness && $ctx->businessId && in_array('business_id', $fillable, true)) {
            $query->where('business_id', $ctx->businessId);
        }
        if ($spec->scopeToBranch && $ctx->branchId && in_array('branch_id', $fillable, true)) {
            $query->where('branch_id', $ctx->branchId);
        }

        return $query->whereRaw('LOWER(' . $spec->relatedLabelColumn . ') = ?', [Str::lower(trim($value))])->first();
    }
}
