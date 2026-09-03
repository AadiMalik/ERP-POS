<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RoleNames;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use App\Services\Concrete\Admin\ActivityLogService;
use App\Services\Concrete\Admin\BusinessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class ActivityLogController extends Controller
{
    protected $activity_log_service;
    protected $business_service;

    public function __construct(ActivityLogService $activity_log_service, BusinessService $business_service)
    {
        $this->middleware('permission:activity-log.view');

        $this->activity_log_service = $activity_log_service;
        $this->business_service = $business_service;
    }

    public function index()
    {
        $business = $this->business_service->getAll();

        // Module/action options are sourced from the actual logged data
        // (cached briefly) instead of a hand-maintained list, so the filters
        // never drift out of sync as new modules/actions start logging.
        $modules = Cache::remember('activity_log_filter_modules', 300, function () {
            return ActivityLog::query()
                ->whereNotNull('module')
                ->distinct()
                ->orderBy('module')
                ->pluck('module')
                ->mapWithKeys(fn ($module) => [$module => ActivityLog::prettifyLabel($module)]);
        });

        $actions = Cache::remember('activity_log_filter_actions', 300, function () {
            return ActivityLog::query()
                ->whereNotNull('action')
                ->distinct()
                ->orderBy('action')
                ->pluck('action')
                ->mapWithKeys(fn ($action) => [$action => ActivityLog::prettifyLabel($action)]);
        });

        $is_superadmin = RoleNames::SUPERADMIN == getRoleName();
        $causers = $is_superadmin ? collect() : User::where('business_id', Auth::user()->business_id)
            ->where('is_deleted', 0)
            ->get();

        return view('admin.activity-log.index', compact('business', 'modules', 'actions', 'causers'));
    }

    public function getData(Request $request)
    {
        return $this->activity_log_service->getData($request->all());
    }
}
