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
        'brands',
        'variations',
        'saleTypes',
        'orderSources',
        'paymentMethods',
        'getProducts',
        'getCategories',
    ];

    // Maps the array key the caller may pass in $obj to the scope-pivot table/column
    // it belongs to. An absent key = leave the existing pivot rows untouched, an empty
    // array = explicitly clear the restriction back to "applies to all".
    protected $scope_dimensions = [
        'product_ids'       => ['table' => 'voucher_products', 'column' => 'product_id'],
        'category_ids'      => ['table' => 'voucher_categories', 'column' => 'category_id'],
        'customer_ids'      => ['table' => 'voucher_customers', 'column' => 'user_id'],
        'order_type_ids'    => ['table' => 'voucher_order_types', 'column' => 'order_type_id'],
        'branch_ids'        => ['table' => 'voucher_branches', 'column' => 'branch_id'],
        'brand_ids'         => ['table' => 'voucher_brands', 'column' => 'brand_id'],
        'variation_ids'     => ['table' => 'voucher_variations', 'column' => 'product_variation_id'],
        'sale_type_ids'     => ['table' => 'voucher_sale_types', 'column' => 'sale_type_id'],
        'order_source_ids'  => ['table' => 'voucher_order_sources', 'column' => 'order_source_id'],
        'payment_method_ids' => ['table' => 'voucher_payment_methods', 'column' => 'payment_method_id'],
        'get_product_ids'   => ['table' => 'voucher_get_products', 'column' => 'product_id'],
        'get_category_ids'  => ['table' => 'voucher_get_categories', 'column' => 'category_id'],
    ];

    public function __construct()
    {
        $this->model_voucher = new Repository(new Voucher());
    }

    /**
     * The full set of scope relations to eager-load whenever a Voucher is
     * fetched for eligibility/calculation - exposed so callers (OrderService)
     * don't have to duplicate this list.
     */
    public function relations(): array
    {
        return $this->with;
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
        $datatable = $this->model_voucher->getModel()::with(['products', 'categories', 'brands', 'variations'])
            ->where($wh)
            ->where('is_deleted', 0)
            ->orderBy('name', $orderBy);
        $datatable = applyRoleScope($datatable, $allow_roles);
        return DataTables::of($datatable)
            ->addColumn('rule', function ($item) {
                return $item->describeRule();
            })
            ->addColumn('type', function ($item) {
                return $item->promo_type === 'discount' ? ucfirst($item->type) : '-';
            })
            ->addColumn('value', function ($item) {
                if ($item->promo_type !== 'discount') {
                    return '-';
                }
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
                    <a class='btn btn-icon btn-outline-info mr-2'
                     id='viewVoucherHistory' href='javascript:void(0)'
                      data-toggle='tooltip' data-id='" . $item->voucher_id . "' data-original-title='Usage History'><i title='History' class='icon-base fa fa-history'></i></a>

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
                'promo_type',
                'value',
                'valid_from',
                'valid_to',
                'days_of_week',
                'time_start',
                'time_end',
                'usage_limit_total',
                'usage_limit_per_customer',
                'min_order_amount',
                'max_discount_amount',
                'is_exclusive',
                'buy_quantity',
                'get_quantity',
                'get_discount_percent',
                'status',
            ])->toArray();

            // days_of_week may arrive as an array of checkbox values from the form.
            if (isset($header['days_of_week']) && is_array($header['days_of_week'])) {
                $header['days_of_week'] = implode(',', $header['days_of_week']);
            }

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
     *
     * $context keys: product_id, category_id, brand_id, variation_id, user_id,
     * order_type_id, branch_id, sale_type_id, order_source_id, payment_method_ids
     * (array, checked only when present - unknowable pre-payment), order_amount,
     * now (optional Carbon, defaults to now()).
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

        if (!empty($voucher->days_of_week)) {
            $allowed_days = array_map('intval', explode(',', $voucher->days_of_week));

            if (!in_array($now->dayOfWeek, $allowed_days, true)) {
                return ['eligible' => false, 'reason' => 'This voucher is not valid on this day of the week.'];
            }
        }

        if (!empty($voucher->time_start) && !empty($voucher->time_end)) {
            $current = $now->format('H:i:s');
            $start = Carbon::parse($voucher->time_start)->format('H:i:s');
            $end = Carbon::parse($voucher->time_end)->format('H:i:s');

            // Overnight window (e.g. 22:00 - 02:00): valid outside the "gap" between end and start.
            $in_window = $start <= $end
                ? ($current >= $start && $current <= $end)
                : ($current >= $start || $current <= $end);

            if (!$in_window) {
                return ['eligible' => false, 'reason' => 'This voucher is not valid at this time of day.'];
            }
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

        if ($voucher->saleTypes->isNotEmpty()
            && !$voucher->saleTypes->contains('sale_type_id', $context['sale_type_id'] ?? null)
        ) {
            return ['eligible' => false, 'reason' => 'This voucher does not apply to the selected sale type.'];
        }

        if ($voucher->orderSources->isNotEmpty()
            && !$voucher->orderSources->contains('order_source_id', $context['order_source_id'] ?? null)
        ) {
            return ['eligible' => false, 'reason' => 'This voucher does not apply to the selected order source.'];
        }

        // Payment method is unknowable until payments are finalized - only checked
        // when the caller (OrderService::post()) actually supplies it.
        if ($voucher->paymentMethods->isNotEmpty() && array_key_exists('payment_method_ids', $context)) {
            $allowed = $voucher->paymentMethods->pluck('payment_method_id')->all();
            $used = (array) $context['payment_method_ids'];

            if (empty(array_intersect($allowed, $used))) {
                return ['eligible' => false, 'reason' => 'This voucher does not apply to the payment method used.'];
            }
        }

        if (!empty($context['brand_id']) && $voucher->brands->isNotEmpty()
            && !$voucher->brands->contains('brand_id', $context['brand_id'])
        ) {
            return ['eligible' => false, 'reason' => 'This voucher does not apply to the selected brand.'];
        }

        if (!empty($context['variation_id']) && $voucher->variations->isNotEmpty()
            && !$voucher->variations->contains('product_variation_id', $context['variation_id'])
        ) {
            return ['eligible' => false, 'reason' => 'This voucher does not apply to the selected variation.'];
        }

        return ['eligible' => true, 'reason' => null];
    }

    /**
     * True if the voucher has any item-level targeting configured (product,
     * category, brand, or variation) - determines whether calculate() bases
     * the discount on matching lines only or the whole order remainder.
     */
    public function hasItemScope(Voucher $voucher): bool
    {
        return $voucher->products->isNotEmpty()
            || $voucher->categories->isNotEmpty()
            || $voucher->brands->isNotEmpty()
            || $voucher->variations->isNotEmpty();
    }

    /**
     * Filters $lines (each an assoc array with at least product_id, category_id,
     * brand_id, product_variation_id, base) down to those matching the voucher's
     * product/category/brand/variation scope - a union match, any one configured
     * dimension matching is enough. An unscoped voucher matches every line.
     */
    public function eligibleLines(Voucher $voucher, array $lines): array
    {
        if (!$this->hasItemScope($voucher)) {
            return $lines;
        }

        $product_ids = $voucher->products->pluck('product_id')->all();
        $category_ids = $voucher->categories->pluck('category_id')->all();
        $brand_ids = $voucher->brands->pluck('brand_id')->all();
        $variation_ids = $voucher->variations->pluck('product_variation_id')->all();

        return array_values(array_filter($lines, function ($line) use ($product_ids, $category_ids, $brand_ids, $variation_ids) {
            return (!empty($product_ids) && in_array($line['product_id'] ?? null, $product_ids, true))
                || (!empty($category_ids) && in_array($line['category_id'] ?? null, $category_ids, true))
                || (!empty($brand_ids) && in_array($line['brand_id'] ?? null, $brand_ids, true))
                || (!empty($variation_ids) && in_array($line['product_variation_id'] ?? null, $variation_ids, true));
        }));
    }

    /**
     * Same union-match as eligibleLines() but for the "get" side of a
     * buy-X-get-Y voucher - falls back to the buy-scope (eligibleLines) when
     * no separate get_products/get_categories are configured.
     */
    public function eligibleGetLines(Voucher $voucher, array $lines): array
    {
        $product_ids = $voucher->getProducts->pluck('product_id')->all();
        $category_ids = $voucher->getCategories->pluck('category_id')->all();

        if (empty($product_ids) && empty($category_ids)) {
            return $this->eligibleLines($voucher, $lines);
        }

        return array_values(array_filter($lines, function ($line) use ($product_ids, $category_ids) {
            return (!empty($product_ids) && in_array($line['product_id'] ?? null, $product_ids, true))
                || (!empty($category_ids) && in_array($line['category_id'] ?? null, $category_ids, true));
        }));
    }

    /**
     * Computes the voucher's discount for a set of order lines and allocates it
     * per line. $lines items need: line_key, product_id, category_id, brand_id,
     * product_variation_id, quantity, unit_price, base (post-line-discount amount
     * for that line). $order_remaining is the whole order's post-line-discount,
     * post-Discount amount (used when the voucher has no item scope).
     *
     * @return array{discount_amount: float, allocations: array<string, array{voucher_discount_amount: float, free_quantity: float}>}
     */
    public function calculate(Voucher $voucher, array $lines, float $order_remaining): array
    {
        if (in_array($voucher->promo_type, ['bogo', 'buy_x_get_y'], true)) {
            return $this->calculateBogo($voucher, $lines);
        }

        $matching = $this->eligibleLines($voucher, $lines);
        $base = $this->hasItemScope($voucher)
            ? array_sum(array_column($matching, 'base'))
            : max($order_remaining, 0);

        if ($base <= 0) {
            return ['discount_amount' => 0, 'allocations' => []];
        }

        $raw = $voucher->type === 'percent'
            ? round($base * (float) $voucher->value / 100, 3)
            : min((float) $voucher->value, $base);

        if (!empty($voucher->max_discount_amount)) {
            $raw = min($raw, (float) $voucher->max_discount_amount);
        }

        $target_lines = $this->hasItemScope($voucher) ? $matching : $lines;

        return [
            'discount_amount' => $raw,
            'allocations' => $this->allocateProportionally($target_lines, $raw),
        ];
    }

    /**
     * BOGO / buy-X-get-Y: for every complete buy_quantity multiple of eligible
     * "buy" quantity in the cart, get_quantity units become free/discounted on
     * the "get" side, capped to what's actually in the cart. Free units are
     * taken from the cheapest-priced eligible lines first to protect margin.
     */
    protected function calculateBogo(Voucher $voucher, array $lines): array
    {
        $buy_quantity = (int) $voucher->buy_quantity;
        $get_quantity = (int) $voucher->get_quantity;

        if ($buy_quantity <= 0 || $get_quantity <= 0) {
            return ['discount_amount' => 0, 'allocations' => []];
        }

        $buy_lines = $this->eligibleLines($voucher, $lines);
        $buy_qty_total = array_sum(array_column($buy_lines, 'quantity'));
        $sets = intdiv((int) floor($buy_qty_total), $buy_quantity);

        if ($sets <= 0) {
            return ['discount_amount' => 0, 'allocations' => []];
        }

        $free_units_remaining = $sets * $get_quantity;

        $get_lines = $this->eligibleGetLines($voucher, $lines);
        usort($get_lines, fn ($a, $b) => $a['unit_price'] <=> $b['unit_price']);

        $discount_total = 0;
        $allocations = [];

        foreach ($get_lines as $line) {
            if ($free_units_remaining <= 0) {
                break;
            }

            $free_here = min($free_units_remaining, $line['quantity']);
            $line_discount = round($free_here * (float) $line['unit_price'] * (float) $voucher->get_discount_percent / 100, 3);

            $allocations[$line['line_key']] = [
                'voucher_discount_amount' => $line_discount,
                'free_quantity' => $free_here,
            ];

            $discount_total += $line_discount;
            $free_units_remaining -= $free_here;
        }

        return ['discount_amount' => round($discount_total, 3), 'allocations' => $allocations];
    }

    /**
     * Spreads $amount across $lines proportionally to each line's own base,
     * giving any rounding remainder to the last line so allocations sum exactly
     * to $amount.
     */
    protected function allocateProportionally(array $lines, float $amount): array
    {
        $total_base = array_sum(array_column($lines, 'base'));

        if ($total_base <= 0 || $amount <= 0 || empty($lines)) {
            return [];
        }

        $allocations = [];
        $allocated = 0;
        $count = count($lines);

        foreach ($lines as $i => $line) {
            $share = $i === $count - 1
                ? round($amount - $allocated, 3)
                : round($amount * ($line['base'] / $total_base), 3);

            $allocations[$line['line_key']] = [
                'voucher_discount_amount' => $share,
                'free_quantity' => 0,
            ];

            $allocated += $share;
        }

        return $allocations;
    }

    /**
     * Active vouchers matching everything checkable pre-payment (business,
     * schedule, branch, customer, order-type, order-source, sale-type) for the
     * POS "available vouchers" list - final per-item/BOGO/payment-method
     * eligibility is still resolved by isApplicable()/calculate() once a
     * specific voucher is applied to the actual cart.
     */
    public function eligibleForCart(array $context): \Illuminate\Support\Collection
    {
        $business_id = $context['business_id'] ?? Auth::user()->business_id;

        $vouchers = $this->model_voucher->getModel()::with($this->with)
            ->where('business_id', $business_id)
            ->where('status', Status::ACTIVE)
            ->where('is_deleted', 0)
            ->get();

        return $vouchers->filter(function (Voucher $voucher) use ($context) {
            return $this->isApplicable($voucher, $context)['eligible'];
        })->values();
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

    public function listActiveByBusiness(string $business_id)
    {
        return $this->model_voucher->getModel()::where('business_id', $business_id)
            ->where('is_deleted', 0)
            ->orderBy('name')
            ->get(['voucher_id', 'code', 'name']);
    }

    public function getRedemptionHistory(string $voucher_id)
    {
        return VoucherRedemption::with(['user', 'order'])
            ->where('voucher_id', $voucher_id)
            ->where('is_deleted', 0)
            ->orderByDesc('date_created')
            ->get()
            ->map(function ($row) {
                return [
                    'voucher_redemption_id' => $row->voucher_redemption_id,
                    'order_id' => $row->order_id,
                    'order_no' => optional($row->order)->daily_order_id,
                    'order_status' => optional($row->order)->status,
                    'user_id' => $row->user_id,
                    'customer' => optional($row->user)->name,
                    'customer_email' => optional($row->user)->email,
                    'discount_amount' => (float) $row->discount_amount,
                    'used_at' => optional($row->date_created)->format('d-m-Y H:i'),
                ];
            });
    }

    public function getRedemptionSummary(string $voucher_id): array
    {
        $rows = VoucherRedemption::where('voucher_id', $voucher_id)
            ->where('is_deleted', 0)
            ->get();

        return [
            'total_uses' => $rows->count(),
            'total_discount' => round((float) $rows->sum('discount_amount'), 3),
            'unique_customers' => $rows->pluck('user_id')->filter()->unique()->count(),
        ];
    }
}
