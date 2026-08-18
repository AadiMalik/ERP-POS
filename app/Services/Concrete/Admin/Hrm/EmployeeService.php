<?php

namespace App\Services\Concrete\Admin\Hrm;

use App\Enums\EmployeeStatus;
use App\Enums\Filter;
use App\Enums\RoleNames;
use App\Enums\Status;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\User;
use App\Repository\Repository;
use App\Traits\Auditable;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Yajra\DataTables\DataTables;

class EmployeeService
{
    use Auditable;

    protected $model_employee;
    protected $model_document;

    public function __construct()
    {
        $this->model_employee = new Repository(new Employee());
        $this->model_document = new Repository(new EmployeeDocument());
    }

    public function getData($obj)
    {
        $wh = [];
        $orderBy = Filter::ORDERBY;

        if (isset($obj['orderBy']) && $obj['orderBy'] != 0 && $obj['orderBy'] != "") {
            $orderBy = $obj['orderBy'];
        }
        if (isset($obj['department_id']) && $obj['department_id'] != 0 && $obj['department_id'] != "") {
            $wh[] = ['department_id', $obj['department_id']];
        }
        if (isset($obj['branch_id']) && $obj['branch_id'] != 0 && $obj['branch_id'] != "") {
            $wh[] = ['branch_id', $obj['branch_id']];
        }
        if (isset($obj['status']) && $obj['status'] != "") {
            $wh[] = ['status', $obj['status']];
        }

        $datatable = $this->model_employee->getModel()::where($wh)
            ->where('is_deleted', 0)
            ->with(['user', 'department', 'designation', 'branch'])
            ->orderBy('date_created', 'desc');
        $datatable = applyRoleScope($datatable);

        return DataTables::of($datatable)
            ->addColumn('name', function ($item) {
                return $item->user?->name ?? '-';
            })
            ->addColumn('email', function ($item) {
                return $item->user?->email ?? '-';
            })
            ->addColumn('department', function ($item) {
                return $item->department?->name ?? '-';
            })
            ->addColumn('designation', function ($item) {
                return $item->designation?->name ?? '-';
            })
            ->addColumn('branch', function ($item) {
                return $item->branch?->name ?? '-';
            })
            ->addColumn('status', function ($item) {
                $map = [
                    EmployeeStatus::ACTIVE => 'success',
                    EmployeeStatus::ON_LEAVE => 'info',
                    EmployeeStatus::SUSPENDED => 'warning',
                    EmployeeStatus::RESIGNED => 'secondary',
                    EmployeeStatus::TERMINATED => 'danger',
                ];
                $color = $map[$item->status] ?? 'secondary';
                $badge = '<span class="badge bg-label-' . $color . '">' . ucfirst(str_replace('_', ' ', $item->status)) . '</span>';

                if (!in_array($item->status, EmployeeStatus::manuallySettable(), true)) {
                    return $badge;
                }

                $options = '';
                foreach (EmployeeStatus::manuallySettable() as $status) {
                    $options .= '<option value="' . $status . '" ' . ($item->status == $status ? 'selected' : '') . '>' . ucfirst(str_replace('_', ' ', $status)) . '</option>';
                }

                return '
                    <select class="form-select form-select-sm employeeStatusSelect" data-id="' . $item->employee_id . '" style="min-width:130px;">
                        ' . $options . '
                    </select>
                ';
            })
            ->addColumn('action', function ($item) {
                return "
                    <a class='btn btn-icon btn-outline-primary mr-2'
                     href='" . route('employee.edit', $item->employee_id) . "'>
                    <i class='fa fa-pencil'></i>
                    </a>

                    <a class='btn btn-icon btn-outline-danger'
                    id='deleteEmployee'
                    data-id='{$item->employee_id}'>
                    <i class='fa fa-trash'></i>
                    </a>
                ";
            })
            ->rawColumns(['status', 'action'])
            ->make(true);
    }

    /**
     * Creates the linked User (role Employee, auto-generated password,
     * must_change_password=true) and the Employee profile row together, or
     * updates an existing pair. Returns ['employee' => Employee, 'password' =>
     * string|null] - password is only present right after a create, so the
     * controller can flash it once for HR to hand to the employee.
     */
    public function save($obj)
    {
        DB::beginTransaction();

        try {
            $business_id = $obj['business_id'] ?? Auth::user()->business_id;
            $branch_id = $obj['branch_id'] ?? Auth::user()->branch_id;
            $plain_password = null;

            if (!empty($obj['employee_id'])) {
                $employee = $this->model_employee->find($obj['employee_id']);

                User::where('id', $employee->user_id)->update([
                    'name' => $obj['name'],
                    'phone' => $obj['phone'] ?? null,
                    'updatedby_id' => Auth::id(),
                    'date_updated' => now(),
                ]);

                $employee_obj = $this->employeeFields($obj);
                $employee_obj['updatedby_id'] = Auth::id();
                $employee_obj['date_updated'] = now();
                $this->model_employee->update($employee_obj, $obj['employee_id']);

                $this->logActivity('employee', $obj['employee_id'], 'updated', null, $employee_obj, null, $business_id, $branch_id);

                $employee = $this->model_employee->find($obj['employee_id']);
            } else {
                $plain_password = Str::random(10);

                $user = User::create([
                    'name' => $obj['name'],
                    'email' => $obj['email'],
                    'phone' => $obj['phone'] ?? null,
                    'password' => Hash::make($plain_password),
                    'status' => Status::ACTIVE,
                    'must_change_password' => true,
                    'business_id' => $business_id,
                    'branch_id' => $branch_id,
                    'createdby_id' => Auth::id(),
                    'date_created' => now(),
                ]);
                $user->syncRoles([RoleNames::EMPLOYEE]);

                $employee_obj = $this->employeeFields($obj);
                $employee_obj['employee_id'] = generateUuid();
                $employee_obj['user_id'] = $user->id;
                $employee_obj['employee_code'] = ($obj['employee_code'] ?? null) ?: $this->generateEmployeeCode($business_id);
                $employee_obj['business_id'] = $business_id;
                $employee_obj['branch_id'] = $branch_id;
                $employee_obj['status'] = EmployeeStatus::ACTIVE;
                $employee_obj['createdby_id'] = Auth::id();
                $employee_obj['date_created'] = now();

                $employee = $this->model_employee->create($employee_obj);

                $this->logActivity('employee', $employee->employee_id, 'created', null, $employee_obj, null, $business_id, $branch_id);
            }

            DB::commit();

            return ['employee' => $employee, 'password' => $plain_password];
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    protected function employeeFields($obj)
    {
        return array_filter([
            'department_id' => $obj['department_id'] ?? null,
            'designation_id' => $obj['designation_id'] ?? null,
            'shift_id' => $obj['shift_id'] ?? null,
            'joining_date' => $obj['joining_date'] ?? null,
            'employment_type' => $obj['employment_type'] ?? 'full_time',
            'dob' => $obj['dob'] ?? null,
            'gender' => $obj['gender'] ?? null,
            'marital_status' => $obj['marital_status'] ?? null,
            'national_id' => $obj['national_id'] ?? null,
            'address' => $obj['address'] ?? null,
            'emergency_contact_name' => $obj['emergency_contact_name'] ?? null,
            'emergency_contact_phone' => $obj['emergency_contact_phone'] ?? null,
            'emergency_contact_relation' => $obj['emergency_contact_relation'] ?? null,
            'bank_name' => $obj['bank_name'] ?? null,
            'bank_account_title' => $obj['bank_account_title'] ?? null,
            'bank_account_number' => $obj['bank_account_number'] ?? null,
            'bank_branch_code' => $obj['bank_branch_code'] ?? null,
            'payment_method' => $obj['payment_method'] ?? 'bank',
        ], fn ($v) => $v !== null);
    }

    protected function generateEmployeeCode($business_id)
    {
        $count = $this->model_employee->getModel()::where('business_id', $business_id)->count() + 1;
        $code = 'EMP-' . str_pad($count, 4, '0', STR_PAD_LEFT);

        while ($this->model_employee->getModel()::where('business_id', $business_id)->where('employee_code', $code)->exists()) {
            $count++;
            $code = 'EMP-' . str_pad($count, 4, '0', STR_PAD_LEFT);
        }

        return $code;
    }

    public function getById($employee_id)
    {
        return $this->model_employee->getModel()::with(['user', 'department', 'designation', 'shift', 'documents'])
            ->findOrFail($employee_id);
    }

    public function changeStatus($employee_id, $status)
    {
        if (!in_array($status, EmployeeStatus::manuallySettable(), true)) {
            throw new Exception('Invalid status.');
        }

        $employee = $this->model_employee->find($employee_id);

        $this->model_employee->update([
            'status' => $status,
            'updatedby_id' => Auth::id(),
            'date_updated' => now(),
        ], $employee_id);

        User::where('id', $employee->user_id)->update([
            'status' => $status === EmployeeStatus::SUSPENDED ? Status::INACTIVE : Status::ACTIVE,
        ]);

        $this->logActivity('employee', $employee_id, 'status_changed', ['status' => $employee->status], ['status' => $status]);
    }

    public function delete($employee_id)
    {
        $employee = $this->model_employee->find($employee_id);

        $this->model_employee->update([
            'is_deleted' => 1,
            'deletedby_id' => Auth::id(),
            'date_deleted' => now(),
        ], $employee_id);

        User::where('id', $employee->user_id)->update(['status' => Status::INACTIVE]);
    }

    public function getAllActive($business_id = null)
    {
        $query = $this->model_employee->getModel()::with(['user', 'department', 'designation'])
            ->where('is_deleted', 0)
            ->whereIn('status', [EmployeeStatus::ACTIVE, EmployeeStatus::ON_LEAVE]);

        if ($business_id) {
            $query->where('business_id', $business_id);
        } else {
            $query = applyRoleScope($query);
        }

        return $query->get();
    }

    public function findByUserId($user_id)
    {
        return $this->model_employee->getModel()::where('user_id', $user_id)->where('is_deleted', 0)->first();
    }

    public function uploadDocument($employee_id, $document_type, $file, $expiry_date = null, $notes = null)
    {
        $employee = $this->model_employee->find($employee_id);

        $fileName = time() . '_' . $file->getClientOriginalName();
        $path = public_path('uploads/employee-document');
        if (!File::exists($path)) {
            File::makeDirectory($path, 0755, true);
        }
        $file->move($path, $fileName);

        return $this->model_document->create([
            'employee_document_id' => generateUuid(),
            'employee_id' => $employee_id,
            'document_type' => $document_type,
            'file_name' => $fileName,
            'file_path' => 'uploads/employee-document/' . $fileName,
            'expiry_date' => $expiry_date,
            'notes' => $notes,
            'business_id' => $employee->business_id,
            'createdby_id' => Auth::id(),
            'date_created' => now(),
        ]);
    }

    public function deleteDocument($employee_document_id)
    {
        return $this->model_document->update([
            'is_deleted' => 1,
            'deletedby_id' => Auth::id(),
            'date_deleted' => now(),
        ], $employee_document_id);
    }
}
