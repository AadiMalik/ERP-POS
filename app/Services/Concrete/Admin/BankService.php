<?php

namespace App\Services\Concrete\Admin;

use App\Enums\Filter;
use App\Enums\RoleNames;
use App\Enums\Status;
use App\Models\Bank;
use App\Repository\Repository;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;

class BankService
{
    protected $model_bank;

    public function __construct()
    {
        $this->model_bank = new Repository(new Bank());
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
        if (isset($obj['branch_id']) && $obj['branch_id'] != 0 && $obj['branch_id'] != "") {
            $wh[] = ['branch_id', $obj['branch_id']];
        }

        $with = ['business', 'branch', 'account'];
        $allow_roles = [
            RoleNames::SUPERADMIN,
            RoleNames::BUSINESSADMIN,
        ];
        $datatable = $this->model_bank->getModel()::where($wh)
            ->with($with)
            ->orderBy('name', $orderBy);
        $datatable = applyRoleScope($datatable, $allow_roles);
        return DataTables::of($datatable)
            ->addColumn('status', function ($item) {
                $checked = $item->status == Status::ACTIVE ? 'checked' : '';

                return '
                <div class="form-check form-switch mb-0">
                    <input
                        class="form-check-input statusBank"
                        type="checkbox"
                        data-id="' . $item->bank_id . '"
                        ' . $checked . '>
                </div>
            ';
            })
            ->addColumn('branch', function ($item) {
                return $item->branch?->name ?? 'All Branches';
            })
            ->addColumn('account', function ($item) {
                return $item->account?->name ?? '-';
            })
            ->addColumn('action', function ($item) {
                return "
                    <a class='btn btn-icon btn-outline-primary mr-2'
                     href='" . route('bank.edit', $item->bank_id) . "'
                    id='editBank'>

                    <i class='fa fa-pencil'></i>
                    </a>

                    <a class='btn btn-icon btn-outline-danger'
                    id='deleteBank'
                    data-id='{$item->bank_id}'>

                    <i class='fa fa-trash'></i>
                    </a>
                ";
            })
            ->rawColumns(['status', 'branch', 'account', 'action'])
            ->make(true);
    }

    public function save($obj)
    {
        if (!empty($obj['bank_id'])) {
            $obj['updatedby_id'] = Auth::user()->id;
            $obj['date_updated'] = now();
            $this->model_bank->update($obj, $obj['bank_id']);
            return $this->model_bank->find($obj['bank_id']);
        }

        $obj['bank_id'] = generateUuid();
        $obj['createdby_id'] = Auth::user()->id;
        $obj['date_created'] = now();
        return $this->model_bank->create($obj);
    }

    public function getById($bank_id)
    {
        return $this->model_bank->find($bank_id);
    }

    public function status($bank_id)
    {
        return $this->model_bank->update([
            'status' => ($this->model_bank->find($bank_id)->status == Status::ACTIVE ? Status::INACTIVE : Status::ACTIVE),
            'updatedby_id' => Auth::id(),
            'date_updated' => now(),
        ], $bank_id);
    }

    public function delete($bank_id)
    {
        return $this->model_bank->update([
            'is_deleted' => 1,
            'deletedby_id' => Auth::id(),
            'date_deleted' => now(),
        ], $bank_id);
    }

    public function getByBusiness($business_id)
    {
        return $this->model_bank->getModel()::with(['branch', 'account'])
            ->where('business_id', $business_id)
            ->where('is_deleted', 0)
            ->get();
    }

    /**
     * Banks usable for a branch: linked to it directly, or shared across
     * every branch of the business (branch_id null - same convention
     * Warehouse used before branch_warehouses, see ValidatesWarehouse).
     * This is what POS/order payment screens must call - never the raw
     * table - so a branch never shows/accepts a bank scoped to another one.
     */
    public function getForBranch($business_id, $branch_id)
    {
        return $this->model_bank->getModel()::with(['account'])
            ->where('business_id', $business_id)
            ->where('status', Status::ACTIVE)
            ->where('is_deleted', 0)
            ->where(function ($q) use ($branch_id) {
                $q->whereNull('branch_id')->orWhere('branch_id', $branch_id);
            })
            ->orderBy('name')
            ->get();
    }
}
