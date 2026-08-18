<?php

namespace App\Services\Concrete\Admin\Hrm;

use App\Enums\Filter;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Repository\Repository;
use App\Traits\Auditable;
use App\Traits\Notifiable;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Yajra\DataTables\DataTables;

class LeaveRequestService
{
    use Auditable, Notifiable;

    protected $model_leave_request;

    public function __construct()
    {
        $this->model_leave_request = new Repository(new LeaveRequest());
    }

    public function getData($obj)
    {
        $wh = [];
        $orderBy = Filter::ORDERBY;

        if (isset($obj['employee_id']) && $obj['employee_id'] != "") {
            $wh[] = ['employee_id', $obj['employee_id']];
        }
        if (isset($obj['status']) && $obj['status'] != "") {
            $wh[] = ['status', $obj['status']];
        }

        $datatable = $this->model_leave_request->getModel()::where($wh)
            ->where('is_deleted', 0)
            ->with(['employee.user', 'leaveType'])
            ->orderBy('date_created', 'desc');
        $datatable = applyRoleScope($datatable);

        return DataTables::of($datatable)
            ->addColumn('employee', function ($item) {
                return $item->employee?->user?->name ?? '-';
            })
            ->addColumn('leave_type', function ($item) {
                return $item->leaveType?->name ?? '-';
            })
            ->addColumn('status', function ($item) {
                $map = ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger', 'cancelled' => 'secondary'];
                $color = $map[$item->status] ?? 'secondary';
                return '<span class="badge bg-label-' . $color . '">' . ucfirst($item->status) . '</span>';
            })
            ->addColumn('action', function ($item) {
                $actions = '';
                if ($item->status == 'pending') {
                    $actions .= "<a class='btn btn-icon btn-outline-success mr-2' id='approveLeaveRequest' data-id='{$item->leave_request_id}'><i class='fa fa-check'></i></a>";
                    $actions .= "<a class='btn btn-icon btn-outline-danger mr-2' id='rejectLeaveRequest' data-id='{$item->leave_request_id}'><i class='fa fa-times'></i></a>";
                }
                $actions .= "<a class='btn btn-icon btn-outline-secondary' id='deleteLeaveRequest' data-id='{$item->leave_request_id}'><i class='fa fa-trash'></i></a>";
                return $actions;
            })
            ->rawColumns(['status', 'action'])
            ->make(true);
    }

    public function save($obj)
    {
        $employee = Employee::findOrFail($obj['employee_id']);

        $start = Carbon::parse($obj['start_date']);
        $end = Carbon::parse($obj['end_date']);
        if ($end->lt($start)) {
            throw new Exception('End date cannot be before start date.');
        }
        $days_count = $start->diffInDays($end) + 1;

        $data = [
            'employee_id' => $obj['employee_id'],
            'leave_type_id' => $obj['leave_type_id'],
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'days_count' => $days_count,
            'reason' => $obj['reason'] ?? null,
            'business_id' => $employee->business_id,
            'branch_id' => $employee->branch_id,
        ];

        if (!empty($obj['attachment'])) {
            $data['attachment'] = $this->storeAttachment($obj['attachment']);
        }

        $data['leave_request_id'] = generateUuid();
        $data['status'] = 'pending';
        $data['createdby_id'] = Auth::id();
        $data['date_created'] = now();

        $leave_request = $this->model_leave_request->create($data);

        $this->logActivity('leave-request', $leave_request->leave_request_id, 'submitted', null, $data, null, $employee->business_id, $employee->branch_id);
        $this->notify('leave_request', $employee->business_id, $employee->branch_id, 'New Leave Request', ($employee->user->name ?? 'An employee') . ' applied for leave.', 'leave-request', $leave_request->leave_request_id, route('leave-request.index'));

        return $leave_request;
    }

    protected function storeAttachment($file)
    {
        $fileName = time() . '_' . $file->getClientOriginalName();
        $path = public_path('uploads/leave-request');
        if (!File::exists($path)) {
            File::makeDirectory($path, 0755, true);
        }
        $file->move($path, $fileName);
        return 'uploads/leave-request/' . $fileName;
    }

    public function getById($leave_request_id)
    {
        return $this->model_leave_request->getModel()::with(['employee.user', 'leaveType'])->findOrFail($leave_request_id);
    }

    public function decide($leave_request_id, $status, $remarks = null)
    {
        if (!in_array($status, ['approved', 'rejected'], true)) {
            throw new Exception('Invalid decision.');
        }

        $leave_request = $this->getById($leave_request_id);
        if ($leave_request->status != 'pending') {
            throw new Exception('This leave request has already been decided.');
        }

        $this->model_leave_request->update([
            'status' => $status,
            'approver_id' => Auth::id(),
            'approved_at' => now(),
            'remarks' => $remarks,
            'updatedby_id' => Auth::id(),
            'date_updated' => now(),
        ], $leave_request_id);

        if ($status == 'approved') {
            $this->markAttendanceOnLeave($leave_request);
        }

        $this->logActivity('leave-request', $leave_request_id, $status, ['status' => 'pending'], ['status' => $status, 'remarks' => $remarks]);
    }

    protected function markAttendanceOnLeave(LeaveRequest $leave_request)
    {
        $period = CarbonPeriod::create($leave_request->start_date, $leave_request->end_date);

        foreach ($period as $date) {
            $exists = Attendance::where('employee_id', $leave_request->employee_id)
                ->where('date', $date->toDateString())
                ->exists();

            if (!$exists) {
                Attendance::create([
                    'attendance_id' => generateUuid(),
                    'employee_id' => $leave_request->employee_id,
                    'date' => $date->toDateString(),
                    'status' => 'on_leave',
                    'source' => 'manual',
                    'business_id' => $leave_request->business_id,
                    'branch_id' => $leave_request->branch_id,
                    'createdby_id' => Auth::id(),
                    'date_created' => now(),
                ]);
            }
        }
    }

    public function cancel($leave_request_id)
    {
        $leave_request = $this->getById($leave_request_id);
        if ($leave_request->status != 'pending') {
            throw new Exception('Only pending leave requests can be cancelled.');
        }

        $this->model_leave_request->update([
            'status' => 'cancelled',
            'updatedby_id' => Auth::id(),
            'date_updated' => now(),
        ], $leave_request_id);
    }

    public function delete($leave_request_id)
    {
        return $this->model_leave_request->update([
            'is_deleted' => 1,
            'deletedby_id' => Auth::id(),
            'date_deleted' => now(),
        ], $leave_request_id);
    }

    /**
     * Remaining leave days for one employee/type/year - entitlement minus
     * approved days already taken. No carry-forward (kept simple).
     */
    public function getBalance($employee_id, $leave_type_id, $year = null)
    {
        $year = $year ?? now()->year;
        $leave_type = LeaveType::find($leave_type_id);
        $entitled = $leave_type->max_days_per_year ?? 0;

        $used = $this->model_leave_request->getModel()::where('employee_id', $employee_id)
            ->where('leave_type_id', $leave_type_id)
            ->where('status', 'approved')
            ->where('is_deleted', 0)
            ->whereYear('start_date', $year)
            ->sum('days_count');

        return [
            'entitled' => $entitled,
            'used' => (int) $used,
            'remaining' => max(0, $entitled - $used),
        ];
    }

    public function getByEmployee($employee_id)
    {
        return $this->model_leave_request->getModel()::with(['leaveType'])
            ->where('employee_id', $employee_id)
            ->where('is_deleted', 0)
            ->orderBy('date_created', 'desc')
            ->get();
    }

    /**
     * Approved leave days within one calendar month, split by whether the
     * leave type is paid - consumed by PayrollService::generate().
     */
    public function approvedDaysInMonth($employee_id, $year, $month)
    {
        $rows = $this->model_leave_request->getModel()::with('leaveType')
            ->where('employee_id', $employee_id)
            ->where('status', 'approved')
            ->where('is_deleted', 0)
            ->whereYear('start_date', $year)
            ->whereMonth('start_date', $month)
            ->get();

        $paid_days = 0;
        $unpaid_days = 0;

        foreach ($rows as $row) {
            if ($row->leaveType && !$row->leaveType->is_paid) {
                $unpaid_days += $row->days_count;
            } else {
                $paid_days += $row->days_count;
            }
        }

        return ['paid_days' => $paid_days, 'unpaid_days' => $unpaid_days];
    }
}
