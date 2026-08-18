<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\Hrm\DepartmentService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class DepartmentController extends Controller
{
    use ResponseAPI;

    protected $department_service;

    public function __construct(DepartmentService $department_service)
    {
        $this->middleware('permission:department.view')->only(['index', 'getData']);
        $this->middleware('permission:department.create')->only(['create']);
        $this->middleware('permission:department.create|department.edit')->only(['store']);
        $this->middleware('permission:department.edit')->only(['edit']);
        $this->middleware('permission:department.delete')->only(['destroy']);

        $this->department_service = $department_service;
    }

    public function index()
    {
        return view('admin.hrm.department.index');
    }

    public function getData(Request $request)
    {
        return $this->department_service->getData($request->all());
    }

    public function create()
    {
        return view('admin.hrm.department.create');
    }

    public function store(Request $request)
    {
        $rules = [
            'name' => [
                'required',
                Rule::unique('departments', 'name')
                    ->where(function ($query) use ($request) {
                        return $query->where('business_id', Auth::user()->business_id)
                            ->where('is_deleted', 0);
                    })
                    ->ignore($request->department_id, 'department_id'),
            ],
        ];

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return redirect()->back()->withErrors($validate)->withInput();
        }

        $obj = $request->only(['department_id', 'name', 'code', 'description', 'status']);
        $obj['business_id'] = Auth::user()->business_id;
        $obj['branch_id'] = Auth::user()->branch_id;
        $obj['status'] = $request->status ?? 'active';

        $this->department_service->save($obj);

        return redirect('admin/department')
            ->with('success', empty($request->department_id) ? Message::SAVE : Message::UPDATE);
    }

    public function edit($department_id)
    {
        $department = $this->department_service->getById($department_id);
        return view('admin.hrm.department.create', compact('department'));
    }

    public function destroy($department_id)
    {
        try {
            $this->department_service->delete($department_id);
            return $this->success(Message::DELETE, []);
        } catch (Exception $e) {
            return $this->error(Message::ERROR);
        }
    }
}
