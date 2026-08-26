<?php

namespace App\Services\Concrete\Admin;

use App\Enums\Filter;
use App\Enums\RoleNames;
use App\Enums\Status;
use App\Models\WebsiteTestimonial;
use App\Repository\Repository;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;

class WebsiteTestimonialService
{
    protected $model_link;

    public function __construct()
    {
        $this->model_link = new Repository(new WebsiteTestimonial());
    }

    public function getData($obj)
    {
        $wh = [];
        $orderBy = Filter::ORDERBY;
        if (isset($obj['business_id']) && $obj['business_id'] != '') {
            $wh[] = ['business_id', $obj['business_id']];
        }

        $allow_roles = [RoleNames::SUPERADMIN, RoleNames::BUSINESSADMIN];
        $datatable = $this->model_link->getModel()::where($wh)
            ->where('is_deleted', 0)
            ->orderBy('sort_order', $orderBy);
        $datatable = applyRoleScope($datatable, $allow_roles);

        return DataTables::of($datatable)
            ->addColumn('status', function ($item) {
                $checked = $item->status == Status::ACTIVE ? 'checked' : '';
                return '
                <div class="form-check form-switch mb-0">
                    <input class="form-check-input statusTestimonial" type="checkbox" data-id="' . $item->testimonial_id . '" ' . $checked . '>
                </div>';
            })
            ->addColumn('preview', function ($item) {
                return $item->avatar_url
                    ? '<img src="' . e($item->avatar_url) . '" style="width:32px;height:32px;border-radius:50%;object-fit:cover;">'
                    : '<i class="fa fa-circle-user" style="font-size:22px;color:#999;"></i>';
            })
            ->addColumn('action', function ($item) {
                return "
                    <a class='btn btn-icon btn-outline-primary mr-2' id='editTestimonial' href='javascript:void(0)' data-id='{$item->testimonial_id}' data-original-title='Edit'><i class='icon-base fa fa-pencil'></i></a>
                    <a class='btn btn-icon btn-outline-danger' id='deleteTestimonial' data-id='{$item->testimonial_id}'><i class='fa fa-trash'></i></a>
                ";
            })
            ->rawColumns(['status', 'preview', 'action'])
            ->make(true);
    }

    public function save($obj)
    {
        if (!empty($obj['testimonial_id'])) {
            $obj['updatedby_id'] = Auth::id();
            $obj['date_updated'] = now();
            $this->model_link->update($obj, $obj['testimonial_id']);
            return $this->model_link->find($obj['testimonial_id']);
        }

        $obj['testimonial_id'] = generateUuid();
        $obj['createdby_id'] = Auth::id();
        $obj['date_created'] = now();
        return $this->model_link->create($obj);
    }

    public function getById($id)
    {
        return $this->model_link->find($id);
    }

    public function status($id)
    {
        return $this->model_link->update([
            'status' => ($this->model_link->find($id)->status == Status::ACTIVE ? Status::INACTIVE : Status::ACTIVE),
            'updatedby_id' => Auth::id(),
            'date_updated' => now(),
        ], $id);
    }

    public function delete($id)
    {
        return $this->model_link->update([
            'is_deleted' => 1,
            'deletedby_id' => Auth::id(),
            'date_deleted' => now(),
        ], $id);
    }

    public function getActivePublicByBusiness($business_id)
    {
        return $this->model_link->getModel()::where('business_id', $business_id)
            ->where('status', Status::ACTIVE)
            ->where('is_deleted', 0)
            ->orderBy('sort_order')
            ->get()
            ->map(fn($t) => [
                'id' => $t->testimonial_id,
                'author_name' => $t->author_name,
                'author_title' => $t->author_title,
                'avatar' => $t->avatar_url,
                'quote' => $t->quote,
                'rating' => $t->rating,
            ])
            ->values();
    }
}
