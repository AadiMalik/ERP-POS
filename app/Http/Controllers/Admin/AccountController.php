<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\AccountService;
use App\Services\Concrete\Admin\AccountTypeService;
use App\Services\Concrete\Admin\BusinessService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AccountController extends Controller
{
    use ResponseAPI;
    protected $account_service;
    protected $account_type_service;
    protected $business_service;
    public function __construct(
        AccountService  $account_service,
        AccountTypeService  $account_type_service,
        BusinessService $business_service
    ) {
        $this->account_service = $account_service;
        $this->account_type_service = $account_type_service;
        $this->business_service = $business_service;
    }

    public function index() {
        $business = $this->business_service->getAll();
        $account_types = $this->account_type_service->getAll();
        $data = $this->account_service->getData();
        return view('admin.account.index', compact('business','data','account_types'));
    }

    public function parentByAccountType($account_type_id)
    {
        try {
            $accounts = $this->account_service->getParentByAccountType($account_type_id);
            return $this->success(
                Message::SUCCESS,
                $accounts
            );
        } catch (Exception $e) {
            return $this->error(
                Message::ERROR
            );
        }
    }

    public function parentByAccountSubType($account_sub_type_id)
    {
        try {
            $accounts = $this->account_service->getParentByAccountSubType($account_sub_type_id);
            return $this->success(
                Message::SUCCESS,
                $accounts
            );
        } catch (Exception $e) {
            return $this->error(
                Message::ERROR
            );
        }
    }
}
