<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\Hrm\LeaveTypeService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class LeaveTypeController extends Controller
{
    use ResponseAPI;

    protected $leave_type_service;

    public function __construct(LeaveTypeService $leave_type_service)
    {
        $this->middleware('permission:leave-type.view')->only(['index', 'getData']);
        $this->middleware('permission:leave-type.create')->only(['create']);
        $this->middleware('permission:leave-type.create|leave-type.edit')->only(['store']);
        $this->middleware('permission:leave-type.edit')->only(['edit']);
        $this->middleware('permission:leave-type.delete')->only(['destroy']);

        $this->leave_type_service = $leave_type_service;
    }

    public function index()
    {
        return view('admin.hrm.leave-type.index');
    }

    public function getData(Request $request)
    {
        return $this->leave_type_service->getData($request->all());
    }

    public function create()
    {
        return view('admin.hrm.leave-type.create');
    }

    public function store(Request $request)
    {
        $rules = [
            'name' => [
                'required',
                Rule::unique('leave_types', 'name')
                    ->where(function ($query) use ($request) {
                        return $query->where('business_id', Auth::user()->business_id)
                            ->where('is_deleted', 0);
                    })
                    ->ignore($request->leave_type_id, 'leave_type_id'),
            ],
            'max_days_per_year' => 'required|integer|min:0',
        ];

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return redirect()->back()->withErrors($validate)->withInput();
        }

        $obj = $request->only(['leave_type_id', 'name', 'code', 'max_days_per_year', 'status']);
        $obj['is_paid'] = $request->has('is_paid') ? 1 : 0;
        $obj['business_id'] = Auth::user()->business_id;
        $obj['status'] = $request->status ?? 'active';

        $this->leave_type_service->save($obj);

        return redirect('admin/leave-type')
            ->with('success', empty($request->leave_type_id) ? Message::SAVE : Message::UPDATE);
    }

    public function edit($leave_type_id)
    {
        $leave_type = $this->leave_type_service->getById($leave_type_id);
        return view('admin.hrm.leave-type.create', compact('leave_type'));
    }

    public function destroy($leave_type_id)
    {
        try {
            $this->leave_type_service->delete($leave_type_id);
            return $this->success(Message::DELETE, []);
        } catch (Exception $e) {
            return $this->error(Message::ERROR);
        }
    }
}
