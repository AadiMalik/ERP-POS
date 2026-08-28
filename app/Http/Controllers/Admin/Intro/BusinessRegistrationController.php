<?php

namespace App\Http\Controllers\Admin\Intro;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\Intro\BusinessRegistrationService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BusinessRegistrationController extends Controller
{
    use ResponseAPI;

    protected $service;

    public function __construct(BusinessRegistrationService $service)
    {
        $this->middleware('superadmin');
        $this->middleware('permission:intro-business.view')->only(['index', 'getData', 'show']);
        $this->middleware('permission:intro-business.edit')->only(['updateStatus']);
        $this->service = $service;
    }

    public function index()
    {
        return view('admin.intro.business_registrations.index');
    }

    public function getData(Request $request)
    {
        return $this->service->getData($request->all());
    }

    public function show($id)
    {
        try {
            return $this->success(Message::FETCH, $this->service->getById($id));
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function updateStatus(Request $request, $id)
    {
        $validate = Validator::make($request->all(), [
            'status' => 'required|string|max:50',
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }
        try {
            $this->service->updateStatus($id, $request->status);
            return $this->success(Message::STATUS, []);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
