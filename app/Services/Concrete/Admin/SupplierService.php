<?php

namespace App\Services\Concrete\Admin;

use App\Enums\Filter;
use App\Enums\RoleNames;
use App\Enums\Status;
use App\Models\Supplier;
use App\Repository\Repository;
use App\Traits\Auditable;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Yajra\DataTables\DataTables;

class SupplierService
{
    use Auditable;

    protected $model_supplier;
    protected $with = [
        'business',
        'branch',
        'account'
    ];

    public function __construct()
    {
        $this->model_supplier = new Repository(new Supplier());
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
        if (isset($obj['brand_id']) && $obj['brand_id'] != 0 && $obj['brand_id'] != "") {
            $wh[] = ['brand_id', $obj['brand_id']];
        }
        if (isset($obj['account_id']) && $obj['account_id'] != 0 && $obj['account_id'] != "") {
            $wh[] = ['account_id', $obj['account_id']];
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
        $datatable = $this->model_supplier->getModel()::where($wh)
            ->with($this->with)
            ->orderBy('name', $orderBy);
        $datatable = applyRoleScope($datatable, $allow_roles);
        return DataTables::of($datatable)
            ->addColumn('business', function ($item) {
                return $item->business->name ?? '';
            })
            ->addColumn('branch', function ($item) {
                return $item->branch->name ?? '';
            })
            ->addColumn('account', function ($item) {
                return $item->account->name ?? '';
            })
            ->addColumn('status', function ($item) {

                $checked = $item->status == Status::ACTIVE ? 'checked' : '';

                return '
                <div class="form-check form-switch mb-0">
                    <input
                        class="form-check-input statusSuuplier"
                        type="checkbox"
                        data-id="' . $item->supplier_id . '"
                        ' . $checked . '>
                </div>
            ';
            })
            ->addColumn('action', function ($item) {

                return "
                    <a class='btn btn-icon btn-outline-primary mr-2'
                     href='" . route('supplier.edit', $item->supplier_id) . "'
                    id='editSupplier'>

                    <i class='fa fa-pencil'></i>
                    </a>

                    <a class='btn btn-icon btn-outline-danger'
                    id='deleteSupplier'
                    data-id='{$item->supplier_id}'>

                    <i class='fa fa-trash'></i>
                    </a>
                ";
            })
            ->rawColumns(['business', 'branch', 'account', 'status', 'action'])
            ->make(true);
    }

    public function save($obj)
    {
        DB::beginTransaction();

        try {

            // =========================
            // UPDATE
            // =========================
            if (!empty($obj['supplier_id'])) {

                $supplier = $this->model_supplier->find($obj['supplier_id']);

                if (!$supplier) {
                    throw new Exception('supplier not found');
                }

                $old_values = $supplier->only(['name', 'credit_limit', 'credit_days']);

                $obj['account_id'] = session('accounting_setting.default_supplier_account_id') ?? null;
                $obj['updatedby_id'] = Auth::user()->id;
                $obj['date_updated'] = now();

                $this->model_supplier->update($obj, $obj['supplier_id']);

                DB::commit();

                $updated = $this->model_supplier->find($supplier->supplier_id);

                $this->logActivity('supplier', $supplier->supplier_id, 'updated', $old_values, $updated->only(['name', 'credit_limit', 'credit_days']));

                return $updated;
            }

            //check limit
            $limit = checkPackageLimit('suppliers');

            if (!$limit['status']) {
                throw new Exception($limit['message']);
            }
            // =========================
            // CREATE
            // =========================

            $obj['supplier_id'] = generateUuid();
            $obj['code'] = $obj['code'] ?? generateSupplierCode();
            $obj['account_id'] = session('accounting_setting.default_supplier_account_id') ?? null;
            $obj['createdby_id'] = Auth::user()->id;
            $obj['date_created'] = now();

            $supplier = $this->model_supplier->create($obj);

            DB::commit();

            $this->logActivity('supplier', $supplier->supplier_id, 'created', null, $supplier->only(['name', 'credit_limit', 'credit_days']));

            return $supplier;
        } catch (Exception $e) {

            DB::rollBack();

            throw $e;
        }
    }

    public function getById($supplier_id)
    {
        return $this->model_supplier->getModel()::with($this->with)->find($supplier_id);
    }
    public function status($supplier_id)
    {
        $result = $this->model_supplier->update([
            'status' => ($this->model_supplier->find($supplier_id)->status == Status::ACTIVE ? Status::INACTIVE : Status::ACTIVE),
            'updatedby_id' => Auth::id(),
            'date_updated' => now()
        ], $supplier_id);

        $this->logActivity('supplier', $supplier_id, 'status_changed');

        return $result;
    }

    public function delete($supplier_id)
    {
        $result = $this->model_supplier->update([
            'is_deleted' => 1,
            'deletedby_id' => Auth::id(),
            'date_deleted' => now()
        ], $supplier_id);

        $this->logActivity('supplier', $supplier_id, 'deleted');

        return $result;
    }

    public function getAll()
    {
        return $this->model_supplier->getModel()::with($this->with)
            ->where('business_id', Auth::user()->business_id)
            ->where('is_deleted', 0)
            ->get();
    }
    public function getAllActive()
    {
        return $this->model_supplier->getModel()::with($this->with)
            ->where('business_id', Auth::user()->business_id)
            ->where('status', Status::ACTIVE)
            ->where('is_deleted', 0)
            ->get();
    }

    public function getByBusiness($business_id)
    {
        return $this->model_supplier->getModel()::with($this->with)
            ->where('business_id', $business_id)
            ->where('is_deleted', 0)
            ->get();
    }

    public function getByBranch($branch_id)
    {
        return $this->model_supplier->getModel()::with($this->with)
            ->where('branch_id', $branch_id)
            ->where('is_deleted', 0)
            ->get();
    }
}
