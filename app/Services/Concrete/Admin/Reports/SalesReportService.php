<?php

namespace App\Services\Concrete\Admin\Reports;

use App\Enums\JournalSourceTypes;
use App\Enums\RoleNames;
use App\Models\AccountingSetting;
use App\Models\Order;
use App\Services\Concrete\Admin\Reports\Accounting\AccountingLedgerQueryService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;

/**
 * Order-level Sales Report, reusing the same centralized order data every
 * other sales screen reads from (the `orders` table, posted rows only), plus
 * a reconciliation summary against the posted Sales Revenue ledger (Order.
 * subtotal is credited 1:1 to AccountingSetting::default_sale_account_id for
 * every posted order in OrderService::post() - see that method's "Credit:
 * gross sales revenue" step) so this report's totals can be checked against
 * the books rather than trusted blindly. Drafts/held/void/cancelled orders
 * never post a journal entry, so they are excluded from both sides - not a
 * reconciliation gap, an intentional scope match.
 */
class SalesReportService
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

    public function __construct(protected AccountingLedgerQueryService $ledger_query_service)
    {
    }

    protected function baseQuery(array $obj)
    {
        $business_id = $obj['business_id'] ?? Auth::user()->business_id;
        $branch_id = $obj['branch_id'] ?? null;

        $query = Order::query()
            ->where('status', 'posted')
            ->where('is_deleted', 0);

        if (!empty($business_id)) {
            $query->where('business_id', $business_id);
        }
        if (!empty($branch_id)) {
            $query->where('branch_id', $branch_id);
        }
        if (!empty($obj['warehouse_id'])) {
            $query->where('warehouse_id', $obj['warehouse_id']);
        }
        if (!empty($obj['start_date'])) {
            $query->where('order_date', '>=', Carbon::parse($obj['start_date'])->startOfDay());
        }
        if (!empty($obj['end_date'])) {
            $query->where('order_date', '<=', Carbon::parse($obj['end_date'])->endOfDay());
        }

        return applyRoleScope($query, $this->allow_roles);
    }

    public function build(array $obj)
    {
        return $this->baseQuery($obj)
            ->with(['user', 'warehouse'])
            ->orderBy('order_date')
            ->get();
    }

    public function getData(array $obj)
    {
        $rows = $this->baseQuery($obj)->with(['customer', 'warehouse']);

        return DataTables::of($rows)
            ->addColumn('order_no', fn ($row) => $row->daily_order_id)
            ->addColumn('order_date', fn ($row) => optional($row->order_date)->format('d-m-Y H:i'))
            ->addColumn('customer', fn ($row) => optional($row->user)->name ?? 'Walk-in')
            ->addColumn('warehouse', fn ($row) => optional($row->warehouse)->name ?? '')
            ->editColumn('subtotal', fn ($row) => currency($row->subtotal))
            ->editColumn('discount_amount', fn ($row) => currency($row->discount_amount))
            ->editColumn('voucher_discount_amount', fn ($row) => currency($row->voucher_discount_amount))
            ->editColumn('tax_amount', fn ($row) => currency($row->tax_amount))
            ->editColumn('total', fn ($row) => currency($row->total))
            ->editColumn('paid_amount', fn ($row) => currency($row->paid_amount))
            ->rawColumns(['order_no', 'order_date', 'customer', 'warehouse'])
            ->make(true);
    }

    /**
     * Ties this report's order-level totals out against what was actually
     * posted to the Sales Revenue account for the same business/branch/date
     * range - the two are computed from entirely independent code paths
     * (this from `orders`, the ledger side from `journal_entry_details`), so
     * a non-zero variance here is a real signal something posted
     * inconsistently, not an expected rounding artifact.
     */
    public function reconcile(array $obj): array
    {
        $business_id = $obj['business_id'] ?? Auth::user()->business_id;
        $branch_id = $obj['branch_id'] ?? null;

        $orders = $this->baseQuery($obj)->get(['subtotal', 'discount_amount', 'tax_amount', 'total']);

        $order_subtotal = (float) $orders->sum('subtotal');
        $order_discount = (float) $orders->sum('discount_amount');
        $order_tax = (float) $orders->sum('tax_amount');
        $order_total = (float) $orders->sum('total');

        $accounting_setting = AccountingSetting::where('business_id', $business_id)->first();
        $ledger_revenue = 0.0;

        if ($accounting_setting && !empty($accounting_setting->default_sale_account_id)) {
            $filters = [
                'business_id' => $business_id,
                'branch_id'   => $branch_id,
                'account_id'  => $accounting_setting->default_sale_account_id,
                'source_type' => JournalSourceTypes::POS_SALE,
                'allow_roles' => $this->allow_roles,
            ];

            $from = !empty($obj['start_date']) ? Carbon::parse($obj['start_date'])->startOfDay() : null;
            $to = !empty($obj['end_date']) ? Carbon::parse($obj['end_date'])->endOfDay() : null;

            $totals = $this->ledger_query_service->periodMovements($filters, $from, $to);
            $ledger_revenue = array_sum(array_column($totals, 'credit')) - array_sum(array_column($totals, 'debit'));
        }

        return [
            'order_count'      => $orders->count(),
            'order_subtotal'   => round($order_subtotal, 2),
            'order_discount'   => round($order_discount, 2),
            'order_tax'        => round($order_tax, 2),
            'order_total'      => round($order_total, 2),
            'ledger_revenue'   => round($ledger_revenue, 2),
            'variance'         => round($order_subtotal - $ledger_revenue, 2),
            'reconciled'       => abs($order_subtotal - $ledger_revenue) < 0.01,
        ];
    }
}
