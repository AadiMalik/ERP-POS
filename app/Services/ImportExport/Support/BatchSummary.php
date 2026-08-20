<?php

namespace App\Services\ImportExport\Support;

/**
 * Aggregate counts + the full row/group tree for one import batch - used
 * both as the JSON response to the preview UI and as import_batches.summary_json.
 */
class BatchSummary
{
    /**
     * @param ResolvedRow[] $groups
     */
    public function __construct(
        public string $moduleKey,
        public int $rowCount = 0,
        public int $validCount = 0,
        public int $invalidCount = 0,
        public int $createCount = 0,
        public int $updateCount = 0,
        public int $failedCount = 0,
        public array $groups = [],
    ) {
    }

    public static function fromGroups(string $moduleKey, array $groups): self
    {
        $summary = new self($moduleKey);
        $summary->groups = $groups;
        $summary->rowCount = count($groups);

        foreach ($groups as $group) {
            if ($group->action === 'invalid') {
                $summary->invalidCount++;
            } else {
                $summary->validCount++;
                $group->action === 'update' ? $summary->updateCount++ : $summary->createCount++;
            }
        }

        return $summary;
    }

    public function toArray(): array
    {
        return [
            'module' => $this->moduleKey,
            'row_count' => $this->rowCount,
            'valid_count' => $this->validCount,
            'invalid_count' => $this->invalidCount,
            'create_count' => $this->createCount,
            'update_count' => $this->updateCount,
            'failed_count' => $this->failedCount,
            'groups' => array_map(fn (ResolvedRow $g) => $g->toArray(), $this->groups),
        ];
    }

    public function errorSample(int $limit = 50): array
    {
        $errors = [];

        foreach ($this->groups as $group) {
            foreach ($group->errors as $error) {
                $errors[] = array_merge(['row' => $group->rowNumber], $error);
                if (count($errors) >= $limit) {
                    return $errors;
                }
            }
            foreach ($group->children as $child) {
                foreach ($child->errors as $error) {
                    $errors[] = array_merge(['row' => $child->rowNumber], $error);
                    if (count($errors) >= $limit) {
                        return $errors;
                    }
                }
            }
        }

        return $errors;
    }
}
