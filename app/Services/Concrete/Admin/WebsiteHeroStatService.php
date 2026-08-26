<?php

namespace App\Services\Concrete\Admin;

use App\Enums\Filter;
use App\Enums\RoleNames;
use App\Enums\Status;
use App\Models\WebsiteHeroStat;
use App\Repository\Repository;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;

class WebsiteHeroStatService
{
    protected $model_link;

    public function __construct()
    {
        $this->model_link = new Repository(new WebsiteHeroStat());
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
                    <input class="form-check-input statusHeroStat" type="checkbox" data-id="' . $item->hero_stat_id . '" ' . $checked . '>
                </div>';
            })
            ->addColumn('preview', function ($item) {
                $color = $item->icon_color ?: '#666';
                $icon = $item->icon ?: 'fa fa-chart-simple';
                return '<i class="' . e($icon) . '" style="color:' . e($color) . ';font-size:20px;"></i>';
            })
            ->addColumn('action', function ($item) {
                return "
                    <a class='btn btn-icon btn-outline-primary mr-2' id='editHeroStat' href='javascript:void(0)' data-id='{$item->hero_stat_id}' data-original-title='Edit'><i class='icon-base fa fa-pencil'></i></a>
                    <a class='btn btn-icon btn-outline-danger' id='deleteHeroStat' data-id='{$item->hero_stat_id}'><i class='fa fa-trash'></i></a>
                ";
            })
            ->rawColumns(['status', 'preview', 'action'])
            ->make(true);
    }

    public function save($obj)
    {
        if (!empty($obj['hero_stat_id'])) {
            $obj['updatedby_id'] = Auth::id();
            $obj['date_updated'] = now();
            $this->model_link->update($obj, $obj['hero_stat_id']);
            return $this->model_link->find($obj['hero_stat_id']);
        }

        $obj['hero_stat_id'] = generateUuid();
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
            ->map(fn($s) => [
                'id' => $s->hero_stat_id,
                'value' => $s->value,
                'label' => $s->label,
                'icon' => $s->icon,
                'icon_color' => $s->icon_color,
            ])
            ->values();
    }
}
