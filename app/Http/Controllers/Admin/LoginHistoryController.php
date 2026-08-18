<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\LoginHistoryService;
use Illuminate\Http\Request;

class LoginHistoryController extends Controller
{
    protected $login_history_service;
    protected $business_service;

    public function __construct(LoginHistoryService $login_history_service, BusinessService $business_service)
    {
        $this->middleware('permission:login-history.view');

        $this->login_history_service = $login_history_service;
        $this->business_service = $business_service;
    }

    public function index()
    {
        $business = $this->business_service->getAll();

        return view('admin.login-history.index', compact('business'));
    }

    public function getData(Request $request)
    {
        return $this->login_history_service->getData($request->all());
    }
}
