<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\ActivityLogService;
use App\Services\Concrete\Admin\BusinessService;
use Illuminate\Http\Request;

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
        $modules = [
            'expense'                    => 'Expense',
            'order'                      => 'Order',
            'supplier'                   => 'Supplier',
            'customer'                   => 'Customer',
            'journal_entry'              => 'Journal Entry',
            'purchase_request_quotation' => 'Purchase Request Quotation',
            'purchase_return'            => 'Purchase Return',
            'auth'                       => 'Auth',
        ];
        $actions = [
            'created'  => 'Created',
            'updated'  => 'Updated',
            'deleted'  => 'Deleted',
            'posted'   => 'Posted',
            'unposted' => 'Unposted',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
        ];

        return view('admin.activity-log.index', compact('business', 'modules', 'actions'));
    }

    public function getData(Request $request)
    {
        return $this->activity_log_service->getData($request->all());
    }
}
