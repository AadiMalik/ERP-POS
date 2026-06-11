<?php

namespace App\Services\Concrete\Admin;

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

    public function __construct()
    {
        $this->model_business = new Repository(new Business());
        $this->model_package = new Repository(new Package());
        $this->model_business_subscription = new Repository(new BusinessSubscription());
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
                if ($item->status == 'active') {
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
        DB::transaction(function () use ($obj) {
            if (!empty($obj['business_id'])) {
                $obj['updatedby_id'] = Auth::user()->id;
                $obj['date_updated'] = now();
                $this->model_business->update($obj, $obj['business_id']);
                return $this->model_business->find($obj['business_id']);
            }
            $obj['business_id'] = generateUuid();
            $obj['createdby_id'] = Auth::user()->id;
            $obj['date_created'] = now();
            $saved_obj = $this->model_business->create($obj);
            $this->subscription($obj['package_id'], $saved_obj);
            return $saved_obj;
        });
    }

    private function subscription($package_id, $business)
    {
        $package = $this->model_package->getModel()::findOrFail($package_id);

        $startDate = now();
        $endDate = now()->addDays($package->duration_days);

        $business->update([
            'package_id' => $package->package_id,
            'subscription_start' => $startDate,
            'subscription_end' => $endDate,
        ]);

        $package_subscription = $this->model_business_subscription->getModel()::create([
            'business_subscription_id' => generateUuid(),
            'business_id' => $business->business_id,
            'package_id' => $package->package_id,
            'start_at' => $startDate,
            'end_at' => $endDate,
            'subtotal' => $package->price,
            'discount' => 0,
            'discount_amount' => 0,
            'tax' => 0,
            'tax_amount' => 0,
            'total' => $package->price,
            'payment_method' => 'cash',
            'payment_status' => 'paid',
            'payment_reference' => 'admin created',
            'status' => 'active',
            'createdby_id' => Auth::id(),
            'date_created' => now(),
        ]);
        return $package_subscription;
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
}
