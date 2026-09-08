<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Message;
use App\Enums\RoleNames;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\AccountService;
use App\Services\Concrete\Admin\BankService;
use App\Services\Concrete\Admin\BranchService;
use App\Services\Concrete\Admin\BusinessService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class BankController extends Controller
{
    use ResponseAPI;

    protected $bank_service;
    protected $business_service;
    protected $branch_service;
    protected $account_service;

    public function __construct(
        BankService $bank_service,
        BusinessService $business_service,
        BranchService $branch_service,
        AccountService $account_service
    ) {
        $this->middleware('permission:bank.view')->only(['index', 'getData']);
        $this->middleware('permission:bank.create')->only(['create']);
        $this->middleware('permission:bank.create|bank.edit')->only(['store']);
        $this->middleware('permission:bank.edit')->only(['edit']);
        $this->middleware('permission:bank.delete')->only(['destroy']);
        $this->middleware('permission:bank.status')->only(['status']);

        $this->bank_service = $bank_service;
        $this->business_service = $business_service;
        $this->branch_service = $branch_service;
        $this->account_service = $account_service;
    }

    public function index()
    {
        $business = $this->business_service->getAll();
        $branches = $this->branch_service->getAll();
        return view('admin.bank.index', compact('business', 'branches'));
    }

    public function getData(Request $request)
    {
        return $this->bank_service->getData($request->all());
    }

    public function create()
    {
        $business = (getRoleName() == RoleNames::SUPERADMIN) ? $this->business_service->getAll() : [];
        $branches = $this->branch_service->getAllActive();
        $accounts = $this->account_service->getChildByBusiness(Auth::user()->business_id);
        return view('admin.bank.create', compact('business', 'branches', 'accounts'));
    }

    public function store(Request $request)
    {
        $rules = [
            'name' => [
                'required',
                Rule::unique('banks', 'name')
                    ->where(function ($query) use ($request) {
                        return $query->where('business_id', $request->business_id ?? Auth::user()->business_id)
                            ->where('is_deleted', 0);
                    })
                    ->ignore($request->bank_id, 'bank_id'),
            ],
        ];
        if (getRoleName() == RoleNames::SUPERADMIN) {
            $rules['business_id'] = 'required|exists:businesses,business_id';
        }

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return redirect()->back()->withErrors($validate)->withInput();
        }

        $obj = $request->only([
            'bank_id',
            'name',
            'code',
            'account_number',
            'branch_id',
            'account_id',
        ]);
        $obj['business_id'] = $request->business_id ?? Auth::user()->business_id;
        $obj['status'] = $request->status ?? 'active';

        $this->bank_service->save($obj);

        return redirect('admin/bank')
            ->with('success', empty($request->bank_id) ? Message::SAVE : Message::UPDATE);
    }

    public function edit($bank_id)
    {
        $bank = $this->bank_service->getById($bank_id);
        $business = $this->business_service->getAll();
        $branches = $this->branch_service->getAllActive();
        $accounts = $this->account_service->getChildByBusiness($bank->business_id);
        return view('admin.bank.create', compact('bank', 'business', 'branches', 'accounts'));
    }

    public function status($bank_id)
    {
        try {
            $this->bank_service->status($bank_id);
            return $this->success(Message::STATUS, []);
        } catch (Exception $e) {
            return $this->error(Message::ERROR);
        }
    }

    public function destroy($bank_id)
    {
        try {
            $this->bank_service->delete($bank_id);
            return $this->success(Message::DELETE, []);
        } catch (Exception $e) {
            return $this->error(Message::ERROR);
        }
    }

    /**
     * Banks selectable for a given branch (linked to it, or shared across
     * every branch) - used by the Order list's Branch->Bank filter cascade.
     * POS itself doesn't call this: PosScreenController::index() already
     * bakes the resolved branch's banks into POS_CONFIG at render time.
     */
    public function forBranch(Request $request)
    {
        try {
            $business_id = $request->business_id ?? Auth::user()->business_id;
            $branch_id = $request->branch_id;

            if (empty($business_id) || empty($branch_id)) {
                return $this->success(Message::FETCH, []);
            }

            return $this->success(Message::FETCH, $this->bank_service->getForBranch($business_id, $branch_id));
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
