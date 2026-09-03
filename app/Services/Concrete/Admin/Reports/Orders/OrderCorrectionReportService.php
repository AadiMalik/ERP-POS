<?php

namespace App\Services\Concrete\Admin\Reports\Orders;

use App\Models\ActivityLog;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductVariation;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Yajra\DataTables\DataTables;

/**
 * Same-day POS order correction history, read off the Activity Log rows
 * OrderService::correct() writes (module 'order', action 'corrected'). Each
 * row already carries the full before/after order snapshot in
 * old_values/new_values (see OrderService::snapshotOrderForAudit()), so this
 * report needs no extra table - it is purely a manager-facing view over the
 * existing audit trail. Filters use activity_logs columns directly (not
 * BaseOrderReportService::applyCommonFilters(), which targets the orders
 * table's column set).
 */
class OrderCorrectionReportService extends BaseOrderReportService
{
    public function build(array $obj): Collection
    {
        $query = ActivityLog::query()
            ->where('module', 'order')
            ->where('action', 'corrected');

        $business_id = $obj['business_id'] ?? auth()->user()->business_id;

        if (!empty($business_id)) {
            $query->where('business_id', $business_id);
        }
        if (!empty($obj['branch_id'])) {
            $query->where('branch_id', $obj['branch_id']);
        }
        if (!empty($obj['causer_id'])) {
            $query->where('causer_id', $obj['causer_id']);
        }
        if (!empty($obj['start_date'])) {
            $query->where('date_created', '>=', Carbon::parse($obj['start_date'])->startOfDay());
        }
        if (!empty($obj['end_date'])) {
            $query->where('date_created', '<=', Carbon::parse($obj['end_date'])->endOfDay());
        }

        applyRoleScope($query, $this->allow_roles, 'business_id', 'branch_id');

        $rows = $query->with(['causer', 'branch', 'business'])
            ->orderBy('date_created', 'desc')
            ->get();

        $orders = Order::whereIn('order_id', $rows->pluck('record_id')->unique()->filter())
            ->get()
            ->keyBy('order_id');

        [$product_names, $variation_names, $payment_method_names] = $this->resolveLookups($rows);

        return $rows->map(function (ActivityLog $log) use ($orders, $product_names, $variation_names, $payment_method_names) {
            $log->setAttribute('order', $orders->get($log->record_id));
            $log->setAttribute('old_values', $this->enrichSnapshot($log->old_values, $product_names, $variation_names, $payment_method_names));
            $log->setAttribute('new_values', $this->enrichSnapshot($log->new_values, $product_names, $variation_names, $payment_method_names));

            return $log;
        });
    }

    /**
     * @return array{0: \Illuminate\Support\Collection, 1: \Illuminate\Support\Collection, 2: \Illuminate\Support\Collection}
     */
    protected function resolveLookups(Collection $rows): array
    {
        $product_ids = collect();
        $variation_ids = collect();
        $payment_method_ids = collect();

        foreach ($rows as $log) {
            foreach ([$log->old_values, $log->new_values] as $snapshot) {
                foreach (($snapshot['details'] ?? []) as $line) {
                    $product_ids->push($line['product_id'] ?? null);
                    $variation_ids->push($line['product_variation_id'] ?? null);
                }
                foreach (($snapshot['payments'] ?? []) as $payment) {
                    $payment_method_ids->push($payment['payment_method_id'] ?? null);
                }
            }
        }

        return [
            Product::whereIn('product_id', $product_ids->unique()->filter())->pluck('name', 'product_id'),
            ProductVariation::whereIn('product_variation_id', $variation_ids->unique()->filter())->pluck('name', 'product_variation_id'),
            PaymentMethod::whereIn('payment_method_id', $payment_method_ids->unique()->filter())->pluck('name', 'payment_method_id'),
        ];
    }

    protected function enrichSnapshot(?array $snapshot, Collection $product_names, Collection $variation_names, Collection $payment_method_names): ?array
    {
        if (empty($snapshot)) {
            return $snapshot;
        }

        if (!empty($snapshot['details'])) {
            foreach ($snapshot['details'] as &$line) {
                $line['product_name'] = $product_names->get($line['product_id'] ?? null, '');
                $line['product_variation_name'] = $variation_names->get($line['product_variation_id'] ?? null, '');
            }
            unset($line);
        }

        if (!empty($snapshot['payments'])) {
            foreach ($snapshot['payments'] as &$payment) {
                $payment['payment_method_name'] = $payment_method_names->get($payment['payment_method_id'] ?? null, '');
            }
            unset($payment);
        }

        return $snapshot;
    }

    public function getData(array $obj)
    {
        $rows = $this->build($obj);

        $totals = [
            'grand_corrections' => $rows->count(),
            'grand_old_total' => currency(round($rows->sum(fn ($row) => (float) ($row->old_values['total'] ?? 0)), 2)),
            'grand_new_total' => currency(round($rows->sum(fn ($row) => (float) ($row->new_values['total'] ?? 0)), 2)),
        ];

        return DataTables::of($rows)
            ->addColumn('date_created', fn ($row) => localDateTime($row->date_created))
            ->addColumn('order_no', fn ($row) => optional($row->order)->daily_order_id ?? $row->record_id)
            ->addColumn('order_id', fn ($row) => $row->record_id)
            ->addColumn('branch', fn ($row) => $row->branch->name ?? '')
            ->addColumn('corrected_by', fn ($row) => $row->causer->name ?? 'System')
            ->addColumn('reason', fn ($row) => $row->new_values['reason'] ?? '-')
            ->addColumn('old_total', fn ($row) => currency($row->old_values['total'] ?? 0))
            ->addColumn('new_total', fn ($row) => currency($row->new_values['total'] ?? 0))
            ->addColumn('difference', function ($row) {
                $diff = (float) ($row->new_values['total'] ?? 0) - (float) ($row->old_values['total'] ?? 0);

                return currency(round($diff, 2));
            })
            ->addColumn('old_values', fn ($row) => $row->old_values)
            ->addColumn('new_values', fn ($row) => $row->new_values)
            ->addColumn('action', function ($row) {
                $buttons = '<a href="javascript:void(0);" data-action="view-diff" class="btn btn-sm btn-outline-secondary"><i class="fa fa-code-compare"></i> View Changes</a>';

                if (!empty($row->record_id)) {
                    $buttons .= ' <a href="' . route('order.print', $row->record_id) . '" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fa fa-eye"></i> View Order</a>';
                }

                return $buttons;
            })
            ->rawColumns(['order_no', 'branch', 'corrected_by', 'reason', 'action'])
            ->with($totals)
            ->make(true);
    }
}
