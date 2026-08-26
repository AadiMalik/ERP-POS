<?php

namespace App\Services\Concrete\Api;

use App\Models\Order;
use Exception;

/**
 * Storefront "My Orders" reads against the shared ERP orders table.
 * Only returns orders owned by the authenticated user for the given business.
 */
class CustomerOrderService
{
    /**
     * Map ERP order statuses onto the storefront UI status vocabulary.
     */
    public function mapStatus(?string $status): string
    {
        $status = strtolower((string) $status);

        return match ($status) {
            'draft', 'hold' => 'processing',
            'posted', 'completed', 'confirmed' => 'delivered',
            'shipped' => 'shipped',
            'delivered' => 'delivered',
            'out_for_delivery' => 'out_for_delivery',
            'cancelled', 'void' => 'cancelled',
            'returned', 'refunded' => 'returned',
            'return_requested' => 'return_requested',
            default => $status ?: 'processing',
        };
    }

    public function list(int $user_id, string $business_id, array $params = []): array
    {
        $page = max(1, (int) ($params['page'] ?? 1));
        $per_page = min(50, max(1, (int) ($params['per_page'] ?? 20)));
        $status = $params['status'] ?? null;

        $query = Order::with([
            'details.product.productImages',
            'details.productVariation',
            'payments',
            'statusHistory',
            'user',
        ])
            ->where('business_id', $business_id)
            ->where('user_id', $user_id)
            ->where('is_deleted', 0)
            ->whereNotIn('status', ['draft', 'hold'])
            ->orderByDesc('date_created');

        if ($status && $status !== 'all') {
            $erp_statuses = $this->erpStatusesForFilter($status);
            if (!empty($erp_statuses)) {
                $query->whereIn('status', $erp_statuses);
            }
        }

        $paginator = $query->paginate($per_page, ['*'], 'page', $page);

        return [
            'data' => collect($paginator->items())->map(fn ($o) => $this->mapOrder($o))->values()->all(),
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'last_page' => $paginator->lastPage(),
        ];
    }

    public function find(int $user_id, string $business_id, string $order_id): array
    {
        $order = Order::with([
            'details.product.productImages',
            'details.productVariation',
            'payments',
            'statusHistory',
            'user',
            'branch',
        ])
            ->where('business_id', $business_id)
            ->where('user_id', $user_id)
            ->where('order_id', $order_id)
            ->where('is_deleted', 0)
            ->first();

        if (!$order) {
            throw new Exception('Order not found.');
        }

        return $this->mapOrder($order, true);
    }

    protected function erpStatusesForFilter(string $filter): array
    {
        return match ($filter) {
            'processing' => ['draft', 'hold', 'processing'],
            'shipped' => ['shipped'],
            'out_for_delivery' => ['out_for_delivery'],
            'delivered' => ['posted', 'completed', 'confirmed', 'delivered'],
            'cancelled' => ['cancelled', 'void', 'returned', 'refunded', 'return_requested'],
            default => [],
        };
    }

    protected function mapOrder(Order $order, bool $detailed = false): array
    {
        $items = $order->details->map(function ($detail) {
            $images = $detail->product?->productImages?->pluck('image_url')->values()->all() ?? [];

            return [
                'productId' => $detail->product_id,
                'slug' => $detail->product->slug ?? null,
                'name' => $detail->product->name ?? 'Product',
                'image' => $images[0] ?? null,
                'variation' => $detail->productVariation->name ?? null,
                'product_variation_id' => $detail->product_variation_id,
                'qty' => (float) $detail->quantity,
                'unitPrice' => (float) $detail->final_unit_price,
                'lineTotal' => (float) $detail->total,
            ];
        })->values()->all();

        $paid = (float) ($order->paid_amount ?? 0);
        $total = (float) ($order->total ?? 0);
        $payment_status = 'pending';
        if ($paid <= 0) {
            $payment_status = 'pending';
        } elseif ($paid + 0.001 >= $total) {
            $payment_status = 'paid';
        } else {
            $payment_status = 'partially_paid';
        }

        if (in_array($this->mapStatus($order->status), ['cancelled', 'returned'], true)) {
            $payment_status = $paid > 0 ? 'refunded' : $payment_status;
        }

        $history = $order->statusHistory
            ->sortBy('date_created')
            ->map(function ($h) {
                return [
                    'status' => $this->mapStatus($h->to_status ?? $h->status ?? null),
                    'at' => optional($h->date_created)->toIso8601String(),
                ];
            })
            ->values()
            ->all();

        if (empty($history)) {
            $history[] = [
                'status' => $this->mapStatus($order->status),
                'at' => optional($order->date_created)->toIso8601String(),
            ];
        }

        $payload = [
            'id' => $order->order_id,
            'orderNumber' => $order->daily_order_id ?: $order->order_id,
            'userId' => $order->user_id,
            'email' => $order->user->email ?? null,
            'items' => $items,
            'subtotal' => (float) $order->subtotal,
            'discount' => (float) ($order->discount_amount ?? $order->discount ?? 0),
            'shipping' => 0,
            'tax' => (float) ($order->tax_amount ?? $order->tax ?? 0),
            'total' => $total,
            'paymentStatus' => $payment_status,
            'branchId' => $order->branch_id,
            'notes' => $order->notes,
            'status' => $this->mapStatus($order->status),
            'statusHistory' => $history,
            'placedAt' => optional($order->date_created ?? $order->order_date)->toIso8601String(),
            'deliveryAddress' => $order->delivery_address,
        ];

        if ($detailed) {
            $payload['payments'] = $order->payments->map(function ($p) {
                return [
                    'method' => $p->payment_method ?? $p->method ?? null,
                    'amount' => (float) ($p->amount ?? 0),
                    'status' => $p->status ?? null,
                ];
            })->values()->all();
        }

        return $payload;
    }
}
