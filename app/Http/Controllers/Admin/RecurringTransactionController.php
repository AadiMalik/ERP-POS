<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Message;
use App\Enums\RecurringFrequency;
use App\Enums\RecurringTransactionType;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\AccountService;
use App\Services\Concrete\Admin\BranchService;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\ExpenseCategoryService;
use App\Services\Concrete\Admin\JournalService;
use App\Services\Concrete\Admin\RecurringTransactionService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class RecurringTransactionController extends Controller
{
    use ResponseAPI;

    protected $recurring_transaction_service;
    protected $business_service;
    protected $branch_service;
    protected $expense_category_service;
    protected $account_service;
    protected $journal_service;

    public function __construct(
        RecurringTransactionService $recurring_transaction_service,
        BusinessService $business_service,
        BranchService $branch_service,
        ExpenseCategoryService $expense_category_service,
        AccountService $account_service,
        JournalService $journal_service
    ) {
        $this->middleware('permission:recurring-transaction.view')->only(['index', 'getData', 'previewNextRun']);
        $this->middleware('permission:recurring-transaction.view-history')->only(['history', 'historyData']);
        $this->middleware('permission:recurring-transaction.create')->only(['create']);
        $this->middleware('permission:recurring-transaction.create|recurring-transaction.edit')->only(['store']);
        $this->middleware('permission:recurring-transaction.edit')->only(['edit']);
        $this->middleware('permission:recurring-transaction.delete')->only(['destroy']);
        $this->middleware('permission:recurring-transaction.pause')->only(['pause']);
        $this->middleware('permission:recurring-transaction.resume')->only(['resume']);
        $this->middleware('permission:recurring-transaction.cancel')->only(['cancel']);
        $this->middleware('permission:recurring-transaction.run-now')->only(['runNow']);

        $this->recurring_transaction_service = $recurring_transaction_service;
        $this->business_service = $business_service;
        $this->branch_service = $branch_service;
        $this->expense_category_service = $expense_category_service;
        $this->account_service = $account_service;
        $this->journal_service = $journal_service;
    }

    public function index()
    {
        $business = $this->business_service->getAll();
        $types = RecurringTransactionType::labels();
        $frequencies = RecurringFrequency::all();

        return view('admin.recurring_transaction.index', compact('business', 'types', 'frequencies'));
    }

    public function getData(Request $request)
    {
        return $this->recurring_transaction_service->getData($request->all());
    }

    public function create()
    {
        return $this->form();
    }

    public function edit($recurring_transaction_id)
    {
        $rt = $this->recurring_transaction_service->getById($recurring_transaction_id);

        if (!$rt) {
            return redirect('admin/recurring-transaction')->with('error', Message::NOTFOUND);
        }

        return $this->form($rt);
    }

    protected function form($rt = null)
    {
        $business_id = $rt->business_id ?? Auth::user()->business_id;

        $business = $this->business_service->getAll();
        $branches = $this->branch_service->getByBusiness($business_id);
        $categories = $this->expense_category_service->getActiveByBusiness($business_id);
        $accounts = $this->account_service->getChildByBusiness($business_id);
        $journals = $this->journal_service->getAll();
        $types = RecurringTransactionType::labels();

        return view('admin.recurring_transaction.create', compact(
            'rt',
            'business',
            'branches',
            'categories',
            'accounts',
            'journals',
            'types'
        ));
    }

    public function store(Request $request)
    {
        $rules = [
            'transaction_type' => ['required', 'in:' . implode(',', RecurringTransactionType::all())],
            'name'             => ['required', 'string', 'max:150'],
            'frequency'        => ['required', 'in:' . implode(',', RecurringFrequency::all())],
            'start_date'       => ['required', 'date'],
            'end_date'         => ['nullable', 'date', 'after_or_equal:start_date'],
            'max_occurrences'  => ['nullable', 'integer', 'min:1'],
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            $obj = $request->all();
            $obj['business_id'] = $request->business_id ?? Auth::user()->business_id;
            $obj['branch_id'] = $request->branch_id ?? Auth::user()->branch_id ?? null;
            $obj['start_date'] = utcDate($request->start_date);
            $obj['end_date'] = $request->end_date ? utcDate($request->end_date) : null;
            $obj['template_data'] = $this->buildTemplateData($request);

            $this->recurring_transaction_service->save($obj);

            return redirect('admin/recurring-transaction')
                ->with('success', empty($request->recurring_transaction_id) ? Message::SAVE : Message::UPDATE);
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }

    protected function buildTemplateData(Request $request): array
    {
        if ($request->transaction_type === RecurringTransactionType::EXPENSE) {
            return [
                'expense_category_id' => $request->expense_category_id,
                'payment_method'      => $request->expense_payment_method,
                'payment_account_id'  => $request->expense_payment_account_id,
                'reference_no'        => $request->expense_reference_no,
                'amount'              => $request->expense_amount,
                'description'         => $request->expense_description,
                'user_id'             => $request->expense_user_id ?: null,
            ];
        }

        if ($request->transaction_type === RecurringTransactionType::JOURNAL_ENTRY) {
            $lines = json_decode($request->je_lines, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid journal entry lines data.');
            }

            return [
                'journal_id'   => $request->je_journal_id,
                'reference_no' => $request->je_reference_no,
                'description'  => $request->je_description,
                'lines'        => $lines ?? [],
            ];
        }

        throw new Exception('Unsupported recurring transaction type.');
    }

    public function previewNextRun(Request $request)
    {
        try {
            $dates = $this->recurring_transaction_service->previewNextRun($request->all(), 5);
            return $this->success(Message::SUCCESS, $dates);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function pause($recurring_transaction_id)
    {
        try {
            $this->recurring_transaction_service->pause($recurring_transaction_id);
            return $this->success(Message::STATUS, []);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function resume($recurring_transaction_id)
    {
        try {
            $this->recurring_transaction_service->resume($recurring_transaction_id);
            return $this->success(Message::STATUS, []);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function cancel($recurring_transaction_id)
    {
        try {
            $this->recurring_transaction_service->cancel($recurring_transaction_id);
            return $this->success(Message::STATUS, []);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function runNow($recurring_transaction_id)
    {
        try {
            $run = $this->recurring_transaction_service->runNow($recurring_transaction_id, Auth::id());

            if ($run->status !== 'success') {
                return $this->error($run->error_message ?? 'Run failed.');
            }

            return $this->success('Transaction generated successfully.', []);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function destroy($recurring_transaction_id)
    {
        try {
            $this->recurring_transaction_service->delete($recurring_transaction_id);
            return $this->success(Message::DELETE, []);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function history($recurring_transaction_id)
    {
        $rt = $this->recurring_transaction_service->getById($recurring_transaction_id);

        if (!$rt) {
            return redirect('admin/recurring-transaction')->with('error', Message::NOTFOUND);
        }

        return view('admin.recurring_transaction.history', compact('rt'));
    }

    public function historyData(Request $request, $recurring_transaction_id)
    {
        return $this->recurring_transaction_service->getExecutionHistory($recurring_transaction_id, $request->all());
    }
}
