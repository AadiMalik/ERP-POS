<?php

namespace App\Http\Controllers\Admin\Accounting;

use App\Enums\Message;
use App\Models\Account;
use App\Models\Branch;
use App\Models\Budget;
use App\Models\BudgetLine;
use App\Models\FiscalYear;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\BudgetGenerationService;
use App\Services\Concrete\Admin\Reports\Accounting\AccountClassifier;
use App\Traits\Auditable;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * Advanced Mode budget CRUD. Auto-generation (Simple mode's "Budgeting =
 * Auto" setting) runs headlessly - see BudgetGenerationService and its
 * scheduler-adjacent trigger - this controller's generate() action is the
 * Advanced Mode "Generate / Regenerate" button, reusing the exact same
 * service so both paths behave identically.
 */
class BudgetController extends Controller
{
    use ResponseAPI, Auditable;

    public function __construct(protected BudgetGenerationService $generation_service, protected AccountClassifier $classifier)
    {
        $this->middleware('permission:budget.view')->only(['index', 'getData', 'edit']);
        $this->middleware('permission:budget.create|budget.edit')->only(['store', 'saveLine']);
        $this->middleware('permission:budget.delete')->only(['destroy']);
        $this->middleware('permission:budget.generate')->only(['generate']);
        $this->middleware(function ($request, $next) {
            if (!businessAccountingAdvancedModeEnabled()) {
                abort(403, 'Advanced Accounting Mode is not enabled for this business.');
            }

            return $next($request);
        });
    }

    public function index()
    {
        $business_id = Auth::user()->business_id;

        $fiscal_years = FiscalYear::where('business_id', $business_id)->where('is_deleted', 0)->orderByDesc('start_date')->get();
        $accounts = Account::where('business_id', $business_id)->where('is_deleted', 0)->orderBy('code')->get();
        $branches = Branch::where('business_id', $business_id)->where('is_deleted', 0)->get();

        return view('admin.budget.index', compact('fiscal_years', 'accounts', 'branches'));
    }

    public function getData(Request $request)
    {
        $business_id = $request->business_id ?? Auth::user()->business_id;

        $budgets = Budget::with('fiscalYear')
            ->where('business_id', $business_id)
            ->where('is_deleted', 0)
            ->orderByDesc('date_created')
            ->get();

        return $this->success(Message::FETCH, $budgets);
    }

    public function store(Request $request)
    {
        $rules = [
            'budget_id'       => 'nullable|exists:budgets,budget_id',
            'fiscal_year_id'  => 'required|exists:fiscal_years,fiscal_year_id',
            'name'            => 'required|string|max:150',
            'granularity'     => 'required|in:monthly,quarterly,yearly',
            'generation_mode' => 'required|in:auto,manual',
            'growth_percent'  => 'nullable|numeric|min:-100|max:1000',
        ];

        $validate = Validator::make($request->all(), $rules);

        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        try {
            $business_id = $request->business_id ?? Auth::user()->business_id;
            $is_update = !empty($request->budget_id);

            if ($is_update) {
                $budget = Budget::findOrFail($request->budget_id);
            } else {
                $budget = new Budget();
                $budget->budget_id = generateUuid();
                $budget->business_id = $business_id;
                $budget->status = 'draft';
                $budget->createdby_id = Auth::id();
                $budget->date_created = now();
            }

            $budget->fiscal_year_id = $request->fiscal_year_id;
            $budget->name = $request->name;
            $budget->granularity = $request->granularity;
            $budget->generation_mode = $request->generation_mode;
            $budget->growth_percent = $request->growth_percent;
            $budget->updatedby_id = Auth::id();
            $budget->date_updated = now();
            $budget->save();

            $this->logActivity('budget', $budget->budget_id, $is_update ? 'updated' : 'created', null, $request->only(['name', 'granularity', 'generation_mode']), null, $business_id);

            return $this->success($is_update ? Message::UPDATE : Message::SAVE, $budget);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function edit($budget_id)
    {
        try {
            $budget = Budget::with('fiscalYear')->findOrFail($budget_id);
            $lines = BudgetLine::where('budget_id', $budget_id)->with('account', 'branch')->orderBy('period_start')->get();

            return $this->success(Message::FETCH, ['budget' => $budget, 'lines' => $lines]);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function destroy($budget_id)
    {
        try {
            $budget = Budget::findOrFail($budget_id);
            $budget->update(['is_deleted' => 1, 'deletedby_id' => Auth::id(), 'date_deleted' => now()]);

            $this->logActivity('budget', $budget_id, 'deleted', null, null, null, $budget->business_id);

            return $this->success(Message::DELETE, []);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Runs BudgetGenerationService for this budget - the same engine the
     * Simple-mode "Budgeting = Auto" setting uses headlessly, so Advanced
     * Mode's "Generate" button and the automatic path never diverge.
     */
    public function generate($budget_id)
    {
        try {
            $budget = Budget::findOrFail($budget_id);
            $count = $this->generation_service->generate($budget);

            return $this->success(Message::SUCCESS, ['lines_written' => $count]);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Advanced Mode manual budget line entry - account-wise, optionally
     * branch-wise, for one monthly/quarterly/yearly slice at a time.
     */
    public function saveLine(Request $request, $budget_id)
    {
        $rules = [
            'account_id'      => 'required|exists:accounts,account_id',
            'branch_id'       => 'nullable|exists:branches,branch_id',
            'period_start'    => 'required|date',
            'period_end'      => 'required|date|after_or_equal:period_start',
            'budgeted_amount' => 'required|numeric',
        ];

        $validate = Validator::make($request->all(), $rules);

        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        try {
            $budget = Budget::findOrFail($budget_id);
            $account = Account::with('accountType')->findOrFail($request->account_id);
            $debit_normal = $this->classifier->isDebitNormal(optional($account->accountType)->name);

            $line = BudgetLine::firstOrNew([
                'budget_id'    => $budget->budget_id,
                'account_id'   => $request->account_id,
                'branch_id'    => $request->branch_id ?: null,
                'period_start' => $request->period_start,
            ]);

            if (!$line->exists) {
                $line->budget_line_id = generateUuid();
                $line->createdby_id = Auth::id();
                $line->date_created = now();
            }

            $line->period_end = $request->period_end;
            $line->account_debit_normal = $debit_normal;
            $line->budgeted_amount = $request->budgeted_amount;
            $line->updatedby_id = Auth::id();
            $line->date_updated = now();
            $line->save();

            return $this->success(Message::SAVE, $line);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
