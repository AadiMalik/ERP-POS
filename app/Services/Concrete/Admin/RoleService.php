<?php

namespace App\Services\Concrete\Admin;

use App\Enums\Filter;
use App\Repository\Repository;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;
use App\Enums\RoleNames;
use Exception;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Str;

class RoleService
{

    protected $model_role;
    public function __construct()
    {
        // set the model
        $this->model_role = new Repository(new Role);
    }

    public function getData($obj)
    {
        $wh = [];
        $orderBy = Filter::ORDERBY;
        $limit = Filter::LIMIT;
        $search = null;

        if (isset($obj['orderBy']) && $obj['orderBy'] != 0 && $obj['orderBy'] != "") {
            $orderBy = $obj['orderBy'];
        }
        if (isset($obj['limit']) && $obj['limit'] != 0 && $obj['limit'] != "") {
            $limit = $obj['limit'];
        }
        // for super admin
        if (isset($obj['business_id']) && $obj['business_id'] != 0 && $obj['business_id'] != "") {
            $wh[] = ['business_id', $obj['business_id']];
        }
        // for business roles or super admin
        if (isset($obj['branch_id']) && $obj['branch_id'] != 0 && $obj['branch_id'] != "") {
            $wh[] = ['branch_id', $obj['branch_id']];
        }

        if (isset($obj['from']) && $obj['from'] != 0 && $obj['from'] != "") {
            $wh[] = ['created_at', '>=', $obj['from'] . " 23:59:59"];
        }
        if (isset($obj['to']) && $obj['to'] != 0 && $obj['to'] != "") {
            $wh[] = ['created_at', '<=', $obj['to'] . " 23:59:59"];
        }
        if (isset($obj['search']) && $obj['search'] != '' && $obj['search'] != null && $obj['search'] != 'null') {
            $search = $obj['search'];
        }

        $request_data = [
            'first_name',
            'last_name',
            'phone_num',
            'created_at'
        ];
        $with = ['permissions'];


        $allow_roles = [
            RoleNames::SUPERADMIN,
            RoleNames::BUSINESSADMIN
        ];

        $datatable = $this->model_role->getModel()::where($wh)
            ->with($with)
            ->orderBy('created_at', $orderBy);
        $datatable = applyRoleScope($datatable, $allow_roles);
        $data = DataTables::of($datatable)
            ->addColumn('permissions', function ($item) {

                if ($item->permissions->isEmpty()) {
                    return '-';
                }

                $badges = '';

                foreach ($item->permissions as $permission) {

                    $badges .= '
                    <span class="badge bg-label-primary me-1 mb-1">
                        ' . $permission->name . '
                    </span>
                ';
                }

                return $badges;
            })
            ->editColumn('description', function ($item) {

                return Str::limit($item->description, 50, '...');
            })
            ->addColumn('action', function ($item) {
                $action_column = '';
                $edit_column    = "<a class='btn btn-icon btn-outline-primary mr-2' href='" . route('roles.edit', $item->id) . "' data-toggle='tooltip' data-original-title='Edit'><i title='Edit' class='icon-base bx bx-edit'></i></a>";
                $delete_column    = "<a class='btn btn-icon btn-outline-danger mr-2'  id='deleteRole' href='javascript:void(0)' data-toggle='tooltip'  data-id='" . $item->id . "' data-original-title='delete'><i title='Delete' class='icon-base bx bx-trash'></i></a>";


                // if (isset($menu_permission) && $menu_permission['can_edit'] == 1) {
                $action_column .= $edit_column;
                // }
                // if (isset($menu_permission) && $menu_permission['can_delete'] == 1) {
                $action_column .= $delete_column;
                // }


                return $action_column;
            })
            ->rawColumns(['permissions','description', 'action'])
            ->make(true);
        return $data;
    }

    public function getByid($id)
    {
        return $this->model_role->find($id);
    }
    public function save($obj)
    {
        if (isset($obj['id']) && $obj['id'] > 0) {
            $this->model_role->update($obj, $obj['id']);
            $saved_obj = $this->model_role->find($obj['id']);
        } else {
            $saved_obj = $this->model_role->create($obj);
        }

        if (!$saved_obj)
            return false;

        return $saved_obj;
    }
    public function resetBusinessRoles()
    {
        try {
            if (getRoleName() == RoleNames::SUPERADMIN) {

                $business_id = null;
                $roles = [
                    [
                        'name' => RoleNames::SUPERADMIN,
                        'description' => 'Full access to all businesses.',
                    ],
                    [
                        'name' => RoleNames::BUSINESSADMIN,
                        'description' => 'Full access to all business operations and all branch data including employees, inventory, finance, sales, purchases, reports, and settings.',
                    ],
                ];
            } else {

                $business_id = Auth::user()->business_id;
                $roles = [

                    [
                        'name' => RoleNames::BRANCHADMIN,
                        'description' => 'Can manage only assigned branch data including staff, sales, inventory, and branch operations.',
                    ],

                    [
                        'name' => RoleNames::GENERALMANAGER,
                        'description' => 'Can monitor and manage complete business performance and data of all branches.',
                    ],

                    [
                        'name' => RoleNames::OPERATIONMANAGER,
                        'description' => 'Can manage operational activities and monitor operations across the entire business.',
                    ],

                    [
                        'name' => RoleNames::INVENTORYMANAGER,
                        'description' => 'Can manage inventory, warehouses, stock transfers, and product availability for the complete business and all branches.',
                    ],

                    [
                        'name' => RoleNames::FINANCEMANAGER,
                        'description' => 'Can access and manage complete business financial records, expenses, payments, and accounting data of all branches.',
                    ],

                    [
                        'name' => RoleNames::SALEMANAGER,
                        'description' => 'Can monitor sales performance, customer orders, and sales reports for assigned branch or complete business based on access rights.',
                    ],

                    [
                        'name' => RoleNames::PURCHASEMANAGER,
                        'description' => 'Can manage suppliers, purchases, purchase orders, and procurement data for the complete business and all branches.',
                    ],

                    [
                        'name' => RoleNames::MARKITINGMANAGER,
                        'description' => 'Can manage marketing campaigns, promotions, and customer engagement data for the complete business.',
                    ],

                    [
                        'name' => RoleNames::ACCOUNTANT,
                        'description' => 'Can manage accounts, vouchers, invoices, and transaction records for assigned branch or complete business based on access rights.',
                    ],

                    [
                        'name' => RoleNames::HRMANAGER,
                        'description' => 'Can manage employees, attendance, payroll, leaves, and HR operations for the complete business.',
                    ],

                    [
                        'name' => RoleNames::REPORTINGANALYST,
                        'description' => 'Can view analytics, dashboards, KPIs, and reports of the complete business without modification rights.',
                    ],

                    [
                        'name' => RoleNames::STAFF,
                        'description' => 'Limited access to assigned branch operations and daily tasks only.',
                    ],

                    [
                        'name' => RoleNames::POSMANAGER,
                        'description' => 'Can manage POS counters, billing operations, and POS reports for assigned branch only.',
                    ],

                    [
                        'name' => RoleNames::ORDERTAKER,
                        'description' => 'Can create customer orders and process POS billing for assigned branch only.',
                    ],

                ];
            }

            foreach ($roles as $role) {

                $this->model_role->getModel()::updateOrCreate(

                    [
                        'business_id' => $business_id,
                        'name' => $role['name'],
                    ],

                    [
                        'description' => $role['description'],
                        'guard_name' => 'web',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }

            return true;
        } catch (Exception $e) {

            return false;
        }
    }
}
