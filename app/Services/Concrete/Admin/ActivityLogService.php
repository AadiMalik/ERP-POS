<?php

namespace App\Services\Concrete\Admin;

use App\Enums\Filter;
use App\Models\ActivityLog;
use App\Repository\Repository;
use Carbon\Carbon;
use Yajra\DataTables\DataTables;

class ActivityLogService
{
    protected $model_activity_log;
    protected $with = [
        'business',
        'branch',
        'causer',
    ];

    public function __construct()
    {
        $this->model_activity_log = new Repository(new ActivityLog());
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
        if (isset($obj['branch_id']) && $obj['branch_id'] != 0 && $obj['branch_id'] != "") {
            $wh[] = ['branch_id', $obj['branch_id']];
        }
        if (isset($obj['module']) && $obj['module'] != 0 && $obj['module'] != "") {
            $wh[] = ['module', $obj['module']];
        }
        if (isset($obj['action']) && $obj['action'] != 0 && $obj['action'] != "") {
            $wh[] = ['action', $obj['action']];
        }
        if (isset($obj['causer_id']) && $obj['causer_id'] != 0 && $obj['causer_id'] != "") {
            $wh[] = ['causer_id', $obj['causer_id']];
        }
        if (!empty($obj['start_date'])) {
            $wh[] = ['date_created', '>=', Carbon::parse($obj['start_date'])->startOfDay()];
        }
        if (!empty($obj['end_date'])) {
            $wh[] = ['date_created', '<=', Carbon::parse($obj['end_date'])->endOfDay()];
        }

        $datatable = $this->model_activity_log->getModel()::with($this->with)
            ->where($wh)
            ->orderBy('date_created', $orderBy);
        $datatable = applyRoleScope($datatable);

        return DataTables::of($datatable)
            ->addColumn('date_created', function ($item) {
                return !empty($item->date_created) ? localDateTime($item->date_created) : 'N/A';
            })
            ->addColumn('module', function ($item) {
                return ucfirst(str_replace('_', ' ', $item->module ?? ''));
            })
            ->addColumn('action', function ($item) {
                $colors = [
                    'created'  => 'success',
                    'updated'  => 'info',
                    'deleted'  => 'danger',
                    'posted'   => 'primary',
                    'unposted' => 'warning',
                    'approved' => 'success',
                    'exported' => 'secondary',
                    'import_completed' => 'success',
                    'import_completed_with_errors' => 'warning',
                    'import_failed' => 'danger',
                    'rejected' => 'danger',
                    'login'    => 'secondary',
                    'logout'   => 'secondary',
                ];
                $color = $colors[$item->action] ?? 'secondary';
                $label = ucfirst(str_replace('_', ' ', $item->action ?? ''));

                return "<span class='badge bg-{$color}'>{$label}</span>";
            })
            ->addColumn('causer', function ($item) {
                return $item->causer->name ?? 'System';
            })
            ->addColumn('branch', function ($item) {
                return $item->branch->name ?? '';
            })
            ->addColumn('business', function ($item) {
                return $item->business->name ?? '';
            })
            ->rawColumns(['action'])
            ->make(true);
    }
}
