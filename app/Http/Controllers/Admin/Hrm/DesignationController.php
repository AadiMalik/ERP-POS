<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\Hrm\DepartmentService;
use App\Services\Concrete\Admin\Hrm\DesignationService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class DesignationController extends Controller
{
    use ResponseAPI;

    protected $designation_service;
    protected $department_service;

    public function __construct(DesignationService $designation_service, DepartmentService $department_service)
    {
        $this->middleware('permission:designation.view')->only(['index', 'getData']);
        $this->middleware('permission:designation.create')->only(['create']);
        $this->middleware('permission:designation.create|designation.edit')->only(['store']);
        $this->middleware('permission:designation.edit')->only(['edit']);
        $this->middleware('permission:designation.delete')->only(['destroy']);

        $this->designation_service = $designation_service;
        $this->department_service = $department_service;
    }

    public function index()
    {
        $departments = $this->department_service->getAllActive();
        return view('admin.hrm.designation.index', compact('departments'));
    }

    public function getData(Request $request)
    {
        return $this->designation_service->getData($request->all());
    }

    public function create()
    {
        $departments = $this->department_service->getAllActive();
        return view('admin.hrm.designation.create', compact('departments'));
    }

    public function store(Request $request)
    {
        $rules = [
            'name' => [
                'required',
                Rule::unique('designations', 'name')
                    ->where(function ($query) use ($request) {
                        return $query->where('business_id', Auth::user()->business_id)
                            ->where('is_deleted', 0);
                    })
                    ->ignore($request->designation_id, 'designation_id'),
            ],
        ];

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return redirect()->back()->withErrors($validate)->withInput();
        }

        $obj = $request->only(['designation_id', 'department_id', 'name', 'code', 'description', 'status']);
        $obj['business_id'] = Auth::user()->business_id;
        $obj['branch_id'] = Auth::user()->branch_id;
        $obj['status'] = $request->status ?? 'active';

        $this->designation_service->save($obj);

        return redirect('admin/designation')
            ->with('success', empty($request->designation_id) ? Message::SAVE : Message::UPDATE);
    }

    public function edit($designation_id)
    {
        $designation = $this->designation_service->getById($designation_id);
        $departments = $this->department_service->getAllActive();
        return view('admin.hrm.designation.create', compact('designation', 'departments'));
    }

    public function destroy($designation_id)
    {
        try {
            $this->designation_service->delete($designation_id);
            return $this->success(Message::DELETE, []);
        } catch (Exception $e) {
            return $this->error(Message::ERROR);
        }
    }
}
