<?php

namespace App\Services\Concrete\Admin;

use App\Enums\RoleNames;
use App\Models\Branch;
use App\Models\Business;
use App\Models\Category;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseRequest;
use App\Models\Supplier;
use App\Models\TransferNote;
use App\Models\User;
use App\Models\Warehouse;
use Exception;
use Illuminate\Support\Facades\Auth;

/**
 * Centralizes the subscription limit/module checks that used to live only
 * inside the checkPackageLimit() helper (now a thin wrapper around this
 * service, see CommonFunctions.php). Keeping this as the single place that
 * maps a "limit type" to its live count avoids duplicating the check inside
 * every module service.
 */
class FeatureLimitService
{
    /**
     * @return array{status: bool, message: string}
     */
    public function check(string $type, ?Business $business = null): array
    {
        try {
            if (getRoleName() == RoleNames::SUPERADMIN) {
                return [
                    'status' => true,
                    'message' => 'Super admin bypass',
                ];
            }

            $business = $business ?? Auth::user()->business;

            if (!$business) {
                return [
                    'status' => false,
                    'message' => 'Business not found',
                ];
            }

            if (!$business->package) {
                return [
                    'status' => false,
                    'message' => 'Package not found',
                ];
            }

            $package = $business->package;
            $limits = $this->limitMap($business);

            if (!isset($limits[$type])) {
                return [
                    'status' => false,
                    'message' => 'Invalid limit type',
                ];
            }

            $limit = (int) $package->{$limits[$type]['column']};
            $count = $limits[$type]['count'];

            if ($limit == -1) {
                return [
                    'status' => true,
                    'message' => 'Unlimited access',
                ];
            }

            if ($count >= $limit) {
                return [
                    'status' => false,
                    'message' => ucfirst($type) . ' limit exceeded',
                ];
            }

            return [
                'status' => true,
                'message' => ucfirst($type) . ' limit available',
            ];
        } catch (Exception $e) {
            return [
                'status' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function checkAndAbort(string $type, ?Business $business = null): void
    {
        $result = $this->check($type, $business);

        if (!$result['status']) {
            abort(403, $result['message']);
        }
    }

    public function hasModule(string $module, ?Business $business = null): bool
    {
        $business = $business ?? Auth::user()->business;

        if (!$business || !$business->package) {
            return false;
        }

        $column = 'is_' . $module . '_enabled';

        return (bool) ($business->package->{$column} ?? false);
    }

    /**
     * customers/sales/expenses/vouchers have no backing model in this
     * codebase yet (no Customer/Sale/Expense/Voucher entity exists), so they
     * are non-fatal stubs (always "available") rather than a real count.
     * transfers is wired to a genuine live count against TransferNote.
     */
    protected function limitMap(Business $business): array
    {
        $business_id = $business->business_id;

        return [
            'branches' => [
                'column' => 'max_branches',
                'count' => Branch::where('business_id', $business_id)->where('is_deleted', 0)->count(),
            ],
            'users' => [
                'column' => 'max_users',
                'count' => User::where('business_id', $business_id)->where('is_deleted', 0)->count(),
            ],
            'customers' => [
                'column' => 'max_customers',
                'count' => 0,
            ],
            'warehouses' => [
                'column' => 'max_warehouses',
                'count' => Warehouse::where('business_id', $business_id)->where('is_deleted', 0)->count(),
            ],
            'categories' => [
                'column' => 'max_categories',
                'count' => Category::where('business_id', $business_id)->where('is_deleted', 0)->count(),
            ],
            'products' => [
                'column' => 'max_products',
                'count' => Product::where('business_id', $business_id)->where('is_deleted', 0)->count(),
            ],
            'suppliers' => [
                'column' => 'max_suppliers',
                'count' => Supplier::where('business_id', $business_id)->where('is_deleted', 0)->count(),
            ],
            'purchase_orders' => [
                'column' => 'max_purchase_orders',
                'count' => PurchaseRequest::where('business_id', $business_id)->where('is_deleted', 0)->count(),
            ],
            'purchases' => [
                'column' => 'max_purchases',
                'count' => Purchase::where('business_id', $business_id)->where('is_deleted', 0)->count(),
            ],
            'sales' => [
                'column' => 'max_sales',
                'count' => 0,
            ],
            'transfers' => [
                'column' => 'max_transfers',
                'count' => TransferNote::where('business_id', $business_id)->where('is_deleted', 0)->count(),
            ],
            'expenses' => [
                'column' => 'max_expenses',
                'count' => 0,
            ],
            'vouchers' => [
                'column' => 'max_vouchers',
                'count' => 0,
            ],
        ];
    }
}
