<?php

namespace App\Services\Concrete\Admin;

use App\Enums\Status;
use App\Models\Business;
use App\Models\BusinessSubscription;
use App\Models\Package;
use App\Repository\Repository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

class BusinessService
{
    protected $model_business;
    protected $model_package;
    protected $model_business_subscription;
    protected SubscriptionService $subscription_service;
    protected ChartOfAccountsCloneService $chart_of_accounts_clone_service;
    protected AccountingSettingCloneService $accounting_setting_clone_service;
    protected WebsiteCmsDefaultsService $website_cms_defaults_service;

    public function __construct(
        SubscriptionService $subscription_service,
        ChartOfAccountsCloneService $chart_of_accounts_clone_service,
        AccountingSettingCloneService $accounting_setting_clone_service,
        WebsiteCmsDefaultsService $website_cms_defaults_service
    ) {
        $this->model_business = new Repository(new Business());
        $this->model_package = new Repository(new Package());
        $this->model_business_subscription = new Repository(new BusinessSubscription());
        $this->subscription_service = $subscription_service;
        $this->chart_of_accounts_clone_service = $chart_of_accounts_clone_service;
        $this->accounting_setting_clone_service = $accounting_setting_clone_service;
        $this->website_cms_defaults_service = $website_cms_defaults_service;
    }

    public function getData($data)
    {
        $datatable = $this->model_business->getModel()::with('package')->where('is_deleted', 0);
        return DataTables::of($datatable)
            ->addColumn('package', function ($item) {
                return $item->package?->name ?? '-';
            })
            ->addColumn('remaining_days', function ($item) {
                if (empty($item->subscription_end)) {
                    return '-';
                }
                return now()->diffInDays($item->subscription_end, false);
            })
            ->addColumn('status', function ($item) {
                if ($item->status == Status::ACTIVE) {
                    return '
                    <span class="badge bg-label-success me-1 mb-1">
                        Active
                    </span>
                ';
                } elseif ($item->status == 'suspended') {
                    return '
                    <span class="badge bg-label-warning me-1 mb-1">
                        Suspended
                    </span>
                ';
                } elseif ($item->status == Status::PENDING || $item->status == Status::UNDER_REVIEW) {
                    $label = $item->status == Status::UNDER_REVIEW ? 'Under Review' : 'Pending';
                    return '
                    <span class="badge bg-label-info me-1 mb-1">
                        ' . $label . '
                    </span>
                ';
                } else {
                    return '
                    <span class="badge bg-label-danger me-1 mb-1">
                        Expired
                    </span>
                ';
                }
            })
            ->addColumn('action', function ($item) {

                return "
                    <a class='btn btn-icon btn-outline-primary mr-2'
                     href='" . route('business.edit', $item->business_id) . "'
                    id='editBusiness'>

                    <i class='fa fa-pencil'></i>
                    </a>

                    <a class='btn btn-icon btn-outline-danger'
                    id='deleteBusiness'
                    data-id='{$item->business_id}'>

                    <i class='fa fa-trash'></i>
                    </a>
                ";
            })
            ->rawColumns(['package', 'status', 'remaining_days', 'action'])
            ->make(true);
    }

    public function save($obj)
    {
        return DB::transaction(function () use ($obj) {
            if (!empty($obj['business_id'])) {
                // Package/subscription changes are handled exclusively
                // through the dedicated Renew flow (SubscriptionService),
                // never through this edit path.
                unset($obj['package_id']);
                $obj['updatedby_id'] = Auth::user()->id;
                $obj['date_updated'] = now();
                $this->model_business->update($obj, $obj['business_id']);
                return $this->model_business->find($obj['business_id']);
            }
            $obj['business_id'] = generateUuid();
            $obj['createdby_id'] = Auth::user()->id;
            $obj['date_created'] = now();
            $package_id = $obj['package_id'];
            unset($obj['package_id']);
            $saved_obj = $this->model_business->create($obj);
            $package = $this->model_package->getModel()::findOrFail($package_id);
            $this->subscription_service->createInitial($saved_obj, $package);
            $account_id_map = $this->chart_of_accounts_clone_service->cloneTemplateToBusiness($saved_obj->business_id);
            $this->accounting_setting_clone_service->cloneTemplateToBusiness($saved_obj->business_id, $account_id_map);
            $this->website_cms_defaults_service->seed($saved_obj->business_id);
            return $saved_obj->fresh();
        });
    }

    public function getById($business_id)
    {
        return $this->model_business->find($business_id);
    }

    public function delete($business_id)
    {
        return $this->model_business->update([
            'is_deleted' => 1,
            'deletedby_id' => Auth::id(),
            'date_deleted' => now()
        ], $business_id);
    }

    public function getAll()
    {
        return $this->model_business->getModel()::where('is_deleted', 0)->get();
    }

    public function getAllActive()
    {
        return $this->model_business->getModel()::where('status', Status::ACTIVE)->where('is_deleted', 0)->get();
    }
}
