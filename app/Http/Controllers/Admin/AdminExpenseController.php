<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Message;
use App\Enums\PaymentMethod;
use App\Enums\Status;
use App\Http\Controllers\Concerns\HandlesImportExport;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\AccountService;
use App\Services\Concrete\Admin\BranchService;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\ExpenseCategoryService;
use App\Services\Concrete\Admin\ExpenseService;
use App\Services\Concrete\Admin\SettingService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * "Admin Expense" - an administrator's own business/daily expenses recorded
 * without requiring a POS session or Order Taker. Thin wrapper around
 * ExpenseService, which every save/post/unpost/delete/JV rule (see
 * ExpenseController for the full "Expense Detail" CRUD) is shared with -
 * this controller only fixes pos_register_session_id/user_id to null and
 * source to 'admin' so the two screens never mix records.
 */
class AdminExpenseController extends Controller
{
    use ResponseAPI;
    use HandlesImportExport;

    protected $expense_service;
    protected $expense_category_service;
    protected $business_service;
    protected $branch_service;
    protected $account_service;
    protected $setting_service;

    public function __construct(
        ExpenseService $expense_service,
        ExpenseCategoryService $expense_category_service,
        BusinessService $business_service,
        BranchService $branch_service,
        AccountService $account_service,
        SettingService $setting_service
    ) {
        $this->middleware('permission:admin-expense.manage');
        $this->middleware('permission:admin-expense.import')->only(['importSample', 'importPreview', 'importConfirm']);
        $this->middleware('permission:admin-expense.export')->only(['export']);
        $this->middleware('module:admin-expense');

        $this->expense_service = $expense_service;
        $this->expense_category_service = $expense_category_service;
        $this->business_service = $business_service;
        $this->branch_service = $branch_service;
        $this->account_service = $account_service;
        $this->setting_service = $setting_service;
    }

    protected function importExportModuleKey(): string
    {
        return 'admin-expense';
    }

    public function index()
    {
        $business = $this->business_service->getAll();
        $categories = $this->expense_category_service->getByBusiness(Auth::user()->business_id);
        $statuses = [
            Status::PENDING => ucfirst(Status::PENDING),
            Status::POSTED  => ucfirst(Status::POSTED),
        ];

        return view('admin.admin_expense.index', compact('business', 'categories', 'statuses'));
    }

    public function getData(Request $request)
    {
        $obj = $request->all();
        $obj['no_session'] = 1;
        $obj['source'] = 'admin';

        return $this->expense_service->getData($obj);
    }

    public function create()
    {
        return $this->form();
    }

    public function edit($expense_id)
    {
        $expense = $this->expense_service->getById($expense_id);

        if (!$expense) {
            return redirect('admin/admin-expense')->with('error', Message::NOTFOUND);
        }

        return $this->form($expense);
    }

    protected function form($expense = null)
    {
        $business_id = $expense->business_id ?? Auth::user()->business_id;

        $business = $this->business_service->getAll();
        $branches = $this->branch_service->getByBusiness($business_id);
        $categories = $this->expense_category_service->getActiveByBusiness($business_id);
        $accounting_setting = $this->setting_service->getAccountingSetting($business_id);
        $accounts = $this->account_service->getChildByBusiness($business_id);
        $expense_no = generateExpenseNo($business_id);

        return view('admin.admin_expense.create', compact(
            'expense',
            'business',
            'branches',
            'categories',
            'accounting_setting',
            'accounts',
            'expense_no'
        ));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'expense_category_id' => ['required', Rule::exists('expense_categories', 'expense_category_id')->where('is_deleted', 0)],
            'expense_date'        => ['required', 'date'],
            'payment_method'      => ['required', 'in:' . PaymentMethod::CASH . ',' . PaymentMethod::BANK_TRANSFER . ',' . PaymentMethod::CHEQUE . ',' . PaymentMethod::ONLINE],
            'payment_account_id'  => ['nullable', Rule::exists('accounts', 'account_id')->where('is_deleted', 0)],
            'reference_no'        => ['nullable', 'string', 'max:191'],
            'amount'              => ['required', 'numeric', 'min:0.01'],
            'description'         => ['nullable', 'string'],
            'attachment'          => ['nullable', 'file', 'max:5120'],
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            $obj = $request->all();
            $obj['expense_date'] = ($request->expense_id) ? utcDate($request->expense_date, true) : utcDate($request->expense_date);
            $obj['business_id'] = $request->business_id ?? Auth::user()->business_id;
            $obj['branch_id'] = $request->branch_id ?? Auth::user()->branch_id ?? null;
            $obj['pos_register_session_id'] = null;
            $obj['user_id'] = null;
            $obj['source'] = 'admin';

            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $path = public_path('uploads/expense');
                if (!File::exists($path)) {
                    File::makeDirectory($path, 0755, true);
                }
                $file->move($path, $fileName);
                $obj['attachment'] = $fileName;
            }

            $this->expense_service->save($obj);

            return redirect('admin/admin-expense')
                ->with('success', empty($request->expense_id) ? Message::SAVE : Message::UPDATE);
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function status(Request $request)
    {
        $rules = [
            'expense_id' => 'required|exists:expenses,expense_id',
            'status'     => 'required|in:' . Status::PENDING . ',' . Status::POSTED,
        ];

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        try {
            $this->expense_service->status($request->all());
            return $this->success(Message::STATUS, []);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function destroy($expense_id)
    {
        try {
            $this->expense_service->delete($expense_id);
            return $this->success(Message::DELETE, []);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
