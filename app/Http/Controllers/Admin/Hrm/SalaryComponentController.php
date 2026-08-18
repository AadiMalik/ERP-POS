<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\Hrm\SalaryComponentService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class SalaryComponentController extends Controller
{
    use ResponseAPI;

    protected $salary_component_service;

    public function __construct(SalaryComponentService $salary_component_service)
    {
        $this->middleware('permission:salary-component.view')->only(['index', 'getData']);
        $this->middleware('permission:salary-component.create')->only(['create']);
        $this->middleware('permission:salary-component.create|salary-component.edit')->only(['store']);
        $this->middleware('permission:salary-component.edit')->only(['edit']);
        $this->middleware('permission:salary-component.delete')->only(['destroy']);

        $this->salary_component_service = $salary_component_service;
    }

    public function index()
    {
        return view('admin.hrm.salary-component.index');
    }

    public function getData(Request $request)
    {
        return $this->salary_component_service->getData($request->all());
    }

    public function create()
    {
        return view('admin.hrm.salary-component.create');
    }

    public function store(Request $request)
    {
        $rules = [
            'name' => [
                'required',
                Rule::unique('salary_components', 'name')
                    ->where(function ($query) use ($request) {
                        return $query->where('business_id', Auth::user()->business_id)
                            ->where('is_deleted', 0);
                    })
                    ->ignore($request->salary_component_id, 'salary_component_id'),
            ],
            'type' => 'required|in:earning,deduction',
            'calculation_type' => 'required|in:fixed,percentage_of_basic',
        ];

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return redirect()->back()->withErrors($validate)->withInput();
        }

        $obj = $request->only(['salary_component_id', 'name', 'code', 'type', 'calculation_type', 'status']);
        $obj['business_id'] = Auth::user()->business_id;
        $obj['status'] = $request->status ?? 'active';

        $this->salary_component_service->save($obj);

        return redirect('admin/salary-component')
            ->with('success', empty($request->salary_component_id) ? Message::SAVE : Message::UPDATE);
    }

    public function edit($salary_component_id)
    {
        $salary_component = $this->salary_component_service->getById($salary_component_id);
        return view('admin.hrm.salary-component.create', compact('salary_component'));
    }

    public function destroy($salary_component_id)
    {
        try {
            $this->salary_component_service->delete($salary_component_id);
            return $this->success(Message::DELETE, []);
        } catch (Exception $e) {
            return $this->error(Message::ERROR);
        }
    }
}
