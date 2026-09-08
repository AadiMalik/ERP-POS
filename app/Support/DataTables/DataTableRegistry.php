<?php

namespace App\Support\DataTables;

use App\DataTables\CustomerDataTable;
use App\DataTables\SupplierDataTable;
use App\Support\DataTables\Contracts\DataTableDefinition;
use InvalidArgumentException;

/**
 * Maps a unique table/module key to its DataTable definition class.
 * Add one line here when a new ERP list screen joins the engine.
 */
class DataTableRegistry
{
    public static function definitions(): array
    {
        return [
            'customers' => CustomerDataTable::class,
            'suppliers' => SupplierDataTable::class,
        ];
    }

    public static function has(string $key): bool
    {
        return isset(self::definitions()[$key]);
    }

    public static function resolve(string $key): DataTableDefinition
    {
        $map = self::definitions();

        if (!isset($map[$key])) {
            throw new InvalidArgumentException("Unknown DataTable key [{$key}].");
        }

        $definition = app($map[$key]);

        if (!$definition instanceof DataTableDefinition) {
            throw new InvalidArgumentException("DataTable [{$key}] must implement DataTableDefinition.");
        }

        return $definition;
    }

    public static function keys(): array
    {
        return array_keys(self::definitions());
    }

    public static function keyForImportExportModule(string $module): ?string
    {
        foreach (self::definitions() as $key => $class) {
            $listing = app($class)->listing();
            if (($listing['import_export_module'] ?? null) === $module) {
                return $key;
            }
        }

        return null;
    }
}
