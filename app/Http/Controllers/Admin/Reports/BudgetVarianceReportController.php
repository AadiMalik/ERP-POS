<?php

namespace App\Http\Controllers\Admin\Reports;

use App\Http\Controllers\Controller;
use App\Models\Budget;
use App\Services\Concrete\Admin\BudgetVarianceService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Budget vs Actual + variance, computed live from posted JournalEntryDetail
 * rows against the selected Budget's lines - see BudgetVarianceService.
 * Visible in BOTH Simple and Advanced Accounting Mode (unlike the other
 * Fiscal Year/Period/Budget-management screens): it is read-only and speaks
 * "budget/actual/variance", never debit/credit.
 */
class BudgetVarianceReportController extends Controller
{
    use ResponseAPI;

    public function __construct(protected BudgetVarianceService $variance_service)
    {
        $this->middleware('permission:reports.budget-vs-actual.view');
    }

    public function index()
    {
        $business_id = Auth::user()->business_id;

        $budgets = Budget::where('business_id', $business_id)
            ->where('is_deleted', 0)
            ->where('status', 'active')
            ->orderByDesc('date_created')
            ->get();

        return view('admin.reports.budget_vs_actual.index', compact('budgets'));
    }

    public function data(Request $request)
    {
        try {
            $budget = Budget::findOrFail($request->budget_id);

            return $this->success('Data fetched successfully!', $this->variance_service->varianceReport($budget, $request->branch_id ?: null));
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
