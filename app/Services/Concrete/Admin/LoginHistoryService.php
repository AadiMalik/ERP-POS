<?php

namespace App\Services\Concrete\Admin;

use App\Enums\Filter;
use App\Models\LoginHistory;
use App\Repository\Repository;
use Carbon\Carbon;
use Yajra\DataTables\DataTables;

class LoginHistoryService
{
    protected $model_login_history;
    protected $with = [
        'user',
        'business',
    ];

    public function __construct()
    {
        $this->model_login_history = new Repository(new LoginHistory());
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
        if (isset($obj['user_id']) && $obj['user_id'] != 0 && $obj['user_id'] != "") {
            $wh[] = ['user_id', $obj['user_id']];
        }
        if (isset($obj['status']) && $obj['status'] != 0 && $obj['status'] != "") {
            $wh[] = ['status', $obj['status']];
        }
        if (!empty($obj['start_date'])) {
            $wh[] = ['login_at', '>=', Carbon::parse($obj['start_date'])->startOfDay()];
        }
        if (!empty($obj['end_date'])) {
            $wh[] = ['login_at', '<=', Carbon::parse($obj['end_date'])->endOfDay()];
        }

        $datatable = $this->model_login_history->getModel()::with($this->with)
            ->where($wh)
            ->orderBy('login_at', $orderBy);
        $datatable = applyRoleScope($datatable);

        return DataTables::of($datatable)
            ->addColumn('login_at', function ($item) {
                return !empty($item->login_at) ? localDateTime($item->login_at) : 'N/A';
            })
            ->addColumn('logout_at', function ($item) {
                return !empty($item->logout_at) ? localDateTime($item->logout_at) : '';
            })
            ->addColumn('user', function ($item) {
                return $item->user->name ?? $item->email ?? 'Unknown';
            })
            ->addColumn('business', function ($item) {
                return $item->business->name ?? '';
            })
            ->addColumn('status', function ($item) {
                $color = $item->status === 'success' ? 'success' : 'danger';

                return "<span class='badge bg-{$color}'>" . ucfirst($item->status ?? '') . "</span>";
            })
            ->rawColumns(['status'])
            ->make(true);
    }
}
