<?php

namespace App\Services\Concrete\Admin;

use App\Enums\Filter;
use App\Enums\RoleNames;
use App\Enums\Status;
use App\Models\Voucher;
use App\Models\VoucherRedemption;
use App\Repository\Repository;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

class VoucherService
{
    protected $model_voucher;

    protected $with = [
        'products',
        'categories',
        'users',
        'orderTypes',
        'branches',
    ];

    // Maps the array key the caller may pass in $obj to the scope-pivot table/column
    // it belongs to. An absent key = leave the existing pivot rows untouched, an empty
    // array = explicitly clear the restriction back to "applies to all".
    protected $scope_dimensions = [
        'product_ids'    => ['table' => 'voucher_products', 'column' => 'product_id'],
        'category_ids'   => ['table' => 'voucher_categories', 'column' => 'category_id'],
        'customer_ids'   => ['table' => 'voucher_customers', 'column' => 'user_id'],
        'order_type_ids' => ['table' => 'voucher_order_types', 'column' => 'order_type_id'],
        'branch_ids'     => ['table' => 'voucher_branches', 'column' => 'branch_id'],
    ];

    public function __construct()
    {
        $this->model_voucher = new Repository(new Voucher());
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
        $datatable = $this->model_voucher->getModel()::where($wh)
            ->where('is_deleted', 0)
            ->orderBy('name', $orderBy);
        $datatable = applyRoleScope($datatable, $allow_roles);
        return DataTables::of($datatable)
            ->addColumn('type', function ($item) {
                return ucfirst($item->type);
            })
            ->addColumn('value', function ($item) {
                return $item->type == 'percent' ? number_format($item->value, 2) . '%' : number_format($item->value, 3);
            })
            ->addColumn('usage', function ($item) {
                $limit = $item->usage_limit_total ?? '&infin;';
                return $item->used_count . ' / ' . $limit;
            })
            ->addColumn('status', function ($item) {

                $checked = $item->status == Status::ACTIVE ? 'checked' : '';

                return '
                <div class="form-check form-switch mb-0">
                    <input
                        class="form-check-input statusVoucher"
                        type="checkbox"
                        data-id="' . $item->voucher_id . '"
                        ' . $checked . '>
                </div>
            ';
            })
            ->addColumn('action', function ($item) {

                return "
                    <a class='btn btn-icon btn-outline-primary mr-2'
                     id='editVoucher' href='javascript:void(0)'
                      data-toggle='tooltip'  data-id='" . $item->voucher_id . "' data-original-title='Edit'><i title='Edit' class='icon-base fa fa-pencil'></i></a>

                    <a class='btn btn-icon btn-outline-danger'
                    id='deleteVoucher'
                    data-id='{$item->voucher_id}'>

                    <i class='fa fa-trash'></i>
                    </a>
                ";
            })
            ->rawColumns(['usage', 'status', 'action'])
            ->make(true);
    }

    public function save($obj)
    {
        DB::beginTransaction();

        try {
            $header = collect($obj)->only([
                'business_id',
                'code',
                'name',
                'type',
                'value',
                'valid_from',
                'valid_to',
                'usage_limit_total',
                'usage_limit_per_customer',
                'min_order_amount',
                'status',
            ])->toArray();

            if (!empty($obj['voucher_id'])) {
                $voucher_id = $obj['voucher_id'];

                $header['updatedby_id'] = Auth::user()->id;
                $header['date_updated'] = now();

                $this->model_voucher->update($header, $voucher_id);
            } else {
                $voucher_id = generateUuid();

                $header['voucher_id'] = $voucher_id;
                $header['used_count'] = 0;
                $header['createdby_id'] = Auth::user()->id;
                $header['date_created'] = now();

                $this->model_voucher->create($header);
            }

            $this->syncScopePivots($voucher_id, $obj);

            DB::commit();

            return $this->model_voucher->getModel()::with($this->with)->find($voucher_id);
        } catch (\Exception $e) {

            DB::rollBack();

            throw $e;
        }
    }

    /**
     * Delete-then-recreate each scope-pivot dimension that was explicitly passed in
     * $obj (same pattern PurchaseService/PurchaseReturnService use for detail rows,
     * applied to pivot tables). A dimension key that is entirely absent from $obj is
     * left untouched; a dimension key present as an empty array clears the
     * restriction back to "applies to all".
     */
    protected function syncScopePivots($voucher_id, $obj)
    {
        foreach ($this->scope_dimensions as $key => $meta) {
            if (!array_key_exists($key, $obj)) {
                continue;
            }

            DB::table($meta['table'])->where('voucher_id', $voucher_id)->delete();

            $ids = array_values(array_filter((array) $obj[$key], function ($id) {
                return $id !== null && $id !== '';
            }));

            if (!empty($ids)) {
                $rows = array_map(function ($id) use ($voucher_id, $meta) {
                    return [
                        'voucher_id'    => $voucher_id,
                        $meta['column'] => $id,
                    ];
                }, $ids);

                DB::table($meta['table'])->insert($rows);
            }
        }
    }

    public function getById($voucher_id)
    {
        return $this->model_voucher->getModel()::with($this->with)->find($voucher_id);
    }

    public function status($voucher_id)
    {
        return $this->model_voucher->update([
            'status' => ($this->model_voucher->find($voucher_id)->status == Status::ACTIVE ? Status::INACTIVE : Status::ACTIVE),
            'updatedby_id' => Auth::id(),
            'date_updated' => now()
        ], $voucher_id);
    }

    public function delete($voucher_id)
    {
        return $this->model_voucher->update([
            'is_deleted' => 1,
            'deletedby_id' => Auth::id(),
            'date_deleted' => now()
        ], $voucher_id);
    }

    /**
     * Lists currently-configured vouchers for admin/reporting screens -
     * eligibility (dates/usage-limits/scope) is still enforced server-side by
     * isApplicable() inside save(), this is only what to show. The POS screen
     * itself does not use this (vouchers are entered by code, not picked from
     * a list) but DiscountService's sibling method exists for symmetry and
     * potential future admin listing use.
     */
    public function getAllActive($business_id = null)
    {
        $business_id = $business_id ?? Auth::user()->business_id;

        return $this->model_voucher->getModel()::where('business_id', $business_id)
            ->where('status', Status::ACTIVE)
            ->where('is_deleted', 0)
            ->get();
    }

    /**
     * Lightweight search-as-you-type for the POS voucher lookup: active,
     * non-deleted, business-scoped, matching code or name, currently inside
     * its valid date window and not yet fully used up. This is a coarse
     * "is it even a live voucher" filter for populating suggestions - full
     * per-cart eligibility (customer/branch/order-amount/product/category
     * scope) is still enforced by isApplicable() at apply-time, same as
     * today when a code is typed exactly.
     */
    public function searchActive(string $term, string $business_id, int $limit = 20)
    {
        $now = Carbon::now();

        return $this->model_voucher->getModel()::where('business_id', $business_id)
            ->where('status', Status::ACTIVE)
            ->where('is_deleted', 0)
            ->where(function ($q) use ($term) {
                $q->where('code', 'like', '%' . $term . '%')
                    ->orWhere('name', 'like', '%' . $term . '%');
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('valid_from')->orWhere('valid_from', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('valid_to')->orWhere('valid_to', '>=', $now);
            })
            ->where(function ($q) {
                $q->whereNull('usage_limit_total')->orWhereColumn('used_count', '<', 'usage_limit_total');
            })
            ->orderBy('code')
            ->limit($limit)
            ->get(['voucher_id', 'code', 'name', 'type', 'value', 'min_order_amount']);
    }

    /**
     * Case-tolerant active/non-deleted lookup by code for the given business - used
     * by the POS screen to resolve a scanned/typed voucher code.
     */
    public function findByCode($code, $business_id)
    {
        return $this->model_voucher->getModel()::with($this->with)
            ->where('business_id', $business_id)
            ->where('status', Status::ACTIVE)
            ->where('is_deleted', 0)
            ->whereRaw('LOWER(code) = ?', [strtolower((string) $code)])
            ->first();
    }

    /**
     * The single server-side eligibility check - Order posting and the POS screen
     * UI both call this so voucher-eligibility logic lives in exactly one place.
     * Identical to DiscountService::isApplicable(), except the per-customer
     * usage-limit check is fully implemented here since voucher redemptions are
     * tracked in voucher_redemptions.
     *
     * $context keys: product_id, category_id, user_id, order_type_id,
     * branch_id, order_amount, now (optional Carbon, defaults to now()).
     *
     * @return array{eligible: bool, reason: string|null}
     */
    public function isApplicable(Voucher $voucher, array $context): array
    {
        $now = $context['now'] ?? Carbon::now();

        if ($voucher->status != Status::ACTIVE || $voucher->is_deleted) {
            return ['eligible' => false, 'reason' => 'This voucher is not active.'];
        }

        if (!empty($voucher->valid_from) && $now->lt(Carbon::parse($voucher->valid_from)->startOfDay())) {
            return ['eligible' => false, 'reason' => 'This voucher is not valid yet.'];
        }

        if (!empty($voucher->valid_to) && $now->gt(Carbon::parse($voucher->valid_to)->endOfDay())) {
            return ['eligible' => false, 'reason' => 'This voucher has expired.'];
        }

        if (!empty($voucher->usage_limit_total) && $voucher->used_count >= $voucher->usage_limit_total) {
            return ['eligible' => false, 'reason' => 'This voucher has reached its total usage limit.'];
        }

        if (!empty($voucher->usage_limit_per_customer) && !empty($context['user_id'])) {
            $customer_redemptions = VoucherRedemption::where('voucher_id', $voucher->voucher_id)
                ->where('user_id', $context['user_id'])
                ->where('is_deleted', 0)
                ->count();

            if ($customer_redemptions >= $voucher->usage_limit_per_customer) {
                return ['eligible' => false, 'reason' => 'This customer has already reached the usage limit for this voucher.'];
            }
        }

        if (!empty($voucher->min_order_amount) && (float) ($context['order_amount'] ?? 0) < (float) $voucher->min_order_amount) {
            return ['eligible' => false, 'reason' => 'Order amount does not meet the minimum required for this voucher.'];
        }

        if (!empty($context['product_id']) && $voucher->products->isNotEmpty()
            && !$voucher->products->contains('product_id', $context['product_id'])
        ) {
            return ['eligible' => false, 'reason' => 'This voucher does not apply to the selected product.'];
        }

        if (!empty($context['category_id']) && $voucher->categories->isNotEmpty()
            && !$voucher->categories->contains('category_id', $context['category_id'])
        ) {
            return ['eligible' => false, 'reason' => 'This voucher does not apply to the selected category.'];
        }

        if ($voucher->users->isNotEmpty()
            && !$voucher->users->contains('id', $context['user_id'] ?? null)
        ) {
            return ['eligible' => false, 'reason' => 'This voucher does not apply to the selected customer.'];
        }

        if ($voucher->orderTypes->isNotEmpty()
            && !$voucher->orderTypes->contains('order_type_id', $context['order_type_id'] ?? null)
        ) {
            return ['eligible' => false, 'reason' => 'This voucher does not apply to the selected order type.'];
        }

        if ($voucher->branches->isNotEmpty()
            && !$voucher->branches->contains('branch_id', $context['branch_id'] ?? null)
        ) {
            return ['eligible' => false, 'reason' => 'This voucher does not apply to the selected branch.'];
        }

        return ['eligible' => true, 'reason' => null];
    }

    /**
     * Records a redemption and increments the voucher's used_count. Called by
     * OrderService::post().
     */
    public function redeem($voucher_id, $order_id, $user_id, $discount_amount)
    {
        DB::beginTransaction();

        try {
            $voucher = $this->model_voucher->find($voucher_id);

            $redemption = VoucherRedemption::create([
                'voucher_redemption_id' => generateUuid(),
                'voucher_id'            => $voucher_id,
                'order_id'              => $order_id,
                'user_id'               => $user_id,
                'discount_amount'       => $discount_amount,
                'is_deleted'            => 0,
                'createdby_id'          => Auth::id(),
                'date_created'          => now(),
            ]);

            $voucher->increment('used_count');

            DB::commit();

            return $redemption;
        } catch (\Exception $e) {

            DB::rollBack();

            throw $e;
        }
    }

    /**
     * Soft-deletes the redemption row(s) for the given order and decrements
     * used_count accordingly. Called by OrderService::void().
     */
    public function reverseRedemption($order_id)
    {
        DB::beginTransaction();

        try {
            $redemptions = VoucherRedemption::where('order_id', $order_id)
                ->where('is_deleted', 0)
                ->get();

            foreach ($redemptions as $redemption) {
                $redemption->update(['is_deleted' => 1]);

                $voucher = $this->model_voucher->getModel()::find($redemption->voucher_id);

                if ($voucher && $voucher->used_count > 0) {
                    $voucher->decrement('used_count');
                }
            }

            DB::commit();

            return true;
        } catch (\Exception $e) {

            DB::rollBack();

            throw $e;
        }
    }
}
