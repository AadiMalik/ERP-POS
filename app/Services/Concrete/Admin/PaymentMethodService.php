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
    // COD is website-only and is seeded separately via seedWebsiteDefaults().
    protected $default_methods = [
        ['name' => 'Cash', 'code' => 'CASH', 'type' => 'cash', 'is_website_only' => false],
        ['name' => 'Card', 'code' => 'CARD', 'type' => 'card', 'is_website_only' => false],
        ['name' => 'Bank', 'code' => 'BANK', 'type' => 'bank', 'is_website_only' => false],
        ['name' => 'Credit', 'code' => 'CREDIT', 'type' => 'credit', 'is_website_only' => false],
        ['name' => 'Wallet', 'code' => 'WALLET', 'type' => 'wallet', 'is_website_only' => false],
    ];

    protected $website_only_methods = [
        ['name' => 'Cash on Delivery', 'code' => 'COD', 'type' => 'cod', 'is_website_only' => true],
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
                'is_website_only' => !empty($method['is_website_only']) ? 1 : 0,
                'status' => Status::ACTIVE,
                'sort_order' => $index,
                'is_deleted' => 0,
                'createdby_id' => Auth::id(),
                'date_created' => now(),
            ]);
        }
    }

    /**
     * Ensures website-only payment methods (COD) exist without adding them to
     * POS defaults. Safe to call repeatedly - only inserts missing codes.
     * Also ensures standard POS methods (including BANK) exist first.
     */
    public function seedWebsiteDefaults($business_id)
    {
        if (empty($business_id)) {
            return;
        }

        // POS defaults first so BANK exists for website bank-transfer reuse.
        $this->seedDefaults($business_id);

        foreach ($this->website_only_methods as $index => $method) {
            $exists = $this->model_payment_method->getModel()::where('business_id', $business_id)
                ->where('code', $method['code'])
                ->where('is_deleted', 0)
                ->exists();

            if ($exists) {
                continue;
            }

            $max_sort = (int) $this->model_payment_method->getModel()::where('business_id', $business_id)
                ->where('is_deleted', 0)
                ->max('sort_order');

            $this->model_payment_method->create([
                'payment_method_id' => generateUuid(),
                'business_id' => $business_id,
                'name' => $method['name'],
                'code' => $method['code'],
                'account_id' => null,
                'type' => $method['type'],
                'is_default' => 0,
                'is_website_only' => 1,
                'status' => Status::ACTIVE,
                'sort_order' => $max_sort + 1 + $index,
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
        // Credit / store_credit / COD route to receivable (or no mapped tender
        // account) at posting time - none need their own mapped account here.
        // Every other type must have a valid account_id.
        if (!in_array($obj['type'] ?? null, ['credit', 'store_credit', 'cod', 'gateway'], true)) {
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

        // POS / admin tender lists never include website-only methods (COD).
        return $this->model_payment_method->getModel()::with($this->with)
            ->where('business_id', $business_id)
            ->where('status', Status::ACTIVE)
            ->where('is_deleted', 0)
            ->where(function ($q) {
                $q->where('is_website_only', 0)->orWhereNull('is_website_only');
            })
            ->orderBy('sort_order', 'asc')
            ->get();
    }
}
