<?php

namespace App\Services\Concrete\Admin\Reports;

use App\Enums\RoleNames;
use App\Models\VoucherRedemption;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;

class VoucherUsageReportService
{
    protected array $allow_roles = [
        RoleNames::SUPERADMIN,
        RoleNames::BUSINESSADMIN,
        RoleNames::BRANCHADMIN,
        RoleNames::SALEMANAGER,
        RoleNames::FINANCEMANAGER,
        RoleNames::ACCOUNTANT,
        RoleNames::REPORTINGANALYST,
    ];

    protected function baseQuery(array $obj)
    {
        $business_id = $obj['business_id'] ?? Auth::user()->business_id;
        $voucher_id = $obj['voucher_id'] ?? null;
        $user_id = $obj['user_id'] ?? null;

        $query = VoucherRedemption::query()
            ->with(['voucher', 'user', 'order'])
            ->join('vouchers', 'voucher_redemptions.voucher_id', '=', 'vouchers.voucher_id')
            ->where('voucher_redemptions.is_deleted', 0)
            ->select('voucher_redemptions.*');

        if (!empty($business_id)) {
            $query->where('vouchers.business_id', $business_id);
        }

        if (!empty($voucher_id)) {
            $query->where('voucher_id', $voucher_id);
        }
        if (!empty($user_id)) {
            $query->where('user_id', $user_id);
        }
        if (!empty($obj['start_date'])) {
            $query->where('voucher_redemptions.date_created', '>=', Carbon::parse($obj['start_date'])->startOfDay());
        }
        if (!empty($obj['end_date'])) {
            $query->where('voucher_redemptions.date_created', '<=', Carbon::parse($obj['end_date'])->endOfDay());
        }

        return applyRoleScope($query, $this->allow_roles, 'vouchers.business_id');
    }

    public function getData(array $obj)
    {
        $rows = $this->baseQuery($obj)->orderByDesc('date_created');

        return DataTables::of($rows)
            ->addColumn('voucher_code', fn ($row) => optional($row->voucher)->code ?? '-')
            ->addColumn('voucher_name', fn ($row) => optional($row->voucher)->name ?? '-')
            ->addColumn('customer', fn ($row) => optional($row->user)->name ?? 'Walk-in')
            ->addColumn('customer_email', fn ($row) => optional($row->user)->email ?? '-')
            ->addColumn('order_no', fn ($row) => optional($row->order)->daily_order_id ?? '-')
            ->addColumn('order_status', fn ($row) => optional($row->order)->status ?? '-')
            ->addColumn('used_at', fn ($row) => optional($row->date_created)->format('d-m-Y H:i'))
            ->editColumn('discount_amount', fn ($row) => currency($row->discount_amount))
            ->rawColumns(['voucher_code', 'customer', 'order_no'])
            ->make(true);
    }

    public function summary(array $obj): array
    {
        $rows = $this->baseQuery($obj)->get();

        $by_voucher = $rows->groupBy('voucher_id')->map(function ($group) {
            $voucher = $group->first()->voucher;

            return [
                'voucher_id' => $voucher->voucher_id ?? null,
                'code' => $voucher->code ?? '-',
                'name' => $voucher->name ?? '-',
                'usage_count' => $group->count(),
                'total_discount' => round((float) $group->sum('discount_amount'), 3),
                'unique_customers' => $group->pluck('user_id')->filter()->unique()->count(),
            ];
        })->values();

        return [
            'total_redemptions' => $rows->count(),
            'total_discount' => round((float) $rows->sum('discount_amount'), 3),
            'unique_vouchers' => $rows->pluck('voucher_id')->unique()->count(),
            'unique_customers' => $rows->pluck('user_id')->filter()->unique()->count(),
            'by_voucher' => $by_voucher,
        ];
    }
}
