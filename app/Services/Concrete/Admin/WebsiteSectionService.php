<?php

namespace App\Services\Concrete\Admin;

use App\Enums\Filter;
use App\Enums\RoleNames;
use App\Enums\Status;
use App\Models\WebsiteSection;
use App\Repository\Repository;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;

class WebsiteSectionService
{
    protected $model_section;

    public function __construct()
    {
        $this->model_section = new Repository(new WebsiteSection());
    }

    public function getData($obj)
    {
        $wh = [];
        $orderBy = Filter::ORDERBY;

        if (isset($obj['business_id']) && $obj['business_id'] != '') {
            $wh[] = ['business_id', $obj['business_id']];
        }
        if (!empty($obj['type'])) {
            $wh[] = ['type', $obj['type']];
        }

        $allow_roles = [RoleNames::SUPERADMIN, RoleNames::BUSINESSADMIN];
        $datatable = $this->model_section->getModel()::where($wh)
            ->where('is_deleted', 0)
            ->orderBy('type')
            ->orderBy('sort_order', $orderBy);
        $datatable = applyRoleScope($datatable, $allow_roles);

        return DataTables::of($datatable)
            ->addColumn('status', function ($item) {
                $checked = $item->status == Status::ACTIVE ? 'checked' : '';
                return '
                <div class="form-check form-switch mb-0">
                    <input class="form-check-input statusWebsiteSection" type="checkbox" data-id="' . $item->section_id . '" ' . $checked . '>
                </div>';
            })
            ->addColumn('image', function ($item) {
                if (!$item->image_url) {
                    return '-';
                }
                return '<img src="' . $item->image_url . '" alt="" style="width:50px;height:50px;object-fit:contain;border:1px solid #ddd;border-radius:5px;padding:2px;background:#fff;">';
            })
            ->addColumn('action', function ($item) {
                return "
                    <a class='btn btn-icon btn-outline-primary mr-2' id='editWebsiteSection' href='javascript:void(0)' data-id='{$item->section_id}' data-original-title='Edit'><i class='icon-base fa fa-pencil'></i></a>
                    <a class='btn btn-icon btn-outline-danger' id='deleteWebsiteSection' data-id='{$item->section_id}'><i class='fa fa-trash'></i></a>
                ";
            })
            ->rawColumns(['status', 'image', 'action'])
            ->make(true);
    }

    public function save($obj)
    {
        if (!empty($obj['section_id'])) {
            $obj['updatedby_id'] = Auth::id();
            $obj['date_updated'] = now();
            $this->model_section->update($obj, $obj['section_id']);
            return $this->model_section->find($obj['section_id']);
        }

        $obj['section_id'] = generateUuid();
        $obj['createdby_id'] = Auth::id();
        $obj['date_created'] = now();
        return $this->model_section->create($obj);
    }

    public function getById($section_id)
    {
        return $this->model_section->find($section_id);
    }

    public function status($section_id)
    {
        return $this->model_section->update([
            'status' => ($this->model_section->find($section_id)->status == Status::ACTIVE ? Status::INACTIVE : Status::ACTIVE),
            'updatedby_id' => Auth::id(),
            'date_updated' => now(),
        ], $section_id);
    }

    public function delete($section_id)
    {
        return $this->model_section->update([
            'is_deleted' => 1,
            'deletedby_id' => Auth::id(),
            'date_deleted' => now(),
        ], $section_id);
    }

    /**
     * Public storefront read - active sections for a business, optionally
     * filtered by type, ordered for display. Used by the homepage aggregator
     * and any page-specific section endpoints.
     */
    public function getActivePublicByBusiness($business_id, $type = null)
    {
        $query = $this->model_section->getModel()::where('business_id', $business_id)
            ->where('status', Status::ACTIVE)
            ->where('is_deleted', 0);

        if ($type) {
            $query->where('type', $type);
        }

        return $query->orderBy('sort_order')->get()->map(function ($s) {
            return [
                'id' => $s->section_id,
                'type' => $s->type,
                'tagline' => $s->tagline,
                'tagline_icon' => $s->tagline_icon,
                'heading' => $s->heading,
                'heading_icon' => $s->heading_icon,
                'description' => $s->description,
                'image' => $s->image_url,
                'image_mobile' => $s->image_mobile_url,
                'button_text' => $s->button_text,
                'button_link' => $s->button_link,
                'link_type' => $s->link_type,
                'link_target_id' => $s->link_target_id,
                'secondary_button_text' => $s->secondary_button_text,
                'secondary_button_link' => $s->secondary_button_link,
                'secondary_link_type' => $s->secondary_link_type,
                'secondary_link_target_id' => $s->secondary_link_target_id,
                'countdown_end_at' => $s->countdown_end_at,
                'sort_order' => $s->sort_order,
            ];
        })->values();
    }
}
