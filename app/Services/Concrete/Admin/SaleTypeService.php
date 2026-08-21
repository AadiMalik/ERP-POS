<?php

namespace App\Services\Concrete\Admin;

use App\Enums\Filter;
use App\Enums\RoleNames;
use App\Enums\Status;
use App\Models\SaleType;
use App\Repository\Repository;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;

class SaleTypeService
{
    protected $model_sale_type;

    // Lazily seeded default sale types for a business that has none yet.
    protected $default_sale_types = [
        ['name' => 'Retail', 'code' => 'RETAIL'],
        ['name' => 'Wholesale', 'code' => 'WHOLESALE'],
        ['name' => 'Branch', 'code' => 'BRANCH'],
        ['name' => 'Promotional', 'code' => 'PROMOTIONAL'],
        ['name' => 'Online', 'code' => 'ONLINE'],
    ];

    public function __construct()
    {
        $this->model_sale_type = new Repository(new SaleType());
    }

    /**
     * Lazily seeds Retail/Wholesale/Branch/Promotional/Online for a business
     * the first time it touches this module - only if it has none yet.
     */
    public function seedDefaults($business_id)
    {
        if (empty($business_id)) {
            return;
        }

        $exists = $this->model_sale_type->getModel()::where('business_id', $business_id)
            ->where('is_deleted', 0)
            ->exists();

        if ($exists) {
            return;
        }

        foreach ($this->default_sale_types as $index => $sale_type) {
            $this->model_sale_type->create([
                'sale_type_id' => generateUuid(),
                'business_id' => $business_id,
                'name' => $sale_type['name'],
                'code' => $sale_type['code'],
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

        $allow_roles = [
            RoleNames::SUPERADMIN,
            RoleNames::BUSINESSADMIN,
        ];

        $datatable = $this->model_sale_type->getModel()::where($wh)
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
                        class="form-check-input statusSaleType"
                        type="checkbox"
                        data-id="' . $item->sale_type_id . '"
                        ' . $checked . '>
                </div>
            ';
            })
            ->addColumn('action', function ($item) {
                return "
                    <a class='btn btn-icon btn-outline-primary mr-2'
                     id='editSaleType' href='javascript:void(0)'
                      data-toggle='tooltip' data-id='" . $item->sale_type_id . "' data-original-title='Edit'><i title='Edit' class='icon-base fa fa-pencil'></i></a>

                    <a class='btn btn-icon btn-outline-danger'
                    id='deleteSaleType'
                    data-id='{$item->sale_type_id}'>
                    <i class='fa fa-trash'></i>
                    </a>
                ";
            })
            ->rawColumns(['is_default', 'status', 'action'])
            ->make(true);
    }

    public function save($obj)
    {
        if (!empty($obj['sale_type_id'])) {
            $obj['updatedby_id'] = Auth::id();
            $obj['date_updated'] = now();
            $this->model_sale_type->update($obj, $obj['sale_type_id']);

            return $this->model_sale_type->find($obj['sale_type_id']);
        }

        $obj['sale_type_id'] = generateUuid();
        $obj['createdby_id'] = Auth::id();
        $obj['date_created'] = now();

        return $this->model_sale_type->create($obj);
    }

    public function getById($sale_type_id)
    {
        return $this->model_sale_type->find($sale_type_id);
    }

    public function status($sale_type_id)
    {
        return $this->model_sale_type->update([
            'status' => ($this->model_sale_type->find($sale_type_id)->status == Status::ACTIVE ? Status::INACTIVE : Status::ACTIVE),
            'updatedby_id' => Auth::id(),
            'date_updated' => now(),
        ], $sale_type_id);
    }

    public function delete($sale_type_id)
    {
        return $this->model_sale_type->update([
            'is_deleted' => 1,
            'deletedby_id' => Auth::id(),
            'date_deleted' => now(),
        ], $sale_type_id);
    }

    /**
     * Plain (non-DataTables) list of every sale type for a business,
     * active or inactive - backs the inline Sale Types manager embedded in
     * the POS Settings tab, where a handful of rows don't warrant a full
     * server-side-processed DataTable.
     */
    public function getAll($business_id = null)
    {
        $business_id = $business_id ?? Auth::user()->business_id;
        $this->seedDefaults($business_id);

        return $this->model_sale_type->getModel()::where('business_id', $business_id)
            ->where('is_deleted', 0)
            ->orderBy('sort_order', 'asc')
            ->orderBy('name')
            ->get();
    }

    public function getAllActive($business_id = null)
    {
        $business_id = $business_id ?? Auth::user()->business_id;
        $this->seedDefaults($business_id);

        return $this->model_sale_type->getModel()::where('business_id', $business_id)
            ->where('status', Status::ACTIVE)
            ->where('is_deleted', 0)
            ->orderBy('sort_order', 'asc')
            ->get();
    }
}
