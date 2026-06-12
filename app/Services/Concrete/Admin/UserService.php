<?php

namespace App\Services\Concrete\Admin;

use App\Enums\Filter;
use App\Repository\Repository;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;
use App\Enums\RoleNames;
use App\Models\User;
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

    public function getData($data)
    {
        $datatable = $this->model_user->getModel()::with([
            'business',
            'branch'
        ])->where('is_deleted', 0);

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
                    href='" . route('users-password', $item->id) . "'>
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
        if (isset($obj['id']) && $obj['id'] > 0) {
            $this->model_user->update($obj, $obj['id']);
            $saved_obj = $this->model_user->find($obj['id']);
        } else {
            $obj['password'] = Hash::make($obj['password']);
            $saved_obj = $this->model_user->create($obj);
        }

        if (!$saved_obj)
            return false;

        return $saved_obj;
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
