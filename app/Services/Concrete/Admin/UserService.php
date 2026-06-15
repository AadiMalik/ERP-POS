<?php

namespace App\Services\Concrete\Admin;

use App\Enums\Filter;
use App\Repository\Repository;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;
use App\Enums\RoleNames;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Testing\Fluent\Concerns\Has;

class UserService
{

    protected $model_user;
    public function __construct()
    {
        // set the model
        $this->model_user = new Repository(new User());
    }

    public function getData($obj)
    {
        $wh = [];
        $role_id = null;
        if (isset($obj['business_id']) && $obj['business_id'] != 0 && $obj['business_id'] != "") {
            $wh[] = ['business_id', $obj['business_id']];
        }
        if (isset($obj['branch_id']) && $obj['branch_id'] != 0 && $obj['branch_id'] != "") {
            $wh[] = ['branch_id', $obj['branch_id']];
        }
        if (!empty($obj['start_date'])) {
            $wh[] = ['date_created', '>=', Carbon::parse($obj['start_date'])->startOfDay()];
        }

        if (!empty($obj['end_date'])) {
            $wh[] = ['date_created', '<=', Carbon::parse($obj['end_date'])->endOfDay()];
        }
        if (isset($obj['role_id']) && $obj['role_id'] != 0 && $obj['role_id'] != "") {
            $role_id = $obj['role_id'];
        }
        $datatable = $this->model_user->getModel()::with([
            'business',
            'branch',
            'roles'
        ])->where($wh)
            ->where('is_deleted', 0);
        if ($role_id) {
            $datatable->whereHas('roles', function ($q) use ($role_id) {
                $q->where('roles.id', $role_id);
            });
        }
        $datatable = applyRoleScope($datatable);

        return DataTables::of($datatable)

            ->addColumn('business', function ($item) {
                return $item->business?->name ?? '-';
            })

            ->addColumn('branch', function ($item) {
                return $item->branch?->name ?? '-';
            })

            ->addColumn('role', function ($item) {
                return $item->roles[0]?->name ?? '-';
            })

            ->addColumn('status', function ($item) {

                $checked = $item->status == 'active' ? 'checked' : '';

                return '
                    <div class="form-check form-switch mb-0">
                        <input
                            class="form-check-input statusUser"
                            type="checkbox"
                            data-id="' . $item->id . '"
                            ' . $checked . '>
                    </div>
                ';
            })

            ->addColumn('action', function ($item) {

                return "

                <a class='btn btn-icon btn-outline-primary'
                    href='" . route('users.edit', $item->id) . "'>
                    <i class='fa fa-pencil'></i>
                </a>

                <a class='btn btn-icon btn-outline-warning'
                    href='" . url('admin/users/change-password') . "/" . $item->id . "'>
                    <i class='fa fa-key'></i>
                </a>

                <a class='btn btn-icon btn-outline-danger'
                    id='deleteUser'
                    data-id='{$item->id}'>
                    <i class='fa fa-trash'></i>
                </a>
            ";
            })

            ->rawColumns([
                'business',
                'branch',
                'role',
                'status',
                'action'
            ])
            ->make(true);
    }

    public function getByid($id)
    {
        return $this->model_user->find($id);
    }
    public function save($obj)
    {
        DB::beginTransaction();

        try {

            if (!empty($obj['id'])) {
                $obj['updatedby_id'] = Auth::id();
                $obj['date_updated'] = now();
                $this->model_user->update($obj, $obj['id']);
                $saved_obj = $this->model_user->find($obj['id']);
            } else {

                $obj['password'] = Hash::make($obj['password']);
                $obj['createdby_id'] = Auth::id();
                $obj['date_created'] = now();
                $saved_obj = $this->model_user->create($obj);
            }

            if (!$saved_obj) {
                DB::rollBack();
                return false;
            }

            // Assign Role
            if (!empty($obj['role_id'])) {

                $role = Role::find($obj['role_id']);

                if ($role) {

                    // Old role remove + new assign
                    $saved_obj->syncRoles([$role->name]);
                }
            }

            DB::commit();

            return $saved_obj;
        } catch (Exception $e) {

            DB::rollBack();
            throw $e;
        }
    }
    public function changePassword($obj)
    {
        return $this->model_user->update([
            'password' => Hash::make($obj['password']),
            'updatedby_id' => Auth::id(),
            'date_updated' => now()
        ], $obj['id']);
    }
    public function status($id)
    {
        return $this->model_user->update([
            'status' => ($this->model_user->find($id)->status == 'active' ? 'inactive' : 'active'),
            'updatedby_id' => Auth::id(),
            'date_updated' => now()
        ], $id);
    }
    public function delete($id)
    {
        return $this->model_user->update([
            'is_deleted' => 1,
            'deletedby_id' => Auth::id(),
            'date_deleted' => now()
        ], $id);
    }
}
