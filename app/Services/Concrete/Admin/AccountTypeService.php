<?php

namespace App\Services\Concrete\Admin;

use App\Enums\Filter;
use App\Repository\Repository;
use Yajra\DataTables\DataTables;
use App\Enums\RoleNames;
use App\Enums\AccountTypes;
use App\Models\AccountType;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AccountTypeService
{

    protected $model_account_type;
    protected $with = ['business'];
    public function __construct()
    {
        // set the model
        $this->model_account_type = new Repository(new AccountType());
    }

    public function getData($obj)
    {
        $wh = [];
        $orderBy = Filter::ORDERBY;

        if (isset($obj['orderBy']) && $obj['orderBy'] != 0 && $obj['orderBy'] != "") {
            $orderBy = $obj['orderBy'];
        }
        // for super admin
        if (isset($obj['business_id']) && $obj['business_id'] != 0 && $obj['business_id'] != "") {
            $wh[] = ['business_id', $obj['business_id']];
        }

        if (!empty($obj['start_date'])) {
            $wh[] = ['date_created', '>=', Carbon::parse($obj['start_date'])->startOfDay()];
        }
        if (!empty($obj['end_date'])) {
            $wh[] = ['date_created', '<=', Carbon::parse($obj['end_date'])->endOfDay()];
        }


        $allow_roles = [
            RoleNames::SUPERADMIN,
            RoleNames::BUSINESSADMIN
        ];

        $datatable = $this->model_account_type->getModel()::where($wh)
            ->with($this->with)
            ->where('is_deleted', 0)
            ->orderBy('date_created', $orderBy);
        $datatable = applyRoleScope($datatable, $allow_roles);
        $data = DataTables::of($datatable)
            ->addColumn('business', function ($item) {
                return isset($item->business) ? $item->business->name : '';
            })
            ->editColumn('description', function ($item) {

                return Str::limit($item->description, 50, '...');
            })
            ->addColumn('action', function ($item) {
                return "
                    <a class='btn btn-icon btn-outline-primary mr-2'
                     id='editAccountType' href='javascript:void(0)'
                      data-toggle='tooltip'  data-id='" . $item->account_type_id . "' data-original-title='Edit'><i title='Edit' class='icon-base fa fa-pencil'></i></a>

                    <a class='btn btn-icon btn-outline-danger'
                    id='deleteAccountType'
                    data-id='{$item->account_type_id}'>

                    <i class='fa fa-trash'></i>
                    </a>
                ";
            })
            ->rawColumns(['business', 'description', 'action'])
            ->make(true);
        return $data;
    }

    public function getByid($account_type_id)
    {
        return $this->model_account_type->find($account_type_id);
    }
    public function save($obj)
    {
        if (isset($obj['account_type_id']) && $obj['account_type_id'] > 0) {
            $this->model_account_type->update($obj, $obj['account_type_id']);
            $saved_obj = $this->model_account_type->find($obj['account_type_id']);
        }

        if (!$saved_obj)
            return false;

        return $saved_obj;
    }
    public function delete($account_type_id)
    {
        return $this->model_account_type->update([
            'is_deleted' => 1,
            'deletedby_id' => Auth::id(),
            'date_deleted' => now()
        ], $account_type_id);
    }

    public function getAll()
    {
        if (getRoleName() != RoleNames::SUPERADMIN) {
            return $this->model_account_type->getModel()::with($this->with)
                ->where('business_id', Auth::user()->business_id)
                ->where('is_deleted', 0)
                ->get();
        }
        return $this->model_account_type->getModel()::with($this->with)
            ->whereNull('business_id')
            ->where('is_deleted', 0)
            ->get();
    }
    public function getByBusiness($business_id)
    {
        return $this->model_account_type->getModel()::with($this->with)
            ->where('business_id', $business_id)
            ->where('is_deleted', 0)
            ->get();
    }
    public function resetBusinessAccountType()
    {
        try {

            $business_id = Auth::user()->business_id;

            $account_types = [
                [
                    'name' => AccountTypes::ASSETS,
                    'code' => '1000',
                    'description' => 'Resources owned or controlled by the business that provide future economic benefits, such as cash, bank accounts, inventory, receivables, and fixed assets.',
                ],
                [
                    'name' => AccountTypes::LIABILITIES,
                    'code' => '2000',
                    'description' => 'Financial obligations or debts the business owes to others, including suppliers, loans, taxes payable, and accrued expenses.',
                ],
                [
                    'name' => AccountTypes::EQUITY,
                    'code' => '3000',
                    'description' => 'The owner\'s interest in the business after deducting liabilities from assets. Includes capital, retained earnings, and drawings.',
                ],
                [
                    'name' => AccountTypes::REVENUE,
                    'code' => '4000',
                    'description' => 'Income earned from normal business operations, including sales, service income, commissions, and other operating revenue.',
                ],
                [
                    'name' => AccountTypes::EXPENSES,
                    'code' => '5000',
                    'description' => 'Costs incurred to operate the business, such as purchases, salaries, rent, utilities, marketing, and administrative expenses.',
                ],
            ];

            foreach ($account_types as $item) {

                $account_type = $this->model_account_type->getModel()::firstOrNew([
                    'business_id' => $business_id,
                    'name' => $item['name'],
                ]);

                // Code sirf tab set hoga jab empty ho
                if (empty($account_type->code)) {
                    $account_type->code = $item['code'];
                }
                if (!$account_type->exists && empty($account_type->account_type_id)) {
                    $account_type->account_type_id = generateUuid();
                }

                // Description hamesha latest rahe
                $account_type->description = $item['description'];

                $account_type->business_id = $business_id;
                $account_type->name = $item['name'];
                $account_type->is_deleted = 0;
                $account_type->date_created = $account_type->exists ? $account_type->date_created : now();
                $account_type->date_updated = now();

                $account_type->save();
            }

            return true;
        } catch (Exception $e) {

           return throw $e;
        }
    }
}
