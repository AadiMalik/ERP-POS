<?php

namespace App\Services\Concrete\Admin\Support;

use App\Enums\Filter;
use App\Enums\RoleNames;
use App\Enums\Status;
use App\Repository\Repository;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;

/**
 * Shared CRUD/list/default/active/sort scaffolding for the "lookup type"
 * trio - Order Type (fulfillment behavior), Order Source (where an order
 * originated), and Sale Type (pricing tier). These represent genuinely
 * different business concepts and their own relations/downstream business
 * logic stay fully separate (each concrete service lives in its own file,
 * keyed by its own model/table); only this identical seed-defaults/list/
 * create/edit/status/delete plumbing is shared here.
 */
abstract class AbstractLookupTypeService
{
    protected $model;

    public function __construct()
    {
        $this->model = new Repository($this->newModelInstance());
    }

    abstract protected function newModelInstance();

    abstract protected function pkField(): string;

    /** @return array<int, array{name: string, code: string}> */
    abstract protected function defaultRows(): array;

    /** DataTables action-button id suffix, e.g. 'OrderType' -> editOrderType/deleteOrderType/statusOrderType. */
    abstract protected function domIdSuffix(): string;

    /** SaleType's getData() has never date-filtered; OrderType/OrderSource have. */
    protected function dateFilterEnabled(): bool
    {
        return true;
    }

    /**
     * Order Type/Source are global platform master data (Super Admin only,
     * no business_id column); Sale Type stays per-business. Overridden to
     * true in OrderTypeService/OrderSourceService only. Public so the
     * controller-layer HasLookupTypeCrudActions trait can branch on it too.
     */
    public function isGlobal(): bool
    {
        return false;
    }

    /**
     * Lazily seeds this type's default rows - table-wide (once) when global,
     * otherwise for the given business the first time it touches this module
     * - only if there are none yet.
     */
    public function seedDefaults($business_id)
    {
        if (!$this->isGlobal() && empty($business_id)) {
            return;
        }

        $existsQuery = $this->model->getModel()::where('is_deleted', 0);
        if (!$this->isGlobal()) {
            $existsQuery->where('business_id', $business_id);
        }

        if ($existsQuery->exists()) {
            return;
        }

        $pk = $this->pkField();

        foreach ($this->defaultRows() as $index => $row) {
            $attributes = [
                $pk => generateUuid(),
                'name' => $row['name'],
                'code' => $row['code'],
                'is_default' => $index === 0,
                'status' => Status::ACTIVE,
                'sort_order' => $index,
                'is_deleted' => 0,
                'createdby_id' => Auth::id(),
                'date_created' => now(),
            ];
            if (!$this->isGlobal()) {
                $attributes['business_id'] = $business_id;
            }

            $this->model->create($attributes);
        }
    }

    public function getData($obj)
    {
        $this->seedDefaults($this->isGlobal() ? null : ($obj['business_id'] ?? Auth::user()->business_id));

        $wh = [];
        $orderBy = Filter::ORDERBY;

        if (isset($obj['orderBy']) && $obj['orderBy'] != 0 && $obj['orderBy'] != "") {
            $orderBy = $obj['orderBy'];
        }
        if (!$this->isGlobal() && isset($obj['business_id']) && $obj['business_id'] != 0 && $obj['business_id'] != "") {
            $wh[] = ['business_id', $obj['business_id']];
        }

        if ($this->dateFilterEnabled()) {
            if (!empty($obj['start_date'])) {
                $wh[] = ['date_created', '>=', businessStartOfDay($obj['start_date'])];
            }
            if (!empty($obj['end_date'])) {
                $wh[] = ['date_created', '<=', businessEndOfDay($obj['end_date'])];
            }
        }

        $datatable = $this->model->getModel()::where($wh)
            ->where('is_deleted', 0)
            ->orderBy('sort_order', 'asc')
            ->orderBy('name', $orderBy);

        if (!$this->isGlobal()) {
            $datatable = applyRoleScope($datatable, [
                RoleNames::SUPERADMIN,
                RoleNames::BUSINESSADMIN,
            ]);
        }

        $pk = $this->pkField();
        $suffix = $this->domIdSuffix();

        return DataTables::of($datatable)
            ->addColumn('is_default', function ($item) {
                return $item->is_default ? '<span class="badge bg-label-primary">Default</span>' : '-';
            })
            ->addColumn('status', function ($item) use ($pk, $suffix) {
                $checked = $item->status == Status::ACTIVE ? 'checked' : '';
                $id = $item->{$pk};

                return '
                <div class="form-check form-switch mb-0">
                    <input
                        class="form-check-input status' . $suffix . '"
                        type="checkbox"
                        data-id="' . $id . '"
                        ' . $checked . '>
                </div>
            ';
            })
            ->addColumn('action', function ($item) use ($pk, $suffix) {
                $id = $item->{$pk};

                return "
                    <a class='btn btn-icon btn-outline-primary mr-2'
                     id='edit{$suffix}' href='javascript:void(0)'
                      data-toggle='tooltip'  data-id='" . $id . "' data-original-title='Edit'><i title='Edit' class='icon-base fa fa-pencil'></i></a>

                    <a class='btn btn-icon btn-outline-danger'
                    id='delete{$suffix}'
                    data-id='" . $id . "'>

                    <i class='fa fa-trash'></i>
                    </a>
                ";
            })
            ->rawColumns(['is_default', 'status', 'action'])
            ->make(true);
    }

    public function save($obj)
    {
        $pk = $this->pkField();

        if (!empty($obj[$pk])) {
            $obj['updatedby_id'] = Auth::id();
            $obj['date_updated'] = now();
            $this->model->update($obj, $obj[$pk]);
            return $this->model->find($obj[$pk]);
        }

        $obj[$pk] = generateUuid();
        $obj['createdby_id'] = Auth::id();
        $obj['date_created'] = now();

        return $this->model->create($obj);
    }

    public function getById($id)
    {
        return $this->model->find($id);
    }

    public function status($id)
    {
        return $this->model->update([
            'status' => ($this->model->find($id)->status == Status::ACTIVE ? Status::INACTIVE : Status::ACTIVE),
            'updatedby_id' => Auth::id(),
            'date_updated' => now(),
        ], $id);
    }

    public function delete($id)
    {
        return $this->model->update([
            'is_deleted' => 1,
            'deletedby_id' => Auth::id(),
            'date_deleted' => now(),
        ], $id);
    }

    public function getAllActive($business_id = null)
    {
        if ($this->isGlobal()) {
            $this->seedDefaults(null);

            return $this->model->getModel()::where('status', Status::ACTIVE)
                ->where('is_deleted', 0)
                ->orderBy('sort_order', 'asc')
                ->get();
        }

        $business_id = $business_id ?? Auth::user()->business_id;
        $this->seedDefaults($business_id);

        return $this->model->getModel()::where('business_id', $business_id)
            ->where('status', Status::ACTIVE)
            ->where('is_deleted', 0)
            ->orderBy('sort_order', 'asc')
            ->get();
    }
}
