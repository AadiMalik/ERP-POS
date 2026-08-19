<?php

namespace App\Services\Concrete\Admin;

use App\Models\Package;
use App\Models\PackageModule;
use App\Repository\Repository;
use App\Support\Subscription\SubscriptionModuleRegistry;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

class PackageService
{
    protected $model_package;

    public function __construct()
    {
        $this->model_package =
            new Repository(new Package());
    }

    public function getData($data)
    {
        $datatable = $this->model_package->getModel()::where('is_deleted', 0);
        return DataTables::of($datatable)
            ->addColumn('status', function ($item) {
                return $item->status
                    ? '
                    <span class="badge bg-label-success me-1 mb-1">
                        Active
                    </span>
                '
                    : '
                    <span class="badge bg-label-danger me-1 mb-1">
                        Inactive
                    </span>
                ';
            })
            ->addColumn('action', function ($item) {

                return "
                    <a class='btn btn-icon btn-outline-primary mr-2'
                     href='" . route('packages.edit', $item->package_id) . "'
                    id='editPackage'>
                    <i class='fa fa-pencil'></i>
                    </a>

                    <a class='btn btn-icon btn-outline-warning mr-2'
                     id='viewPackage' href='javascript:void(0)'
                    data-id='{$item->package_id}'>
                    <i class='fa fa-eye'></i>
                    </a>

                    <a class='btn btn-icon btn-outline-danger'
                    id='deletePackage' href='javascript:void(0)'
                    data-id='{$item->package_id}'>
                    <i class='fa fa-trash'></i>
                    </a>
                ";
            })
            ->rawColumns(['status','action'])
            ->make(true);
    }

    public function save($obj)
    {
        $modules = $obj['modules'] ?? [];
        unset($obj['modules']);

        return DB::transaction(function () use ($obj, $modules) {
            if (!empty($obj['package_id'])) {
                $obj['updatedby_id'] = Auth::user()->id;
                $obj['date_updated'] = now();
                $this->model_package->update($obj, $obj['package_id']);
                $package = $this->model_package->find($obj['package_id']);
            } else {
                $obj['package_id'] = generateUuid();
                $obj['createdby_id'] = Auth::user()->id;
                $obj['date_created'] = now();
                $package = $this->model_package->create($obj);
            }

            $this->saveModules($package->package_id, $modules);

            return $package;
        });
    }

    /**
     * Upserts one package_modules row per SubscriptionModuleRegistry entry
     * from the submitted `modules[<key>][enabled|limit|unlimited]` matrix.
     * Any module missing from $modules (e.g. a feature-only module whose
     * checkbox was left unchecked, so it never posted) is treated as
     * disabled.
     */
    protected function saveModules(string $packageId, array $modules)
    {
        foreach (SubscriptionModuleRegistry::modules() as $key => $meta) {
            if ($meta['type'] === 'core') {
                continue;
            }

            $submitted = $modules[$key] ?? [];
            $isEnabled = !empty($submitted['enabled']);
            $unlimitedAllowed = $meta['unlimited_allowed'] ?? false;
            $isUnlimited = $unlimitedAllowed && !empty($submitted['unlimited']);
            $limitValue = null;

            if ($meta['type'] === 'limited' && !$isUnlimited) {
                $limitValue = isset($submitted['limit']) && $submitted['limit'] !== ''
                    ? max(0, (int) $submitted['limit'])
                    : ($meta['default_limit'] ?? 5);
            }

            PackageModule::updateOrCreate(
                ['package_id' => $packageId, 'module_key' => $key],
                [
                    'is_enabled' => $isEnabled,
                    'is_unlimited' => $isUnlimited,
                    'limit_value' => $limitValue,
                ]
            );
        }
    }

    public function getById($package_id)
    {
        return $this->model_package->find($package_id)->load('modules');
    }

    public function delete($package_id)
    {
        return $this->model_package->update([
            'is_deleted' => 1,
            'deletedby_id' => Auth::id(),
            'date_deleted' => now()
        ], $package_id);
    }

    public function getAll()
    {
        return $this->model_package->getModel()::where('is_deleted', 0)->get();
    }
}
