<?php

namespace App\Services\Concrete\Admin;

use App\Enums\Filter;
use App\Repository\Repository;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;
use App\Enums\RoleNames;
use App\Enums\Status;
use App\Models\Role;
use App\Models\User;
use App\Traits\Auditable;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Testing\Fluent\Concerns\Has;

class UserService
{
    use Auditable;

    protected $model_user;
    protected $customer_service;

    public function __construct(CustomerService $customer_service)
    {
        // set the model
        $this->model_user = new Repository(new User());
        $this->customer_service = $customer_service;
    }

    public function getData($obj)
    {
        $wh = [];
        $role_id = null;
        $business_id_filter = null;

        if (isset($obj['business_id']) && $obj['business_id'] != 0 && $obj['business_id'] != "") {
            $business_id_filter = $obj['business_id'];
        }
        if (isset($obj['branch_id']) && $obj['branch_id'] != 0 && $obj['branch_id'] != "") {
            $wh[] = ['branch_id', $obj['branch_id']];
        }
        if (!empty($obj['start_date'])) {
            $wh[] = ['date_created', '>=', businessStartOfDay($obj['start_date'])];
        }

        if (!empty($obj['end_date'])) {
            $wh[] = ['date_created', '<=', businessEndOfDay($obj['end_date'])];
        }
        if (isset($obj['role_id']) && $obj['role_id'] != 0 && $obj['role_id'] != "") {
            $role_id = $obj['role_id'];
        }

        $datatable = $this->model_user->getModel()::with([
            'business',
            'branch',
            'roles'
        ])->where($wh)
            ->where('is_deleted', 0)
            // Customers are managed exclusively through the dedicated
            // Customer module now (CustomerController) - never listed here.
            ->whereDoesntHave('roles', function ($q) {
                $q->where('name', RoleNames::USER);
            });

        if ($role_id) {
            $datatable->whereHas('roles', function ($q) use ($role_id) {
                $q->where('roles.id', $role_id);
            });
        }

        if ($business_id_filter) {
            $datatable->where('business_id', $business_id_filter);
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

                $checked = $item->status == Status::ACTIVE ? 'checked' : '';

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

                $login_as = '';
                if (getRoleName() === RoleNames::SUPERADMIN && $item->id !== Auth::id() && Auth::user()->can('user.login-as')) {
                    $login_as = "
                    <a class='btn btn-icon btn-outline-info'
                        title='" . __('users.login_as') . "'
                        href='" . route('users.login-as', $item->id) . "'>
                        <i class='fa fa-sign-in'></i>
                    </a>";
                }

                return "

                <a class='btn btn-icon btn-outline-primary'
                    href='" . route('users.edit', $item->id) . "'>
                    <i class='fa fa-pencil'></i>
                </a>

                <a class='btn btn-icon btn-outline-warning'
                    href='" . url('admin/users/change-password') . "/" . $item->id . "'>
                    <i class='fa fa-key'></i>
                </a>

                {$login_as}

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

    /**
     * Switches the current session to the target user without knowing their
     * password (Super Admin only, enforced by the user.login-as permission
     * and the caller check below). The original Super Admin id is stashed in
     * the session so returnToSuperAdmin() can restore it later.
     */
    public function loginAs($id)
    {
        $target = $this->model_user->find($id);
        $impersonator_id = Auth::id();

        if ((int) $target->id === (int) $impersonator_id) {
            throw new Exception('You cannot login as yourself.');
        }

        $this->logActivity(
            'user',
            (string) $target->id,
            'login_as',
            null,
            ['target_user_id' => $target->id, 'target_email' => $target->email],
            'Super Admin logged in as user',
            Auth::user()->business_id
        );

        Auth::login($target);
        session(['impersonator_id' => $impersonator_id]);
    }

    public function returnToSuperAdmin()
    {
        $impersonator_id = session()->pull('impersonator_id');

        if (!$impersonator_id) {
            return false;
        }

        $super_admin = $this->model_user->find($impersonator_id);
        Auth::login($super_admin);

        return true;
    }

    public function save($obj)
    {
        DB::beginTransaction();

        try {

            $role = !empty($obj['role_id']) ? Role::find($obj['role_id']) : null;
            $is_customer = $role && $role->name === RoleNames::USER;

            // Customers reuse an existing global account for their email
            // instead of creating a duplicate one - the same rule the OTP
            // onboarding flow (AuthController) follows - so the same person
            // never ends up with two User rows just because a second
            // business added them as a customer.
            $existing_customer = ($is_customer && empty($obj['id']) && !empty($obj['email']))
                ? User::whereRaw('LOWER(email) = ?', [strtolower(trim($obj['email']))])->first()
                : null;

            if ($existing_customer) {
                $saved_obj = $existing_customer;
            } elseif (!empty($obj['id'])) {
                $obj['business_id'] = $obj['business_id']??Auth::user()->business_id;
                $obj['updatedby_id'] = Auth::id();
                $obj['date_updated'] = now();
                $this->model_user->update($obj, $obj['id']);
                $saved_obj = $this->model_user->find($obj['id']);
            } else {

                $obj['password'] = !empty($obj['password']) ? Hash::make($obj['password']) : null;
                $obj['business_id'] = $obj['business_id']??Auth::user()->business_id;
                $obj['createdby_id'] = Auth::id();
                $obj['date_created'] = now();
                $saved_obj = $this->model_user->create($obj);
            }

            if (!$saved_obj) {
                DB::rollBack();
                return false;
            }

            // Assign Role
            if ($role) {

                // Old role remove + new assign
                $saved_obj->syncRoles([$role->name]);

                // Customer accounts carry a business-scoped commercial
                // profile (credit terms, address, ...) alongside the
                // shared User identity - persisted here, not on a
                // separate Customer screen.
                if ($is_customer) {
                    $profile_business_id = $obj['business_id'] ?? $saved_obj->business_id;
                    $this->customer_service->upsertProfile($saved_obj->id, $profile_business_id, $obj);
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
            'must_change_password' => false,
            'updatedby_id' => Auth::id(),
            'date_updated' => now()
        ], $obj['id']);
    }
    public function status($id)
    {
        return $this->model_user->update([
            'status' => ($this->model_user->find($id)->status == Status::ACTIVE ? Status::INACTIVE : Status::ACTIVE),
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
