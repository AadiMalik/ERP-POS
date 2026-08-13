<?php

namespace App\Services\Concrete\Admin;

use App\Enums\Filter;
use App\Enums\RoleNames;
use App\Enums\Status;
use App\Models\OrderType;
use App\Repository\Repository;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;

class OrderTypeService
{
    protected $model_order_type;

    // Lazily seeded default order types for a business that has none yet.
    protected $default_types = [
        ['name' => 'Mart', 'code' => 'MART'],
        ['name' => 'Takeaway', 'code' => 'TAKEAWAY'],
        ['name' => 'Delivery', 'code' => 'DELIVERY'],
    ];

    public function __construct()
    {
        $this->model_order_type = new Repository(new OrderType());
    }

    /**
     * Lazily seeds Mart/Takeaway/Delivery for a business the first time it
     * touches this module - only if it has no order types yet.
     */
    public function seedDefaults($business_id)
    {
        if (empty($business_id)) {
            return;
        }

        $exists = $this->model_order_type->getModel()::where('business_id', $business_id)
            ->where('is_deleted', 0)
            ->exists();

        if ($exists) {
            return;
        }

        foreach ($this->default_types as $index => $type) {
            $this->model_order_type->create([
                'order_type_id' => generateUuid(),
                'business_id' => $business_id,
                'name' => $type['name'],
                'code' => $type['code'],
                'is_default' => $index === 0,
                'status' => Status::ACTIVE,
                'sort_order' => $index,
                'is_deleted' => 0,
                'createdby_id' => Auth::id(),
                'date_created' => now(),
            ]);
        }
    }

    public function getData($obj)
    {
        $this->seedDefaults($obj['business_id'] ?? Auth::user()->business_id);

        $wh = [];
        $orderBy = Filter::ORDERBY;

        if (isset($obj['orderBy']) && $obj['orderBy'] != 0 && $obj['orderBy'] != "") {
            $orderBy = $obj['orderBy'];
        }
        if (isset($obj['business_id']) && $obj['business_id'] != 0 && $obj['business_id'] != "") {
            $wh[] = ['business_id', $obj['business_id']];
        }
        if (!empty($obj['start_date'])) {
            $wh[] = ['date_created', '>=', Carbon::parse($obj['start_date'])->startOfDay()];
        }

        if (!empty($obj['end_date'])) {
            $wh[] = ['date_created', '<=', Carbon::parse($obj['end_date'])->endOfDay()];
        }
        $allow_roles = [
            RoleNames::SUPERADMIN,
            RoleNames::BUSINESSADMIN,
        ];
        $datatable = $this->model_order_type->getModel()::where($wh)
            ->where('is_deleted', 0)
            ->orderBy('sort_order', 'asc')
            ->orderBy('name', $orderBy);
        $datatable = applyRoleScope($datatable, $allow_roles);
        return DataTables::of($datatable)
            ->addColumn('is_default', function ($item) {
                return $item->is_default ? '<span class="badge bg-label-primary">Default</span>' : '-';
            })
            ->addColumn('status', function ($item) {

                $checked = $item->status == Status::ACTIVE ? 'checked' : '';

                return '
                <div class="form-check form-switch mb-0">
                    <input
                        class="form-check-input statusOrderType"
                        type="checkbox"
                        data-id="' . $item->order_type_id . '"
                        ' . $checked . '>
                </div>
            ';
            })
            ->addColumn('action', function ($item) {

                return "
                    <a class='btn btn-icon btn-outline-primary mr-2'
                     id='editOrderType' href='javascript:void(0)'
                      data-toggle='tooltip'  data-id='" . $item->order_type_id . "' data-original-title='Edit'><i title='Edit' class='icon-base fa fa-pencil'></i></a>

                    <a class='btn btn-icon btn-outline-danger'
                    id='deleteOrderType'
                    data-id='{$item->order_type_id}'>

                    <i class='fa fa-trash'></i>
                    </a>
                ";
            })
            ->rawColumns(['is_default', 'status', 'action'])
            ->make(true);
    }

    public function save($obj)
    {

        if (!empty($obj['order_type_id'])) {
            $obj['updatedby_id'] = Auth::user()->id;
            $obj['date_updated'] = now();
            $this->model_order_type->update($obj, $obj['order_type_id']);
            return $this->model_order_type->find($obj['order_type_id']);
        }

        $obj['order_type_id'] = generateUuid();
        $obj['createdby_id'] = Auth::user()->id;
        $obj['date_created'] = now();
        $saved_obj = $this->model_order_type->create($obj);
        return $saved_obj;
    }

    public function getById($order_type_id)
    {
        return $this->model_order_type->find($order_type_id);
    }
    public function status($order_type_id)
    {
        return $this->model_order_type->update([
            'status' => ($this->model_order_type->find($order_type_id)->status == Status::ACTIVE ? Status::INACTIVE : Status::ACTIVE),
            'updatedby_id' => Auth::id(),
            'date_updated' => now()
        ], $order_type_id);
    }

    public function delete($order_type_id)
    {
        return $this->model_order_type->update([
            'is_deleted' => 1,
            'deletedby_id' => Auth::id(),
            'date_deleted' => now()
        ], $order_type_id);
    }

    public function getAllActive($business_id = null)
    {
        $business_id = $business_id ?? Auth::user()->business_id;
        $this->seedDefaults($business_id);

        return $this->model_order_type->getModel()::where('business_id', $business_id)
            ->where('status', Status::ACTIVE)
            ->where('is_deleted', 0)
            ->orderBy('sort_order', 'asc')
            ->get();
    }
}
