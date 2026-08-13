<?php

namespace App\Services\Concrete\Admin;

use App\Enums\Filter;
use App\Enums\RoleNames;
use App\Enums\Status;
use App\Models\Discount;
use App\Repository\Repository;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;

class DiscountService
{
    protected $model_discount;

    public function __construct()
    {
        $this->model_discount = new Repository(new Discount());
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
            $wh[] = ['date_created', '>=', Carbon::parse($obj['start_date'])->startOfDay()];
        }

        if (!empty($obj['end_date'])) {
            $wh[] = ['date_created', '<=', Carbon::parse($obj['end_date'])->endOfDay()];
        }
        $allow_roles = [
            RoleNames::SUPERADMIN,
            RoleNames::BUSINESSADMIN,
        ];
        $datatable = $this->model_discount->getModel()::where($wh)
            ->where('is_deleted', 0)
            ->orderBy('name', $orderBy);
        $datatable = applyRoleScope($datatable, $allow_roles);
        return DataTables::of($datatable)
            ->addColumn('type', function ($item) {
                return ucfirst($item->type);
            })
            ->addColumn('value', function ($item) {
                return $item->type == 'percent' ? number_format($item->value, 2) . '%' : number_format($item->value, 3);
            })
            ->addColumn('status', function ($item) {

                $checked = $item->status == Status::ACTIVE ? 'checked' : '';

                return '
                <div class="form-check form-switch mb-0">
                    <input
                        class="form-check-input statusDiscount"
                        type="checkbox"
                        data-id="' . $item->discount_id . '"
                        ' . $checked . '>
                </div>
            ';
            })
            ->addColumn('action', function ($item) {

                return "
                    <a class='btn btn-icon btn-outline-primary mr-2'
                     id='editDiscount' href='javascript:void(0)'
                      data-toggle='tooltip'  data-id='" . $item->discount_id . "' data-original-title='Edit'><i title='Edit' class='icon-base fa fa-pencil'></i></a>

                    <a class='btn btn-icon btn-outline-danger'
                    id='deleteDiscount'
                    data-id='{$item->discount_id}'>

                    <i class='fa fa-trash'></i>
                    </a>
                ";
            })
            ->rawColumns(['status', 'action'])
            ->make(true);
    }

    public function save($obj)
    {
        $header = collect($obj)->only([
            'business_id',
            'name',
            'type',
            'value',
            'status',
        ])->toArray();

        if (!empty($obj['discount_id'])) {
            $discount_id = $obj['discount_id'];

            $header['updatedby_id'] = Auth::user()->id;
            $header['date_updated'] = now();

            $this->model_discount->update($header, $discount_id);
        } else {
            $discount_id = generateUuid();

            $header['discount_id'] = $discount_id;
            $header['createdby_id'] = Auth::user()->id;
            $header['date_created'] = now();

            $this->model_discount->create($header);
        }

        return $this->model_discount->getModel()::find($discount_id);
    }

    public function getById($discount_id)
    {
        return $this->model_discount->getModel()::find($discount_id);
    }

    public function status($discount_id)
    {
        return $this->model_discount->update([
            'status' => ($this->model_discount->find($discount_id)->status == Status::ACTIVE ? Status::INACTIVE : Status::ACTIVE),
            'updatedby_id' => Auth::id(),
            'date_updated' => now()
        ], $discount_id);
    }

    public function delete($discount_id)
    {
        return $this->model_discount->update([
            'is_deleted' => 1,
            'deletedby_id' => Auth::id(),
            'date_deleted' => now()
        ], $discount_id);
    }

    /**
     * Lists currently-configured discounts for the POS screen to offer as
     * candidates - a Discount is just a named rate, so "active + this
     * business" is the whole eligibility check.
     */
    public function getAllActive($business_id = null)
    {
        $business_id = $business_id ?? Auth::user()->business_id;

        return $this->model_discount->getModel()::where('business_id', $business_id)
            ->where('status', Status::ACTIVE)
            ->where('is_deleted', 0)
            ->get();
    }
}
