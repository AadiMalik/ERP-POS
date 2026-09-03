<?php

namespace App\Services\Concrete\Admin;

use App\Enums\Filter;
use App\Enums\RoleNames;
use App\Enums\Status;
use App\Models\Branch;
use App\Models\PosRegister;
use App\Models\PosSetting;
use App\Models\User;
use App\Models\Warehouse;
use App\Repository\Repository;
use App\Traits\Auditable;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;

class PosRegisterService
{
    use Auditable;

    protected $model_pos_register;

    public function __construct()
    {
        $this->model_pos_register = new Repository(new PosRegister());
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
        ];
        $datatable = $this->model_pos_register->getModel()::with(['branch', 'warehouse', 'assignedUser'])
            ->where($wh)
            ->where('is_deleted', 0)
            ->orderBy('name', $orderBy);
        $datatable = applyRoleScope($datatable, $allow_roles);
        return DataTables::of($datatable)
            ->addColumn('branch', function ($item) {
                return $item->branch->name ?? '-';
            })
            ->addColumn('warehouse', function ($item) {
                return $item->warehouse->name ?? '-';
            })
            ->addColumn('assigned_user', function ($item) {
                return $item->assignedUser->name ?? '-';
            })
            ->addColumn('mode', function ($item) {
                return ucfirst($item->mode);
            })
            ->addColumn('status', function ($item) {

                $checked = $item->status == Status::ACTIVE ? 'checked' : '';

                return '
                <div class="form-check form-switch mb-0">
                    <input
                        class="form-check-input statusPosRegister"
                        type="checkbox"
                        data-id="' . $item->pos_register_id . '"
                        ' . $checked . '>
                </div>
            ';
            })
            ->addColumn('action', function ($item) {

                return "
                    <a class='btn btn-icon btn-outline-primary mr-2'
                     id='editPosRegister' href='javascript:void(0)'
                      data-toggle='tooltip'  data-id='" . $item->pos_register_id . "' data-original-title='Edit'><i title='Edit' class='icon-base fa fa-pencil'></i></a>

                    <a class='btn btn-icon btn-outline-danger'
                    id='deletePosRegister'
                    data-id='{$item->pos_register_id}'>

                    <i class='fa fa-trash'></i>
                    </a>
                ";
            })
            ->rawColumns(['status', 'action'])
            ->make(true);
    }

    public function save($obj)
    {

        if (!empty($obj['pos_register_id'])) {
            $old = $this->model_pos_register->find($obj['pos_register_id']);
            $old_values = $old->only(['name', 'code', 'branch_id', 'warehouse_id', 'assigned_user_id', 'mode', 'status']);

            $obj['updatedby_id'] = Auth::user()->id;
            $obj['date_updated'] = now();
            $this->model_pos_register->update($obj, $obj['pos_register_id']);
            $saved_obj = $this->model_pos_register->find($obj['pos_register_id']);

            $this->logActivity(
                'pos_register',
                $saved_obj->pos_register_id,
                'updated',
                $old_values,
                $saved_obj->only(['name', 'code', 'branch_id', 'warehouse_id', 'assigned_user_id', 'mode', 'status']),
                null,
                $saved_obj->business_id,
                $saved_obj->branch_id
            );

            return $saved_obj;
        }

        $obj['pos_register_id'] = generateUuid();
        $obj['createdby_id'] = Auth::user()->id;
        $obj['date_created'] = now();
        $saved_obj = $this->model_pos_register->create($obj);

        $this->logActivity(
            'pos_register',
            $saved_obj->pos_register_id,
            'created',
            null,
            $saved_obj->only(['name', 'code', 'branch_id', 'warehouse_id', 'assigned_user_id', 'mode', 'status']),
            null,
            $saved_obj->business_id,
            $saved_obj->branch_id
        );

        return $saved_obj;
    }

    public function getById($pos_register_id)
    {
        return $this->model_pos_register->find($pos_register_id);
    }

    public function status($pos_register_id)
    {
        $register = $this->model_pos_register->find($pos_register_id);
        $new_status = $register->status == Status::ACTIVE ? Status::INACTIVE : Status::ACTIVE;

        $result = $this->model_pos_register->update([
            'status' => $new_status,
            'updatedby_id' => Auth::id(),
            'date_updated' => now()
        ], $pos_register_id);

        $this->logActivity(
            'pos_register',
            $pos_register_id,
            'status_changed',
            ['status' => $register->status],
            ['status' => $new_status],
            null,
            $register->business_id,
            $register->branch_id
        );

        return $result;
    }

    public function delete($pos_register_id)
    {
        $register = $this->model_pos_register->find($pos_register_id);

        $result = $this->model_pos_register->update([
            'is_deleted' => 1,
            'deletedby_id' => Auth::id(),
            'date_deleted' => now()
        ], $pos_register_id);

        $this->logActivity(
            'pos_register',
            $pos_register_id,
            'deleted',
            null,
            null,
            null,
            $register->business_id,
            $register->branch_id
        );

        return $result;
    }

    /**
     * Resolves which register a cashier should use for the given business/branch.
     *
     * - manual mode: the caller must explicitly pick an active register belonging
     *   to this business/branch.
     * - automatic mode: a single shared "Auto Register" is lazily created per
     *   business/branch the first time it's needed.
     */
    public function resolveRegisterForUser($business_id, $branch_id, $user, $requested_register_id = null)
    {
        $pos_setting = PosSetting::where('business_id', $business_id)->first();
        $register_mode = $pos_setting->register_mode ?? 'manual';

        if ($register_mode == 'manual') {
            if (empty($requested_register_id)) {
                throw new Exception('Please select a register to open a session.');
            }

            $register = $this->model_pos_register->getModel()::where('pos_register_id', $requested_register_id)
                ->where('business_id', $business_id)
                ->where('branch_id', $branch_id)
                ->where('status', Status::ACTIVE)
                ->where('is_deleted', 0)
                ->first();

            if (empty($register)) {
                throw new Exception('Selected register is invalid or inactive.');
            }

            return $register;
        }

        // automatic mode - reuse the existing auto register for this branch if one
        // was already created, otherwise resolve a warehouse and create it.
        $existing = $this->model_pos_register->getModel()::where('business_id', $business_id)
            ->where('branch_id', $branch_id)
            ->where('mode', 'automatic')
            ->first();

        if (!empty($existing)) {
            return $existing;
        }

        $warehouse = Warehouse::where('branch_id', $branch_id)
            ->where('is_deleted', 0)
            ->first();

        if (empty($warehouse)) {
            throw new Exception('No warehouse is configured for this branch. Please configure a warehouse before using automatic register mode.');
        }

        return $this->model_pos_register->getModel()::create([
            'pos_register_id' => generateUuid(),
            'business_id' => $business_id,
            'branch_id' => $branch_id,
            'mode' => 'automatic',
            'name' => 'Auto Register',
            'code' => 'AUTO-' . substr($branch_id, 0, 8),
            'warehouse_id' => $warehouse->warehouse_id,
            'status' => Status::ACTIVE,
            'assigned_user_id' => null,
            'is_deleted' => 0,
            'createdby_id' => $user->id ?? null,
            'date_created' => now(),
        ]);
    }

    /**
     * Active users of a business, for the "assigned user" dropdown shown when a
     * register is in manual mode.
     */
    public function getAssignableUsers($business_id)
    {
        return User::where('business_id', $business_id)
            ->where('is_deleted', 0)
            ->get();
    }

    /**
     * The effective Automatic register mode open/close window for a branch -
     * the branch's own open_time/close_time overrides the business-level
     * default configured on pos_settings when set.
     *
     * @return array{open_time: ?string, close_time: ?string}
     */
    public function getEffectiveWindow($business_id, $branch_id)
    {
        $branch = Branch::find($branch_id);
        $pos_setting = PosSetting::where('business_id', $business_id)->first();

        return [
            'open_time' => $branch->open_time ?? $pos_setting->open_time ?? null,
            'close_time' => $branch->close_time ?? $pos_setting->close_time ?? null,
        ];
    }

    /**
     * Whether $now falls inside the [open_time, close_time) window, correctly
     * handling an overnight window (close_time <= open_time, e.g. 20:00-02:00)
     * by treating it as spanning midnight into the next day.
     */
    public function isWithinWindow($open_time, $close_time, ?Carbon $now = null)
    {
        if (empty($open_time) || empty($close_time)) {
            // No window configured - Automatic mode has nothing to gate on,
            // so treat the business as always open.
            return true;
        }

        $now = $now ?? Carbon::now();
        $open = Carbon::parse($now->toDateString() . ' ' . $open_time);
        $close = Carbon::parse($now->toDateString() . ' ' . $close_time);

        if ($close->lte($open)) {
            // Overnight window - "today" is whichever calendar day the window
            // started on: before opening time we're still inside yesterday's
            // window, at/after opening time we're inside today's.
            if ($now->lt($open)) {
                $open->subDay();
            } else {
                $close->addDay();
            }
        }

        return $now->gte($open) && $now->lt($close);
    }

    /**
     * The start-of-window timestamp $now currently belongs to - used to bucket
     * Automatic-mode sessions into one per business day even when the window
     * spans midnight.
     */
    public function currentWindowStart($open_time, $close_time, ?Carbon $now = null)
    {
        $now = $now ?? Carbon::now();

        if (empty($open_time)) {
            return $now->copy()->startOfDay();
        }

        $open = Carbon::parse($now->toDateString() . ' ' . $open_time);

        if (!empty($close_time)) {
            $close = Carbon::parse($now->toDateString() . ' ' . $close_time);

            if ($close->lte($open) && $now->lt($open)) {
                $open->subDay();
            }
        }

        return $open;
    }
}
