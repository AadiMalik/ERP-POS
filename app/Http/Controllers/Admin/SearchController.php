<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Message;
use App\Enums\RoleNames;
use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Concrete\Admin\AccessControlService;
use App\Traits\ResponseAPI;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Global header search - aggregates a lightweight, permission-scoped preview
 * of matching records across modules. Each module is gated by that module's
 * own existing `.view` (or equivalent) permission from PermissionRegistry,
 * combined with the business's package/module access via AccessControlService
 * - search itself is not a separate permission, it only ever surfaces
 * records the current user could already open directly.
 */
class SearchController extends Controller
{
    use ResponseAPI;

    const RESULTS_PER_MODULE = 5;

    public function __construct(private AccessControlService $access_control_service)
    {
    }

    public function globalSearch(Request $request)
    {
        $term = trim((string) $request->query('q', ''));

        if (mb_strlen($term) < 2) {
            return $this->success(Message::SUCCESS, ['groups' => [], 'total' => 0]);
        }

        $groups = [];

        if ($this->access_control_service->allows('pos.access')) {
            $groups[] = $this->searchOrders($term);
            $groups[] = $this->searchOrderPayments($term);
        }

        if ($this->access_control_service->allows('user.view')) {
            $groups[] = $this->searchUsers($term, true);  // customers
            $groups[] = $this->searchUsers($term, false); // employees / staff
        }

        if ($this->access_control_service->allows('supplier.view')) {
            $groups[] = $this->searchSuppliers($term);
        }

        if ($this->access_control_service->allows('product.view')) {
            $groups[] = $this->searchProducts($term);
        }

        if ($this->access_control_service->allows('purchase.view')) {
            $groups[] = $this->searchPurchases($term);
        }

        if ($this->access_control_service->allows('warehouse.view')) {
            $groups[] = $this->searchWarehouses($term);
        }

        if ($this->access_control_service->allows('supplier-payment.view')) {
            $groups[] = $this->searchSupplierPayments($term);
        }

        if ($this->access_control_service->allows('expense.view')) {
            $groups[] = $this->searchExpenses($term);
        }

        $groups = array_values(array_filter($groups, fn ($group) => $group['count'] > 0));
        $total = array_sum(array_column($groups, 'count'));

        return $this->success(Message::SUCCESS, ['groups' => $groups, 'total' => $total]);
    }

    /**
     * Applies the same business/branch role scoping every other module list
     * uses (see CommonFunctions::applyRoleScope). $branchColumn = null is for
     * business-wide entities with no branch column (e.g. products), where
     * applying the branch filter would error - falls back to business_id only.
     */
    private function scopeToBusiness($query, string $businessColumn = 'business_id', ?string $branchColumn = 'branch_id')
    {
        if ($branchColumn === null) {
            if (getRoleName() != RoleNames::SUPERADMIN && Auth::user()->business_id) {
                $query->where($businessColumn, Auth::user()->business_id);
            }
            return $query;
        }

        return applyRoleScope($query, [], $businessColumn, $branchColumn);
    }

    /**
     * Runs exactly two queries against $query - one count(), one limited
     * get() mapped through $mapper - and shapes the result into the group
     * structure the frontend renders. Every search*() method below funnels
     * through this so a keystroke never issues more than 2 queries/module.
     */
    private function buildGroup(string $key, string $label, string $icon, $query, callable $mapper): array
    {
        $total = (clone $query)->count();
        $results = $mapper((clone $query)->limit(self::RESULTS_PER_MODULE)->get());

        return [
            'key' => $key,
            'label' => $label,
            'icon' => $icon,
            'count' => $total,
            'more' => max(0, $total - count($results)),
            'results' => array_values($results),
        ];
    }

    private function searchOrders(string $term): array
    {
        $query = Order::query()
            ->where('is_deleted', 0)
            ->where(function ($q) use ($term) {
                $q->where('daily_order_id', 'like', "%{$term}%")
                    ->orWhere('fbr_invoice_number', 'like', "%{$term}%")
                    ->orWhere('pra_invoice_number', 'like', "%{$term}%");
            })
            ->orderByDesc('date_created');
        $this->scopeToBusiness($query);

        return $this->buildGroup('order', 'Orders / Invoices', 'fa-receipt', $query, function ($orders) {
            return $orders->map(function ($order) {
                return [
                    'title' => 'Order #' . ($order->daily_order_id ?? $order->order_id),
                    'subtitle' => trim(($order->fbr_invoice_number ? 'FBR: ' . $order->fbr_invoice_number . ' · ' : '') . ucfirst($order->status ?? '') . ' · ' . number_format((float) $order->total, 2)),
                    'url' => route('order.show', $order->order_id),
                ];
            })->all();
        });
    }

    private function searchOrderPayments(string $term): array
    {
        $query = OrderPayment::query()
            ->where('is_deleted', 0)
            ->where('reference_no', 'like', "%{$term}%")
            ->with('order:order_id,daily_order_id,business_id,branch_id');

        // OrderPayment has no business_id/branch_id of its own - scope via the parent order.
        if (getRoleName() != RoleNames::SUPERADMIN) {
            $query->whereHas('order', function ($q) {
                $this->scopeToBusiness($q);
            });
        }

        return $this->buildGroup('order-payment', 'Order Payments', 'fa-cash-register', $query, function ($payments) {
            return $payments->filter(fn ($payment) => $payment->order)
                ->map(function ($payment) {
                    return [
                        'title' => 'Payment Ref: ' . $payment->reference_no,
                        'subtitle' => 'Order #' . ($payment->order->daily_order_id ?? '') . ' · ' . number_format((float) $payment->amount, 2),
                        'url' => route('order.show', $payment->order->order_id),
                    ];
                })->all();
        });
    }

    private function searchUsers(string $term, bool $customers): array
    {
        $query = User::query()
            ->where('is_deleted', 0)
            ->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%")
                    ->orWhereHas('customerProfiles', function ($cq) use ($term) {
                        $cq->where('code', 'like', "%{$term}%")
                            ->orWhere('company_name', 'like', "%{$term}%")
                            ->orWhere('contact_person', 'like', "%{$term}%");
                    });
            })
            ->when($customers, fn ($q) => $q->role(RoleNames::USER))
            ->when(!$customers, fn ($q) => $q->whereDoesntHave('roles', fn ($rq) => $rq->where('name', RoleNames::USER)))
            ->with('customerProfiles:customer_profile_id,user_id,code,company_name');
        $this->scopeToBusiness($query);

        return $this->buildGroup(
            $customers ? 'customer' : 'employee',
            $customers ? 'Customers' : 'Employees',
            $customers ? 'fa-user' : 'fa-id-badge',
            $query,
            function ($users) {
                return $users->map(function ($user) {
                    $profile = $user->customerProfiles->first();
                    return [
                        'title' => $profile->company_name ?? $user->name,
                        'subtitle' => trim(($profile->code ?? '') . ' ' . ($user->phone ?? $user->email ?? '')),
                        'url' => route('users.edit', $user->id),
                    ];
                })->all();
            }
        );
    }

    private function searchSuppliers(string $term): array
    {
        $query = Supplier::query()
            ->where('is_deleted', 0)
            ->where(function ($q) use ($term) {
                $q->where('code', 'like', "%{$term}%")
                    ->orWhere('name', 'like', "%{$term}%")
                    ->orWhere('company_name', 'like', "%{$term}%")
                    ->orWhere('contact_person', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%");
            });
        $this->scopeToBusiness($query);

        return $this->buildGroup('supplier', 'Suppliers', 'fa-truck', $query, function ($suppliers) {
            return $suppliers->map(function ($supplier) {
                return [
                    'title' => $supplier->name,
                    'subtitle' => trim(($supplier->code ?? '') . ' · ' . ($supplier->company_name ?? '')),
                    'url' => route('supplier.edit', $supplier->supplier_id),
                ];
            })->all();
        });
    }

    private function searchProducts(string $term): array
    {
        $query = Product::query()
            ->where('is_deleted', 0)
            ->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhereHas('productVariations', function ($vq) use ($term) {
                        $vq->where('sku', 'like', "%{$term}%")
                            ->orWhere('barcode', 'like', "%{$term}%")
                            ->orWhere('name', 'like', "%{$term}%");
                    });
            })
            ->with('productVariations:product_variation_id,product_id,sku');
        $this->scopeToBusiness($query, 'business_id', null);

        return $this->buildGroup('product', 'Products', 'fa-box', $query, function ($products) {
            return $products->map(function ($product) {
                $variation = $product->productVariations->first();
                return [
                    'title' => $product->name,
                    'subtitle' => $variation && $variation->sku ? 'SKU: ' . $variation->sku : '',
                    'url' => route('product.edit', $product->product_id),
                ];
            })->all();
        });
    }

    private function searchPurchases(string $term): array
    {
        $query = Purchase::query()
            ->where('is_deleted', 0)
            ->where(function ($q) use ($term) {
                $q->where('purchase_no', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%")
                    ->orWhereHas('supplier', fn ($sq) => $sq->where('name', 'like', "%{$term}%"));
            })
            ->with('supplier:supplier_id,name');
        $this->scopeToBusiness($query);

        return $this->buildGroup('purchase', 'Purchases', 'fa-shopping-cart', $query, function ($purchases) {
            return $purchases->map(function ($purchase) {
                return [
                    'title' => $purchase->purchase_no,
                    'subtitle' => trim(($purchase->supplier->name ?? '') . ' · ' . number_format((float) $purchase->total, 2)),
                    'url' => route('purchase.edit', $purchase->purchase_id),
                ];
            })->all();
        });
    }

    private function searchWarehouses(string $term): array
    {
        $query = Warehouse::query()
            ->where('is_deleted', 0)
            ->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('code', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%");
            });
        $this->scopeToBusiness($query);

        return $this->buildGroup('warehouse', 'Warehouses', 'fa-warehouse', $query, function ($warehouses) {
            return $warehouses->map(function ($warehouse) {
                return [
                    'title' => $warehouse->name,
                    'subtitle' => $warehouse->code ?? '',
                    'url' => route('warehouse.edit', $warehouse->warehouse_id),
                ];
            })->all();
        });
    }

    private function searchSupplierPayments(string $term): array
    {
        $query = SupplierPayment::query()
            ->where('is_deleted', 0)
            ->where(function ($q) use ($term) {
                $q->where('payment_no', 'like', "%{$term}%")
                    ->orWhere('reference_no', 'like', "%{$term}%")
                    ->orWhere('remarks', 'like', "%{$term}%");
            })
            ->with('supplier:supplier_id,name');
        $this->scopeToBusiness($query);

        return $this->buildGroup('supplier-payment', 'Supplier Payments', 'fa-money-bill-wave', $query, function ($payments) {
            return $payments->map(function ($payment) {
                return [
                    'title' => $payment->payment_no,
                    'subtitle' => trim(($payment->supplier->name ?? '') . ' · ' . number_format((float) $payment->amount, 2)),
                    'url' => route('supplier-payment.edit', $payment->supplier_payment_id),
                ];
            })->all();
        });
    }

    private function searchExpenses(string $term): array
    {
        $query = Expense::query()
            ->where('is_deleted', 0)
            ->where(function ($q) use ($term) {
                $q->where('expense_no', 'like', "%{$term}%")
                    ->orWhere('reference_no', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%");
            });
        $this->scopeToBusiness($query);

        return $this->buildGroup('expense', 'Expenses', 'fa-file-invoice-dollar', $query, function ($expenses) {
            return $expenses->map(function ($expense) {
                return [
                    'title' => $expense->expense_no,
                    'subtitle' => trim(($expense->description ?? '') . ' · ' . number_format((float) $expense->amount, 2)),
                    'url' => route('expense.edit', $expense->expense_id),
                ];
            })->all();
        });
    }
}
