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

class AccountService
{

    protected $model_account_type;
    protected $with = ['business'];
    public function __construct()
    {
        // set the model
        $this->model_account_type = new Repository(new AccountType());
    }

    public function getData()
    {
        $allow_roles = [
            RoleNames::SUPERADMIN,
            RoleNames::BUSINESSADMIN
        ];
        $data = $this->model_account_type->getModel()::with([
            'accountSubTypes.accounts' => function ($q) {
                $q->whereNull('parent_account_id')
                    ->where('is_deleted', 0)
                    ->with('childAccounts');
            }
        ])->where('is_deleted', 0);
        $account_types = applyRoleScope($data, $allow_roles);
        return $account_types->get();
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
