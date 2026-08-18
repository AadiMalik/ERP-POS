<?php

namespace App\Services\Concrete\Admin;

use App\Enums\Filter;
use App\Enums\RoleNames;
use App\Enums\Status;
use App\Models\AccountingSetting;
use App\Models\ExpenseCategory;
use App\Repository\Repository;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Yajra\DataTables\DataTables;

class ExpenseCategoryService
{
    protected $model_expense_category;
    protected $with = ['business', 'account'];

    public function __construct()
    {
        $this->model_expense_category = new Repository(new ExpenseCategory());
    }

    public function getData($obj)
    {
        $wh = [];
        $orderBy = Filter::ORDERBY;

        if (isset($obj['orderBy']) && $obj['orderBy'] != 0 && $obj['orderBy'] != "") {
            $orderBy = $obj['orderBy'];
        }
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
            RoleNames::BUSINESSADMIN,
            RoleNames::FINANCEMANAGER,
            RoleNames::ACCOUNTANT,
        ];

        $datatable = $this->model_expense_category->getModel()::where($wh)
            ->with($this->with)
            ->where('is_deleted', 0)
            ->orderBy('date_created', $orderBy);
        $datatable = applyRoleScope($datatable, $allow_roles);

        return DataTables::of($datatable)
            ->addColumn('business', function ($item) {
                return $item->business->name ?? '';
            })
            ->addColumn('account', function ($item) {
                $account = $item->account ? ($item->account->code . ' ' . $item->account->name) : 'Not Configured';
                return $item->use_default_account
                    ? $account . ' <span class="badge bg-label-info">Default</span>'
                    : $account . ' <span class="badge bg-label-warning">Manual</span>';
            })
            ->editColumn('description', function ($item) {
                return Str::limit($item->description, 50, '...');
            })
            ->addColumn('status', function ($item) {
                $badge = $item->status == Status::ACTIVE ? 'bg-label-success' : 'bg-label-secondary';
                return '<span class="badge ' . $badge . '">' . ucfirst($item->status) . '</span>';
            })
            ->addColumn('action', function ($item) {
                return "
                    <a class='btn btn-icon btn-outline-primary mr-2'
                     id='editExpenseCategory' href='javascript:void(0)'
                      data-toggle='tooltip'  data-id='" . $item->expense_category_id . "' data-original-title='Edit'><i title='Edit' class='icon-base fa fa-pencil'></i></a>

                    <a class='btn btn-icon btn-outline-danger'
                    id='deleteExpenseCategory'
                    data-id='{$item->expense_category_id}'>

                    <i class='fa fa-trash'></i>
                    </a>
                ";
            })
            ->rawColumns(['business', 'account', 'description', 'status', 'action'])
            ->make(true);
    }

    public function getById($expense_category_id)
    {
        return $this->model_expense_category->getModel()::with($this->with)->find($expense_category_id);
    }

    public function save($obj)
    {
        $business_id = $obj['business_id'] ?? Auth::user()->business_id;

        // "Use default" is whatever the form explicitly says; if it wasn't
        // sent at all (e.g. an API caller), fall back to inferring it from
        // whether a specific account was chosen.
        $use_default_account = array_key_exists('use_default_account', $obj)
            ? (bool) $obj['use_default_account']
            : empty($obj['account_id']);

        if ($use_default_account) {
            $accounting_setting = AccountingSetting::where('business_id', $business_id)->first();
            $account_id = $accounting_setting->default_expense_account_id ?? null;
        } else {
            $account_id = $obj['account_id'] ?? null;
        }

        $data = [
            'business_id'          => $business_id,
            'name'                 => $obj['name'],
            'code'                 => $obj['code'] ?? null,
            'description'          => $obj['description'] ?? null,
            'account_id'           => $account_id,
            'use_default_account'  => $use_default_account,
            'status'               => $obj['status'] ?? Status::ACTIVE,
        ];

        if (!empty($obj['expense_category_id'])) {
            $data['updatedby_id'] = Auth::id();
            $data['date_updated'] = now();

            $this->model_expense_category->update($data, $obj['expense_category_id']);
            return $this->model_expense_category->find($obj['expense_category_id']);
        }

        $data['expense_category_id'] = generateUuid();
        $data['is_deleted'] = 0;
        $data['createdby_id'] = Auth::id();
        $data['date_created'] = now();

        return $this->model_expense_category->create($data);
    }

    public function delete($expense_category_id)
    {
        return $this->model_expense_category->update([
            'is_deleted'   => 1,
            'deletedby_id' => Auth::id(),
            'date_deleted' => now(),
        ], $expense_category_id);
    }

    public function getAll()
    {
        if (getRoleName() != RoleNames::SUPERADMIN) {
            return $this->model_expense_category->getModel()::with($this->with)
                ->where('business_id', Auth::user()->business_id)
                ->where('is_deleted', 0)
                ->get();
        }
        return $this->model_expense_category->getModel()::with($this->with)
            ->whereNull('business_id')
            ->where('is_deleted', 0)
            ->get();
    }

    public function getByBusiness($business_id)
    {
        return $this->model_expense_category->getModel()::with($this->with)
            ->where('business_id', $business_id)
            ->where('is_deleted', 0)
            ->get();
    }

    public function getActiveByBusiness($business_id)
    {
        return $this->model_expense_category->getModel()::with($this->with)
            ->where('business_id', $business_id)
            ->where('status', Status::ACTIVE)
            ->where('is_deleted', 0)
            ->orderBy('name')
            ->get();
    }

    /**
     * Re-points every Expense Category that is still following the business
     * default (use_default_account = true) to the newly-selected Expense
     * Account - called from SettingService::updateAccountingSetting()
     * whenever default_expense_account_id changes. Categories with a
     * deliberate manual override are left untouched, and already-posted
     * expenses keep their own snapshot account_id, so historical JVs are
     * never altered by this.
     */
    public function syncDefaultAccount($business_id, $account_id)
    {
        return $this->model_expense_category->getModel()::where('business_id', $business_id)
            ->where('use_default_account', true)
            ->where('is_deleted', 0)
            ->update([
                'account_id'   => $account_id,
                'date_updated' => now(),
            ]);
    }

    /**
     * Commonly-used standard expense categories, restored/refilled for a
     * business by name (idempotent - safe to call repeatedly). Mirrors
     * AccountTypeService::resetBusinessAccountType(). Every category always
     * defaults to the business's configured Expense Account; a category
     * that was manually re-pointed to a different account
     * (use_default_account = false) keeps that choice across resets.
     */
    public function resetBusinessExpenseCategory($business_id = false)
    {
        try {
            $business_id = $business_id === false ? Auth::user()->business_id : $business_id;

            $accounting_setting = AccountingSetting::where('business_id', $business_id)->first();
            $default_account_id = $accounting_setting->default_expense_account_id ?? null;

            if (empty($default_account_id)) {
                throw new Exception('Please select an Expense Account in Accounting Settings first, then reset the Expense Categories.');
            }

            $categories = [
                ['name' => 'Rent Expense', 'code' => 'EXPC-001', 'description' => 'Monthly rent for shop, office, or warehouse premises.'],
                ['name' => 'Utilities (Electricity, Water, Gas)', 'code' => 'EXPC-002', 'description' => 'Electricity, water, gas, and other utility bills.'],
                ['name' => 'Salaries & Wages', 'code' => 'EXPC-003', 'description' => 'Staff salaries, wages, and daily labour payments.'],
                ['name' => 'Office & Store Supplies', 'code' => 'EXPC-004', 'description' => 'Stationery, printing, packaging, and other consumable supplies.'],
                ['name' => 'Repairs & Maintenance', 'code' => 'EXPC-005', 'description' => 'Repair and maintenance of equipment, fixtures, and premises.'],
                ['name' => 'Marketing & Advertising', 'code' => 'EXPC-006', 'description' => 'Promotions, advertising, and marketing campaign costs.'],
                ['name' => 'Transportation & Fuel', 'code' => 'EXPC-007', 'description' => 'Fuel, delivery, and transportation costs.'],
                ['name' => 'Communication (Phone/Internet)', 'code' => 'EXPC-008', 'description' => 'Phone, internet, and other communication service bills.'],
                ['name' => 'Bank Charges', 'code' => 'EXPC-009', 'description' => 'Bank service charges, transaction fees, and card processing fees.'],
                ['name' => 'Staff Meals & Refreshments', 'code' => 'EXPC-010', 'description' => 'Meals, tea, and refreshments provided to staff.'],
                ['name' => 'Cleaning & Sanitation', 'code' => 'EXPC-011', 'description' => 'Cleaning supplies and sanitation services.'],
                ['name' => 'Miscellaneous Expense', 'code' => 'EXPC-012', 'description' => 'Small or one-off expenses that do not fit another category.'],
            ];

            foreach ($categories as $item) {
                $category = $this->model_expense_category->getModel()::firstOrNew([
                    'business_id' => $business_id,
                    'name'        => $item['name'],
                ]);

                if (empty($category->code)) {
                    $category->code = $item['code'];
                }
                if (!$category->exists && empty($category->expense_category_id)) {
                    $category->expense_category_id = generateUuid();
                }

                // A brand-new row, or one still following the default,
                // always resets to the current default Expense Account. A
                // row with a deliberate manual override keeps its account.
                if (!$category->exists || $category->use_default_account) {
                    $category->account_id = $default_account_id;
                    $category->use_default_account = true;
                }

                $category->description = $item['description'];
                $category->business_id = $business_id;
                $category->name = $item['name'];
                $category->status = $category->status ?: Status::ACTIVE;
                $category->is_deleted = 0;
                $category->date_created = $category->exists ? $category->date_created : now();
                $category->date_updated = now();

                $category->save();
            }

            return true;
        } catch (Exception $e) {
            throw $e;
        }
    }
}
