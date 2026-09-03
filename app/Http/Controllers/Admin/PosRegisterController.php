<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\BranchService;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\PosRegisterService;
use App\Services\Concrete\Admin\WarehouseService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PosRegisterController extends Controller
{
    use ResponseAPI;

    protected $pos_register_service;
    protected $business_service;
    protected $branch_service;
    protected $warehouse_service;

    public function __construct(
        PosRegisterService $pos_register_service,
        BusinessService $business_service,
        BranchService $branch_service,
        WarehouseService $warehouse_service
    ) {
        $this->middleware('permission:pos-register.view')->only(['index', 'getData', 'edit']);
        $this->middleware('permission:pos-register.create|pos-register.edit')->only(['store']);
        $this->middleware('permission:pos-register.delete')->only(['destroy', 'status']);

        $this->pos_register_service = $pos_register_service;
        $this->business_service = $business_service;
        $this->branch_service = $branch_service;
        $this->warehouse_service = $warehouse_service;
    }

    public function index()
    {
        $business = $this->business_service->getAllActive();
        $branches = $this->branch_service->getAllActive();
        $warehouses = $this->warehouse_service->getAllActive();
        $users = $this->pos_register_service->getAssignableUsers(Auth::user()->business_id);
        return view('admin.pos.register.index', compact('business', 'branches', 'warehouses', 'users'));
    }

    public function getData(Request $request)
    {
        return $this->pos_register_service->getData($request->all());
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $is_superadmin = getRoleName() == \App\Enums\RoleNames::SUPERADMIN;

        // Non-Super-Admin callers cannot spoof tenant identity via the request
        // body - the register always belongs to the caller's own business,
        // regardless of what business_id was posted.
        $business_id = $is_superadmin ? ($request->business_id ?? $user->business_id) : $user->business_id;

        if (!empty($request->pos_register_id)) {
            $existing = $this->pos_register_service->getById($request->pos_register_id);

            if (empty($existing) || !userInBusinessBranchScope($user, $existing->business_id, $existing->branch_id)) {
                return $this->error('This register was not found.');
            }
        }

        // A branch-scoped role (POS Manager/Branch Admin/etc.) can never
        // target a branch other than their own, whether creating a new
        // register or moving an existing one they're allowed to edit.
        if (!$is_superadmin && !userInBusinessBranchScope($user, $business_id, $request->branch_id)) {
            return $this->error('You cannot save a register outside your own branch.');
        }

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'branch_id' => ['required', 'string'],
            'warehouse_id' => ['required', 'string'],
            'mode' => ['required', Rule::in(['manual', 'automatic'])],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('pos_registers', 'code')
                    ->where(function ($query) use ($business_id) {
                        return $query->where('business_id', $business_id)
                            ->where('is_deleted', 0);
                    })
                    ->ignore($request->pos_register_id, 'pos_register_id')
            ],
        ];

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        $obj = $request->only([
            'pos_register_id',
            'name',
            'code',
            'branch_id',
            'warehouse_id',
            'mode',
        ]);
        $obj['business_id'] = $business_id;
        $obj['assigned_user_id'] = $request->mode == 'manual' ? ($request->assigned_user_id ?: null) : null;
        $obj['status'] = $request->status ?? 'active';

        try {
            $pos_register = $this->pos_register_service->save($obj);
            return $this->success(
                empty($request->pos_register_id) ? Message::SAVE : Message::UPDATE,
                $pos_register
            );
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function edit($pos_register_id)
    {
        try {
            $pos_register = $this->pos_register_service->getById($pos_register_id);
            return $this->success(
                Message::FETCH,
                $pos_register
            );
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function status($pos_register_id)
    {
        try {
            $this->pos_register_service->status($pos_register_id);
            return $this->success(
                Message::STATUS,
                []
            );
        } catch (Exception $e) {
            return $this->error(
                Message::ERROR
            );
        }
    }

    public function destroy($pos_register_id)
    {
        try {

            $this->pos_register_service->delete($pos_register_id);

            return $this->success(
                Message::DELETE,
                []
            );
        } catch (Exception $e) {

            return $this->error(
                Message::ERROR
            );
        }
    }
}
