<?php

namespace App\Services\Concrete\Admin;

use App\Enums\Filter;
use App\Enums\RoleNames;
use App\Enums\Status;
use App\Models\ComplimentaryReason;
use App\Repository\Repository;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;

class ComplimentaryReasonService
{
    public const DEFAULT_REASONS = [
        ['name' => 'Guest Complimentary', 'code' => 'guest'],
        ['name' => 'Marketing', 'code' => 'marketing'],
        ['name' => 'Promotion', 'code' => 'promotion'],
        ['name' => 'Customer Relationship', 'code' => 'customer_relationship'],
        ['name' => 'Staff Motivation', 'code' => 'staff_motivation'],
        ['name' => 'Management Approval', 'code' => 'management_approval'],
        ['name' => 'Sample / Demo', 'code' => 'sample_demo'],
        ['name' => 'Goodwill', 'code' => 'goodwill'],
        ['name' => 'Other', 'code' => 'other'],
    ];

    protected $model_complimentary_reason;
    protected $with = ['business'];

    public function __construct()
    {
        $this->model_complimentary_reason = new Repository(new ComplimentaryReason());
    }

    public function getData($obj)
    {
        $wh = [];
        $orderBy = Filter::ORDERBY;

        if (isset($obj['orderBy']) && $obj['orderBy'] != 0 && $obj['orderBy'] != "") {
            $orderBy = $obj['orderBy'];
        }
        if (isset($obj['business_id']) && $obj['business_id'] != 0 && $obj['business_id'] != "") {
            $wh[] = ['business_id', $obj['business_id']];
        }
        if (!empty($obj['start_date'])) {
            $wh[] = ['date_created', '>=', businessStartOfDay($obj['start_date'])];
        }
        if (!empty($obj['end_date'])) {
            $wh[] = ['date_created', '<=', businessEndOfDay($obj['end_date'])];
        }

        $allow_roles = [
            RoleNames::SUPERADMIN,
            RoleNames::BUSINESSADMIN,
            RoleNames::SALEMANAGER,
            RoleNames::POSMANAGER,
            RoleNames::BRANCHADMIN,
        ];

        $datatable = $this->model_complimentary_reason->getModel()::where($wh)
            ->with($this->with)
            ->where('is_deleted', 0)
            ->orderBy('date_created', $orderBy);
        $datatable = applyRoleScope($datatable, $allow_roles);

        return DataTables::of($datatable)
            ->addColumn('business', function ($item) {
                return $item->business->name ?? '';
            })
            ->addColumn('status', function ($item) {
                $badge = $item->status == Status::ACTIVE ? 'bg-label-success' : 'bg-label-secondary';
                return '<span class="badge ' . $badge . '">' . ucfirst($item->status) . '</span>';
            })
            ->addColumn('action', function ($item) {
                return "
                    <a class='btn btn-icon btn-outline-primary mr-2'
                     id='editComplimentaryReason' href='javascript:void(0)'
                      data-id='" . $item->complimentary_reason_id . "'><i class='icon-base fa fa-pencil'></i></a>

                    <a class='btn btn-icon btn-outline-danger'
                    id='deleteComplimentaryReason'
                    data-id='{$item->complimentary_reason_id}'>

                    <i class='fa fa-trash'></i>
                    </a>
                ";
            })
            ->rawColumns(['business', 'status', 'action'])
            ->make(true);
    }

    public function getById($complimentary_reason_id)
    {
        return $this->model_complimentary_reason->getModel()::with($this->with)->find($complimentary_reason_id);
    }

    public function save($obj)
    {
        $business_id = $obj['business_id'] ?? Auth::user()->business_id;

        $data = [
            'business_id' => $business_id,
            'name'        => $obj['name'],
            'status'      => $obj['status'] ?? Status::ACTIVE,
        ];

        if (!empty($obj['complimentary_reason_id'])) {
            $data['updatedby_id'] = Auth::id();
            $data['date_updated'] = now();

            $this->model_complimentary_reason->update($data, $obj['complimentary_reason_id']);
            return $this->model_complimentary_reason->find($obj['complimentary_reason_id']);
        }

        $data['complimentary_reason_id'] = generateUuid();
        $data['is_deleted'] = 0;
        $data['createdby_id'] = Auth::id();
        $data['date_created'] = now();

        return $this->model_complimentary_reason->create($data);
    }

    public function delete($complimentary_reason_id)
    {
        return $this->model_complimentary_reason->update([
            'is_deleted'   => 1,
            'deletedby_id' => Auth::id(),
            'date_deleted' => now(),
        ], $complimentary_reason_id);
    }

    public function seedDefaults($business_id)
    {
        if (empty($business_id)) {
            return;
        }

        $exists = ComplimentaryReason::where('business_id', $business_id)
            ->where('is_deleted', 0)
            ->exists();

        if ($exists) {
            return;
        }

        foreach (self::DEFAULT_REASONS as $row) {
            ComplimentaryReason::create([
                'complimentary_reason_id' => generateUuid(),
                'business_id' => $business_id,
                'name' => $row['name'],
                'code' => $row['code'],
                'status' => Status::ACTIVE,
                'is_deleted' => 0,
                'createdby_id' => Auth::id(),
                'date_created' => now(),
            ]);
        }
    }

    public function getActiveByBusiness($business_id)
    {
        $this->seedDefaults($business_id);

        return $this->model_complimentary_reason->getModel()::where('business_id', $business_id)
            ->where('status', Status::ACTIVE)
            ->where('is_deleted', 0)
            ->orderBy('name')
            ->get();
    }

    public function findActiveForBusiness($complimentary_reason_id, $business_id)
    {
        if (empty($complimentary_reason_id) || empty($business_id)) {
            return null;
        }

        return ComplimentaryReason::where('complimentary_reason_id', $complimentary_reason_id)
            ->where('business_id', $business_id)
            ->where('status', Status::ACTIVE)
            ->where('is_deleted', 0)
            ->first();
    }
}
