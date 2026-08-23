<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Enums\Message;
use App\Http\Controllers\Concerns\HandlesImportExport;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\Hrm\ShiftService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ShiftController extends Controller
{
    use ResponseAPI;
    use HandlesImportExport;

    protected $shift_service;

    public function __construct(ShiftService $shift_service)
    {
        $this->middleware('permission:shift.view')->only(['index', 'getData']);
        $this->middleware('permission:shift.create')->only(['create']);
        $this->middleware('permission:shift.create|shift.edit')->only(['store']);
        $this->middleware('permission:shift.edit')->only(['edit']);
        $this->middleware('permission:shift.delete')->only(['destroy']);
        $this->middleware('permission:shift.import')->only(['importSample', 'importPreview', 'importConfirm']);
        $this->middleware('permission:shift.export')->only(['export']);

        $this->shift_service = $shift_service;
    }

    protected function importExportModuleKey(): string
    {
        return 'shift';
    }

    public function index()
    {
        return view('admin.hrm.shift.index');
    }

    public function getData(Request $request)
    {
        return $this->shift_service->getData($request->all());
    }

    public function create()
    {
        return view('admin.hrm.shift.create');
    }

    public function store(Request $request)
    {
        $rules = [
            'name' => [
                'required',
                Rule::unique('shifts', 'name')
                    ->where(function ($query) use ($request) {
                        return $query->where('business_id', Auth::user()->business_id)
                            ->where('is_deleted', 0);
                    })
                    ->ignore($request->shift_id, 'shift_id'),
            ],
            'start_time' => 'required',
            'end_time'   => 'required',
            'working_days' => 'required|array|min:1',
        ];

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            if ($request->ajax()) {
                return $this->validationResponse($validate->errors()->first());
            }
            return redirect()->back()->withErrors($validate)->withInput();
        }

        $obj = $request->only([
            'shift_id', 'name', 'start_time', 'end_time',
            'break_duration_minutes', 'grace_period_minutes', 'status',
        ]);
        $obj['working_days'] = $request->working_days;
        $obj['business_id'] = Auth::user()->business_id;
        $obj['branch_id'] = Auth::user()->branch_id;
        $obj['status'] = $request->status ?? 'active';
        $obj['break_duration_minutes'] = $request->break_duration_minutes ?? 0;
        $obj['grace_period_minutes'] = $request->grace_period_minutes ?? 0;

        $shift = $this->shift_service->save($obj);

        if ($request->ajax()) {
            return $this->success(
                empty($request->shift_id) ? Message::SAVE : Message::UPDATE,
                $shift
            );
        }

        return redirect('admin/shift')
            ->with('success', empty($request->shift_id) ? Message::SAVE : Message::UPDATE);
    }

    public function edit($shift_id)
    {
        $shift = $this->shift_service->getById($shift_id);
        return view('admin.hrm.shift.create', compact('shift'));
    }

    public function destroy($shift_id)
    {
        try {
            $this->shift_service->delete($shift_id);
            return $this->success(Message::DELETE, []);
        } catch (Exception $e) {
            return $this->error(Message::ERROR);
        }
    }
}
