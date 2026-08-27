<?php

namespace App\Services\Concrete\Admin;

use App\Enums\Filter;
use App\Enums\RoleNames;
use App\Enums\Status;
use App\Models\NotificationTemplate;
use App\Repository\Repository;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;
use Carbon\Carbon;

class NotificationTemplateService
{
    protected $model;

    public function __construct()
    {
        $this->model = new Repository(new NotificationTemplate());
    }

    public function getData($obj)
    {
        $wh = [['is_deleted', 0]];
        $orderBy = Filter::ORDERBY;

        if (!empty($obj['orderBy'])) {
            $orderBy = $obj['orderBy'];
        }
        if (!empty($obj['business_id'])) {
            $wh[] = ['business_id', $obj['business_id']];
        }
        if (!empty($obj['status'])) {
            $wh[] = ['status', $obj['status']];
        }
        if (!empty($obj['start_date'])) {
            $wh[] = ['date_created', '>=', Carbon::parse($obj['start_date'])->startOfDay()];
        }
        if (!empty($obj['end_date'])) {
            $wh[] = ['date_created', '<=', Carbon::parse($obj['end_date'])->endOfDay()];
        }

        $datatable = $this->model->getModel()::where($wh)
            ->with(['business', 'createdby'])
            ->orderBy('date_created', $orderBy);

        $datatable = applyRoleScope($datatable, [RoleNames::SUPERADMIN, RoleNames::BUSINESSADMIN]);

        return DataTables::of($datatable)
            ->addColumn('business', fn ($item) => $item->business?->name ?? '-')
            ->addColumn('status', function ($item) {
                $checked = $item->status == Status::ACTIVE ? 'checked' : '';

                return '
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input statusNotificationTemplate" type="checkbox"
                            data-id="' . $item->notification_template_id . '" ' . $checked . '>
                    </div>';
            })
            ->addColumn('action', function ($item) {
                return "
                    <a class='btn btn-icon btn-outline-primary mr-2'
                       href='" . route('notification-template.edit', $item->notification_template_id) . "'>
                        <i class='fa fa-pencil'></i>
                    </a>
                    <a class='btn btn-icon btn-outline-danger' id='deleteNotificationTemplate'
                       data-id='{$item->notification_template_id}'>
                        <i class='fa fa-trash'></i>
                    </a>";
            })
            ->rawColumns(['status', 'action'])
            ->make(true);
    }

    public function save(array $obj)
    {
        if (!empty($obj['data']) && is_string($obj['data'])) {
            $decoded = json_decode($obj['data'], true);
            $obj['data'] = json_last_error() === JSON_ERROR_NONE ? $decoded : null;
        }

        if (!empty($obj['notification_template_id'])) {
            $obj['updatedby_id'] = Auth::id();
            $obj['date_updated'] = now();
            $this->model->update($obj, $obj['notification_template_id']);

            return $this->model->find($obj['notification_template_id']);
        }

        $obj['notification_template_id'] = generateUuid();
        $obj['createdby_id'] = Auth::id();
        $obj['date_created'] = now();
        $obj['status'] = $obj['status'] ?? Status::ACTIVE;

        return $this->model->create($obj);
    }

    public function getById($id)
    {
        return $this->model->find($id);
    }

    public function status($id)
    {
        $template = $this->model->find($id);
        $template->status = $template->status == Status::ACTIVE ? Status::INACTIVE : Status::ACTIVE;
        $template->updatedby_id = Auth::id();
        $template->date_updated = now();
        $template->save();

        return $template;
    }

    public function delete($id)
    {
        $template = $this->model->find($id);
        $template->is_deleted = 1;
        $template->deletedby_id = Auth::id();
        $template->date_deleted = now();
        $template->save();

        return true;
    }

    public function getActiveByBusiness($businessId)
    {
        return $this->model->getModel()::notDeleted()
            ->where('business_id', $businessId)
            ->where('status', Status::ACTIVE)
            ->orderBy('name')
            ->get();
    }
}
