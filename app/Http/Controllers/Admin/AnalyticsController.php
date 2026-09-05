<?php

namespace App\Http\Controllers\Admin;

use App\Exports\Analytics\AnalyticsWidgetExport;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\Analytics\AnalyticsAccessService;
use App\Services\Concrete\Admin\Analytics\AnalyticsFilterOptionsService;
use App\Services\Concrete\Admin\Analytics\AnalyticsService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class AnalyticsController extends Controller
{
    public function __construct(
        protected AnalyticsAccessService $access_service,
        protected AnalyticsFilterOptionsService $filter_options_service,
        protected AnalyticsService $analytics_service
    ) {
        $this->middleware('permission:analytics.view')->only(['index', 'data', 'table']);
        $this->middleware('permission:analytics.export')->only(['export']);
    }

    public function index(Request $request)
    {
        $scope = $this->access_service->resolveScope($request);

        if (!$scope['allowed']) {
            return view('admin.analytics.index', [
                'restricted' => true,
                'scope' => $scope,
                'filter_options' => [],
                'widgets' => AnalyticsService::widgetKeys(),
            ]);
        }

        return view('admin.analytics.index', [
            'restricted' => false,
            'scope' => $scope,
            'filter_options' => $this->filter_options_service->build($scope),
            'widgets' => AnalyticsService::widgetKeys(),
        ]);
    }

    public function data(Request $request, string $widget)
    {
        $scope = $this->access_service->resolveScope($request);
        abort_unless($scope['allowed'] ?? false, 403);

        return response()->json($this->analytics_service->widgetData($widget, $scope));
    }

    public function table(Request $request, string $widget)
    {
        $scope = $this->access_service->resolveScope($request);
        abort_unless($scope['allowed'] ?? false, 403);

        return response()->json($this->analytics_service->tableData($widget, $scope));
    }

    public function export(Request $request, string $widget)
    {
        $scope = $this->access_service->resolveScope($request);
        abort_unless($scope['allowed'] ?? false, 403);

        $payload = $this->analytics_service->exportPayload($widget, $scope);
        $filename = 'analytics-' . $widget . '-' . now()->format('Ymd-His') . '.xlsx';

        return Excel::download(
            new AnalyticsWidgetExport($payload['rows'], $payload['headings']),
            $filename
        );
    }
}
