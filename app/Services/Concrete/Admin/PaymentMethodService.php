<?php

namespace App\Services\Concrete\Admin;

use App\Enums\Filter;
use App\Enums\RoleNames;
use App\Enums\Status;
use App\Models\Account;
use App\Models\PaymentMethod;
use App\Repository\Repository;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;

class PaymentMethodService
{
    protected $model_payment_method;
    protected $with = ['account'];

    // Lazily seeded default payment methods for a business that has none yet.
    // account_id is intentionally left null - the business admin maps real
    // accounts afterward via the UI (Credit stays null permanently by design).
    protected $default_methods = [
        ['name' => 'Cash', 'code' => 'CASH', 'type' => 'cash'],
        ['name' => 'Card', 'code' => 'CARD', 'type' => 'card'],
        ['name' => 'Bank', 'code' => 'BANK', 'type' => 'bank'],
        ['name' => 'Credit', 'code' => 'CREDIT', 'type' => 'credit'],
        ['name' => 'Wallet', 'code' => 'WALLET', 'type' => 'wallet'],
    ];

    public function __construct()
    {
        $this->model_payment_method = new Repository(new PaymentMethod());
    }

    /**
     * Lazily seeds Cash/Card/Bank/Credit/Wallet for a business the first time
     * it touches this module - only if it has no payment methods yet.
     */
    public function seedDefaults($business_id)
    {
        if (empty($business_id)) {
            return;
        }

        $exists = $this->model_payment_method->getModel()::where('business_id', $business_id)
            ->where('is_deleted', 0)
            ->exists();

        if ($exists) {
            return;
        }

        foreach ($this->default_methods as $index => $method) {
            $this->model_payment_method->create([
                'payment_method_id' => generateUuid(),
                'business_id' => $business_id,
                'name' => $method['name'],
                'code' => $method['code'],
                'account_id' => null,
                'type' => $method['type'],
                'is_default' => $index === 0,
                'status' => Status::ACTIVE,
                'sort_order' => $index,
                'is_deleted' => 0,
                'createdby_id' => Auth::id(),
                'date_created' => now(),
            ]);
        }
    }

    public function getData($obj)
    {
        $this->seedDefaults($obj['business_id'] ?? Auth::user()->business_id);

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
        ];
        $datatable = $this->model_payment_method->getModel()::where($wh)
            ->with($this->with)
            ->where('is_deleted', 0)
            ->orderBy('sort_order', 'asc')
            ->orderBy('name', $orderBy);
        $datatable = applyRoleScope($datatable, $allow_roles);
        return DataTables::of($datatable)
            ->addColumn('account', function ($item) {
                return $item->account?->name ?? '-';
            })
            ->addColumn('is_default', function ($item) {
                return $item->is_default ? '<span class="badge bg-label-primary">Default</span>' : '-';
            })
            ->addColumn('status', function ($item) {

                $checked = $item->status == Status::ACTIVE ? 'checked' : '';

                return '
                <div class="form-check form-switch mb-0">
                    <input
                        class="form-check-input statusPaymentMethod"
                        type="checkbox"
                        data-id="' . $item->payment_method_id . '"
                        ' . $checked . '>
                </div>
            ';
            })
            ->addColumn('action', function ($item) {

                return "
                    <a class='btn btn-icon btn-outline-primary mr-2'
                     id='editPaymentMethod' href='javascript:void(0)'
                      data-toggle='tooltip'  data-id='" . $item->payment_method_id . "' data-original-title='Edit'><i title='Edit' class='icon-base fa fa-pencil'></i></a>

                    <a class='btn btn-icon btn-outline-danger'
                    id='deletePaymentMethod'
                    data-id='{$item->payment_method_id}'>

                    <i class='fa fa-trash'></i>
                    </a>
                ";
            })
            ->rawColumns(['account', 'is_default', 'status', 'action'])
            ->make(true);
    }

    public function save($obj)
    {
        // Credit routes to the business's receivable account at posting time,
        // and store_credit to the business's dedicated store-credit account -
        // neither needs its own mapped account here. Every other type must
        // have a valid account_id.
        if (!in_array($obj['type'] ?? null, ['credit', 'store_credit'], true)) {
            if (empty($obj['account_id'])) {
                throw new Exception('Account is required for this payment method type.');
            }

            $account_exists = Account::where('account_id', $obj['account_id'])
                ->where('is_deleted', 0)
                ->exists();

            if (!$account_exists) {
                throw new Exception('Selected account does not exist.');
            }
        }

        if (!empty($obj['payment_method_id'])) {
            $obj['updatedby_id'] = Auth::user()->id;
            $obj['date_updated'] = now();
            $this->model_payment_method->update($obj, $obj['payment_method_id']);
            return $this->model_payment_method->find($obj['payment_method_id']);
        }

        $obj['payment_method_id'] = generateUuid();
        $obj['createdby_id'] = Auth::user()->id;
        $obj['date_created'] = now();
        $saved_obj = $this->model_payment_method->create($obj);
        return $saved_obj;
    }

    public function getById($payment_method_id)
    {
        return $this->model_payment_method->getModel()::with($this->with)->find($payment_method_id);
    }
    public function status($payment_method_id)
    {
        return $this->model_payment_method->update([
            'status' => ($this->model_payment_method->find($payment_method_id)->status == Status::ACTIVE ? Status::INACTIVE : Status::ACTIVE),
            'updatedby_id' => Auth::id(),
            'date_updated' => now()
        ], $payment_method_id);
    }

    public function delete($payment_method_id)
    {
        return $this->model_payment_method->update([
            'is_deleted' => 1,
            'deletedby_id' => Auth::id(),
            'date_deleted' => now()
        ], $payment_method_id);
    }

    public function getAllActive($business_id = null)
    {
        $business_id = $business_id ?? Auth::user()->business_id;
        $this->seedDefaults($business_id);

        return $this->model_payment_method->getModel()::with($this->with)
            ->where('business_id', $business_id)
            ->where('status', Status::ACTIVE)
            ->where('is_deleted', 0)
            ->orderBy('sort_order', 'asc')
            ->get();
    }
}
