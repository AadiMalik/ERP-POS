<?php

namespace App\Http\Controllers\Admin\Reports;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Concrete\Admin\BranchService;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\ExpenseCategoryService;
use App\Services\Concrete\Admin\PosRegisterSessionService;
use App\Services\Concrete\Admin\Reports\ExpenseDetailReportService;
use App\Traits\ResponseAPI;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExpenseDetailReportController extends Controller
{
    use ResponseAPI;

    protected $expense_detail_report_service;
    protected $business_service;
    protected $branch_service;
    protected $expense_category_service;
    protected $pos_register_session_service;

    public function __construct(
        ExpenseDetailReportService $expense_detail_report_service,
        BusinessService $business_service,
        BranchService $branch_service,
        ExpenseCategoryService $expense_category_service,
        PosRegisterSessionService $pos_register_session_service
    ) {
        $this->middleware('permission:reports.expense-detail-report.view');

        $this->expense_detail_report_service = $expense_detail_report_service;
        $this->business_service = $business_service;
        $this->branch_service = $branch_service;
        $this->expense_category_service = $expense_category_service;
        $this->pos_register_session_service = $pos_register_session_service;
    }

    public function index()
    {
        $business_id = Auth::user()->business_id;

        $business = $this->business_service->getAll();
        $branches = $this->branch_service->getByBusiness($business_id);
        $categories = $this->expense_category_service->getByBusiness($business_id);
        $sessions = $this->pos_register_session_service->getByBusiness($business_id);
        $users = User::where('business_id', $business_id)->where('is_deleted', 0)->orderBy('name')->get();

        return view('admin.reports.expense_detail_report.index', compact('business', 'branches', 'categories', 'sessions', 'users'));
    }

    public function data(Request $request)
    {
        return $this->expense_detail_report_service->getData($request->all());
    }
}
