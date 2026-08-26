<?php

namespace App\Services\Concrete\Admin;

use App\Enums\Filter;
use App\Enums\RoleNames;
use App\Enums\Status;
use App\Models\WebsiteFaq;
use App\Repository\Repository;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;

class WebsiteFaqService
{
    protected $model_faq;

    public function __construct()
    {
        $this->model_faq = new Repository(new WebsiteFaq());
    }

    public function getData($obj)
    {
        $wh = [];
        $orderBy = Filter::ORDERBY;
        if (isset($obj['business_id']) && $obj['business_id'] != '') {
            $wh[] = ['business_id', $obj['business_id']];
        }

        $allow_roles = [RoleNames::SUPERADMIN, RoleNames::BUSINESSADMIN];
        $datatable = $this->model_faq->getModel()::where($wh)
            ->where('is_deleted', 0)
            ->orderBy('sort_order', $orderBy);
        $datatable = applyRoleScope($datatable, $allow_roles);

        return DataTables::of($datatable)
            ->addColumn('status', function ($item) {
                $checked = $item->status == Status::ACTIVE ? 'checked' : '';
                return '
                <div class="form-check form-switch mb-0">
                    <input class="form-check-input statusWebsiteFaq" type="checkbox" data-id="' . $item->faq_id . '" ' . $checked . '>
                </div>';
            })
            ->addColumn('action', function ($item) {
                return "
                    <a class='btn btn-icon btn-outline-primary mr-2' id='editWebsiteFaq' href='javascript:void(0)' data-id='{$item->faq_id}' data-original-title='Edit'><i class='icon-base fa fa-pencil'></i></a>
                    <a class='btn btn-icon btn-outline-danger' id='deleteWebsiteFaq' data-id='{$item->faq_id}'><i class='fa fa-trash'></i></a>
                ";
            })
            ->rawColumns(['status', 'action'])
            ->make(true);
    }

    public function save($obj)
    {
        if (!empty($obj['faq_id'])) {
            $obj['updatedby_id'] = Auth::id();
            $obj['date_updated'] = now();
            $this->model_faq->update($obj, $obj['faq_id']);
            return $this->model_faq->find($obj['faq_id']);
        }

        $obj['faq_id'] = generateUuid();
        $obj['createdby_id'] = Auth::id();
        $obj['date_created'] = now();
        return $this->model_faq->create($obj);
    }

    public function getById($faq_id)
    {
        return $this->model_faq->find($faq_id);
    }

    public function status($faq_id)
    {
        return $this->model_faq->update([
            'status' => ($this->model_faq->find($faq_id)->status == Status::ACTIVE ? Status::INACTIVE : Status::ACTIVE),
            'updatedby_id' => Auth::id(),
            'date_updated' => now(),
        ], $faq_id);
    }

    public function delete($faq_id)
    {
        return $this->model_faq->update([
            'is_deleted' => 1,
            'deletedby_id' => Auth::id(),
            'date_deleted' => now(),
        ], $faq_id);
    }

    public function getActivePublicByBusiness($business_id)
    {
        return $this->model_faq->getModel()::where('business_id', $business_id)
            ->where('status', Status::ACTIVE)
            ->where('is_deleted', 0)
            ->orderBy('sort_order')
            ->get()
            ->map(fn($f) => ['id' => $f->faq_id, 'question' => $f->question, 'answer' => $f->answer])
            ->values();
    }
}
