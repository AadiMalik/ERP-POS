<?php

namespace App\Services\Concrete\Admin;

use App\Enums\AccountSubTypes;
use App\Enums\Filter;
use App\Repository\Repository;
use Yajra\DataTables\DataTables;
use App\Enums\RoleNames;
use App\Enums\AccountTypes;
use App\Models\AccountSubType;
use App\Models\AccountType;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AccountSubTypeService
{

    protected $model_account_sub_type;
    protected $model_account_type;
    protected $with = ['business', 'accountType'];
    public function __construct()
    {
        // set the model
        $this->model_account_sub_type = new Repository(new AccountSubType());
        $this->model_account_type = new Repository(new AccountType());
    }

    public function getData($obj)
    {
        $wh = [];
        $orderBy = Filter::ORDERBY;

        if (isset($obj['orderBy']) && $obj['orderBy'] != 0 && $obj['orderBy'] != "") {
            $orderBy = $obj['orderBy'];
        }
        if (isset($obj['account_type_id']) && $obj['account_type_id'] != 0 && $obj['account_type_id'] != "") {
            $wh[] = ['account_type_id', $obj['account_type_id']];
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

        $datatable = $this->model_account_sub_type->getModel()::where($wh)
            ->with($this->with)
            ->where('is_deleted', 0)
            ->orderBy('date_created', $orderBy);
        $datatable = applyRoleScope($datatable, $allow_roles);
        $data = DataTables::of($datatable)
            ->addColumn('accountType', function ($item) {
                return isset($item->accountType) ? $item->accountType->code . ' ' . $item->accountType->name : '';
            })
            ->addColumn('business', function ($item) {
                return isset($item->business) ? $item->business->name : '';
            })
            ->editColumn('description', function ($item) {

                return Str::limit($item->description, 50, '...');
            })
            ->addColumn('action', function ($item) {
                return "
                    <a class='btn btn-icon btn-outline-primary mr-2'
                     id='editAccountSubType' href='javascript:void(0)'
                      data-toggle='tooltip'  data-id='" . $item->account_sub_type_id . "' data-original-title='Edit'><i title='Edit' class='icon-base fa fa-pencil'></i></a>

                    <a class='btn btn-icon btn-outline-danger'
                    id='deleteAccountSubType'
                    data-id='{$item->account_sub_type_id}'>

                    <i class='fa fa-trash'></i>
                    </a>
                ";
            })
            ->rawColumns(['business', 'accountType', 'description', 'action'])
            ->make(true);
        return $data;
    }

    public function getByid($account_sub_type_id)
    {
        return $this->model_account_sub_type->find($account_sub_type_id);
    }
    public function save($obj)
    {
        if (isset($obj['account_sub_type_id']) && $obj['account_sub_type_id'] > 0) {
            $this->model_account_sub_type->update($obj, $obj['account_sub_type_id']);
            $saved_obj = $this->model_account_sub_type->find($obj['account_sub_type_id']);
        }

        if (!$saved_obj)
            return false;

        return $saved_obj;
    }
    public function delete($account_sub_type_id)
    {
        return $this->model_account_sub_type->update([
            'is_deleted' => 1,
            'deletedby_id' => Auth::id(),
            'date_deleted' => now()
        ], $account_sub_type_id);
    }

    public function getAll()
    {
        if (getRoleName() != RoleNames::SUPERADMIN) {
            return $this->model_account_sub_type->getModel()::with($this->with)
                ->where('business_id', Auth::user()->business_id)
                ->where('is_deleted', 0)
                ->get();
        }
        return $this->model_account_sub_type->getModel()::with($this->with)
            ->whereNull('business_id')
            ->where('is_deleted', 0)
            ->get();
    }

    public function getByAccountType($account_type_id)
    {
        return $this->model_account_sub_type->getModel()::with($this->with)
            ->where('account_type_id', $account_type_id)
            ->where('is_deleted', 0)
            ->get();
    }
    public function getByBusiness($business_id)
    {
        return $this->model_account_sub_type->getModel()::with($this->with)
            ->where('business_id', $business_id)
            ->where('is_deleted', 0)
            ->get();
    }
    public function resetBusinessAccountSubType()
    {
        try {

            $business_id = Auth::user()->business_id;
            $account_types = $this->model_account_type->getModel()::where('business_id', $business_id)
                ->where('is_deleted', 0)
                ->get()
                ->keyBy('name');

            $account_sub_types = [

                // ASSETS
                [
                    'account_type' => AccountTypes::ASSETS,
                    'name' => AccountSubTypes::CURRENT_ASSETS,
                    'code' => '1100',
                    'description' => 'Assets expected to be converted into cash or consumed within one year.',
                ],
                [
                    'account_type' => AccountTypes::ASSETS,
                    'name' => AccountSubTypes::CASH_CASH_EQUIVALENTS,
                    'code' => '1110',
                    'description' => 'Cash in hand, petty cash and bank balances.',
                ],
                [
                    'account_type' => AccountTypes::ASSETS,
                    'name' => AccountSubTypes::ACCOUNTS_RECEIVABLE,
                    'code' => '1120',
                    'description' => 'Amounts receivable from customers.',
                ],
                [
                    'account_type' => AccountTypes::ASSETS,
                    'name' => AccountSubTypes::INVENTORY,
                    'code' => '1130',
                    'description' => 'Raw material, stock and finished goods.',
                ],
                [
                    'account_type' => AccountTypes::ASSETS,
                    'name' => AccountSubTypes::PREPAID_EXPENSES,
                    'code' => '1140',
                    'description' => 'Expenses paid in advance.',
                ],
                [
                    'account_type' => AccountTypes::ASSETS,
                    'name' => AccountSubTypes::ADVANCES_DEPOSITS,
                    'code' => '1150',
                    'description' => 'Supplier advances, employee advances and security deposits.',
                ],
                [
                    'account_type' => AccountTypes::ASSETS,
                    'name' => AccountSubTypes::INVESTMENTS,
                    'code' => '1160',
                    'description' => 'Short-term and long-term investments.',
                ],
                [
                    'account_type' => AccountTypes::ASSETS,
                    'name' => AccountSubTypes::FIXED_ASSETS,
                    'code' => '1700',
                    'description' => 'Land, building, vehicles, furniture and equipment.',
                ],
                [
                    'account_type' => AccountTypes::ASSETS,
                    'name' => AccountSubTypes::ACCUMULATED_DEPRECIATION,
                    'code' => '1800',
                    'description' => 'Contra asset for accumulated depreciation.',
                ],
                [
                    'account_type' => AccountTypes::ASSETS,
                    'name' => AccountSubTypes::INTANGIBLE_ASSETS,
                    'code' => '1900',
                    'description' => 'Software, goodwill, patents and licenses.',
                ],

                // LIABILITIES
                [
                    'account_type' => AccountTypes::LIABILITIES,
                    'name' => AccountSubTypes::CURRENT_LIABILITIES,
                    'code' => '2100',
                    'description' => 'Liabilities due within one year.',
                ],
                [
                    'account_type' => AccountTypes::LIABILITIES,
                    'name' => AccountSubTypes::ACCOUNTS_PAYABLE,
                    'code' => '2200',
                    'description' => 'Amounts payable to suppliers.',
                ],
                [
                    'account_type' => AccountTypes::LIABILITIES,
                    'name' => AccountSubTypes::ACCRUED_LIABILITIES,
                    'code' => '2300',
                    'description' => 'Expenses incurred but not yet paid.',
                ],
                [
                    'account_type' => AccountTypes::LIABILITIES,
                    'name' => AccountSubTypes::TAXES_PAYABLE,
                    'code' => '2400',
                    'description' => 'Sales tax, VAT and withholding taxes payable.',
                ],
                [
                    'account_type' => AccountTypes::LIABILITIES,
                    'name' => AccountSubTypes::LOANS_BORROWINGS,
                    'code' => '2500',
                    'description' => 'Short-term and long-term loans.',
                ],
                [
                    'account_type' => AccountTypes::LIABILITIES,
                    'name' => AccountSubTypes::UNEARNED_REVENUE,
                    'code' => '2600',
                    'description' => 'Advance received from customers.',
                ],
                [
                    'account_type' => AccountTypes::LIABILITIES,
                    'name' => AccountSubTypes::OTHER_LIABILITIES,
                    'code' => '2900',
                    'description' => 'Other current and non-current liabilities.',
                ],

                // EQUITY
                [
                    'account_type' => AccountTypes::EQUITY,
                    'name' => AccountSubTypes::CAPITAL,
                    'code' => '3100',
                    'description' => 'Owner, partner or shareholder capital.',
                ],
                [
                    'account_type' => AccountTypes::EQUITY,
                    'name' => AccountSubTypes::RETAINED_EARNINGS,
                    'code' => '3200',
                    'description' => 'Accumulated profits retained in the business.',
                ],
                [
                    'account_type' => AccountTypes::EQUITY,
                    'name' => AccountSubTypes::CURRENT_YEAR_EARNINGS,
                    'code' => '3300',
                    'description' => 'Current financial year profit or loss.',
                ],
                [
                    'account_type' => AccountTypes::EQUITY,
                    'name' => AccountSubTypes::DRAWINGS,
                    'code' => '3400',
                    'description' => 'Owner withdrawals.',
                ],
                [
                    'account_type' => AccountTypes::EQUITY,
                    'name' => AccountSubTypes::RESERVES,
                    'code' => '3500',
                    'description' => 'Business reserves.',
                ],

                // REVENUE
                [
                    'account_type' => AccountTypes::REVENUE,
                    'name' => AccountSubTypes::OPERATING_REVENUE,
                    'code' => '4100',
                    'description' => 'Revenue generated from normal business operations.',
                ],
                [
                    'account_type' => AccountTypes::REVENUE,
                    'name' => AccountSubTypes::SALES_REVENUE,
                    'code' => '4200',
                    'description' => 'Revenue earned from product sales.',
                ],
                [
                    'account_type' => AccountTypes::REVENUE,
                    'name' => AccountSubTypes::SERVICE_REVENUE,
                    'code' => '4300',
                    'description' => 'Revenue earned from services.',
                ],
                [
                    'account_type' => AccountTypes::REVENUE,
                    'name' => AccountSubTypes::OTHER_REVENUE,
                    'code' => '4900',
                    'description' => 'Interest, commission, rental and miscellaneous income.',
                ],

                // EXPENSES
                [
                    'account_type' => AccountTypes::EXPENSES,
                    'name' => AccountSubTypes::COST_OF_GOODS_SOLD,
                    'code' => '5100',
                    'description' => 'Direct cost of goods sold.',
                ],
                [
                    'account_type' => AccountTypes::EXPENSES,
                    'name' => AccountSubTypes::DIRECT_EXPENSES,
                    'code' => '5200',
                    'description' => 'Expenses directly related to production.',
                ],
                [
                    'account_type' => AccountTypes::EXPENSES,
                    'name' => AccountSubTypes::ADMINISTRATIVE_EXPENSES,
                    'code' => '5300',
                    'description' => 'Office and administration expenses.',
                ],
                [
                    'account_type' => AccountTypes::EXPENSES,
                    'name' => AccountSubTypes::SELLING_DISTRIBUTION_EXPENSES,
                    'code' => '5400',
                    'description' => 'Marketing, advertising and delivery expenses.',
                ],
                [
                    'account_type' => AccountTypes::EXPENSES,
                    'name' => AccountSubTypes::FINANCIAL_EXPENSES,
                    'code' => '5500',
                    'description' => 'Interest and bank charges.',
                ],
                [
                    'account_type' => AccountTypes::EXPENSES,
                    'name' => AccountSubTypes::PAYROLL_EXPENSES,
                    'code' => '5600',
                    'description' => 'Salary, wages and employee benefits.',
                ],
                [
                    'account_type' => AccountTypes::EXPENSES,
                    'name' => AccountSubTypes::DEPRECIATION_AMORTIZATION,
                    'code' => '5700',
                    'description' => 'Depreciation and amortization expenses.',
                ],
                [
                    'account_type' => AccountTypes::EXPENSES,
                    'name' => AccountSubTypes::TAX_EXPENSES,
                    'code' => '5800',
                    'description' => 'Income tax and other taxes.',
                ],
                [
                    'account_type' => AccountTypes::EXPENSES,
                    'name' => AccountSubTypes::OTHER_EXPENSES,
                    'code' => '5900',
                    'description' => 'Miscellaneous operating expenses.',
                ],
            ];

            foreach ($account_sub_types as $item) {

                if (!isset($account_types[$item['account_type']])) {
                    continue;
                }

                $account_type = $account_types[$item['account_type']];

                $account_sub_type = $this->model_account_sub_type->getModel()::firstOrNew([
                    'business_id'     => $business_id,
                    'account_type_id' => $account_type->account_type_id,
                    'name'            => $item['name'],
                ]);

                // Code sirf tab set hoga jab empty ho
                if (empty($account_sub_type->code)) {
                    $account_sub_type->code = $item['code'];
                }
                if (!$account_sub_type->exists && empty($account_sub_type->account_sub_type_id)) {
                    $account_sub_type->account_sub_type_id = generateUuid();
                }
                $account_sub_type->business_id = $business_id;
                $account_sub_type->account_type_id = $account_type->account_type_id;
                $account_sub_type->name = $item['name'];
                $account_sub_type->description = $item['description'];
                $account_sub_type->is_deleted = 0;
                $account_sub_type->date_created = $account_sub_type->exists ? $account_sub_type->date_created : now();
                $account_sub_type->date_updated = now();
                $account_sub_type->save();
            }

            return true;
        } catch (Exception $e) {
            return throw $e;
        }
    }
}
