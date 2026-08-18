<?php

namespace App\Services\Concrete\Admin;

use App\Enums\Filter;
use App\Enums\RoleNames;
use App\Models\PosRegisterCashMovement;
use App\Models\PosRegisterSession;
use App\Models\PosSetting;
use App\Repository\Repository;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Yajra\DataTables\DataTables;

class PosRegisterSessionService
{
    protected $model_pos_register_session;

    protected $pos_register_service;

    protected $expense_service;

    public function __construct(PosRegisterService $pos_register_service, ExpenseService $expense_service)
    {
        $this->model_pos_register_session = new Repository(new PosRegisterSession());
        $this->pos_register_service = $pos_register_service;
        $this->expense_service = $expense_service;
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
        $datatable = $this->model_pos_register_session->getModel()::with(['register', 'branch', 'cashier'])
            ->where($wh)
            ->where('is_deleted', 0)
            ->orderBy('opening_datetime', $orderBy);
        $datatable = applyRoleScope($datatable, $allow_roles);
        return DataTables::of($datatable)
            ->addColumn('register', function ($item) {
                return $item->register->name ?? '-';
            })
            ->addColumn('branch', function ($item) {
                return $item->branch->name ?? '-';
            })
            ->addColumn('cashier', function ($item) {
                return $item->cashier->name ?? '-';
            })
            ->addColumn('opening_datetime', function ($item) {
                return $item->opening_datetime ? localDateTime($item->opening_datetime) : '-';
            })
            ->addColumn('closing_datetime', function ($item) {
                return $item->closing_datetime ? localDateTime($item->closing_datetime) : '-';
            })
            ->addColumn('status', function ($item) {
                $badge = $item->status == 'open' ? 'bg-label-success' : 'bg-label-secondary';
                return '<span class="badge ' . $badge . '">' . ucfirst($item->status) . '</span>';
            })
            ->rawColumns(['status'])
            ->make(true);
    }

    /**
     * Opens a new register session for a cashier.
     *
     * Guards:
     * 1. The target register must not already have an open session.
     * 2. The cashier must not already have an open session on ANY OTHER register
     *    (one active session per cashier at a time).
     */
    public function open($obj)
    {
        $cashier_id = $obj['cashier_id'] ?? Auth::id();

        // Always resolve (and validate) the register through the service - this
        // enforces the manual-mode business/branch/status checks even when a
        // pos_register_id was explicitly passed in.
        $register = $this->pos_register_service->resolveRegisterForUser(
            $obj['business_id'] ?? Auth::user()->business_id,
            $obj['branch_id'] ?? Auth::user()->branch_id,
            Auth::user(),
            $obj['pos_register_id'] ?? null
        );

        if (empty($register)) {
            throw new Exception('Unable to resolve a register for this session.');
        }

        $register_open = PosRegisterSession::where('pos_register_id', $register->pos_register_id)
            ->where('status', 'open')
            ->where('is_deleted', 0)
            ->exists();

        if ($register_open) {
            throw new Exception('This register already has an active session.');
        }

        $cashier_open = PosRegisterSession::where('cashier_id', $cashier_id)
            ->where('status', 'open')
            ->where('is_deleted', 0)
            ->exists();

        if ($cashier_open) {
            throw new Exception('You already have an active register session open.');
        }

        $session = $this->model_pos_register_session->create([
            'pos_register_session_id' => generateUuid(),
            'pos_register_id' => $register->pos_register_id,
            'business_id' => $register->business_id,
            'branch_id' => $register->branch_id,
            'cashier_id' => $cashier_id,
            'opening_datetime' => now(),
            'opening_cash' => $obj['opening_cash'] ?? 0,
            'opening_notes' => $obj['opening_notes'] ?? null,
            'status' => 'open',
            'is_deleted' => 0,
            'createdby_id' => Auth::id(),
            'date_created' => now(),
        ]);

        return $session;
    }

    /**
     * Closes an open register session, computing expected cash vs the counted
     * actual cash.
     *
     // TODO: pos.register.close permission check - the controller should verify
     // Auth::id() == $session->cashier_id OR the acting user holds the
     // pos.register.close permission before calling this, per this codebase's
     // convention of doing authorization in controllers rather than services.
     */
    public function close($obj)
    {
        DB::beginTransaction();

        try {
            $session = $this->model_pos_register_session->find($obj['pos_register_session_id']);

            if ($session->status != 'open') {
                throw new Exception('This session is not open.');
            }

            $summary = $this->getSummary($obj['pos_register_session_id']);
            $expected_cash = $summary['expected_cash'];
            $actual_cash = $obj['actual_cash'] ?? 0;
            $cash_difference = $actual_cash - $expected_cash;

            $session->update([
                'closing_datetime' => now(),
                'expected_cash' => $expected_cash,
                'actual_cash' => $actual_cash,
                'cash_difference' => $cash_difference,
                'closing_notes' => $obj['closing_notes'] ?? null,
                'status' => 'closed',
                'updatedby_id' => Auth::id(),
                'date_updated' => now(),
            ]);

            DB::commit();

            return $this->model_pos_register_session->find($obj['pos_register_session_id']);
        } catch (Exception $e) {

            DB::rollBack();

            throw $e;
        }
    }

    /**
     * Cash-reconciliation + full sales-summary for a register session. Reads
     * `orders` joined to `order_payments` exactly once (never per payment
     * method separately) so a multi-payment order is never double-counted
     * across the totals below.
     */
    public function getSummary($pos_register_session_id)
    {
        $session = $this->model_pos_register_session->find($pos_register_session_id);

        $cash_sales = 0;
        $cash_refunds = 0;
        $payment_method_totals = collect();
        $order_source_totals = collect();
        $total_orders = 0;
        $total_sales_amount = 0;
        $total_tax = 0;
        $total_discount = 0;
        $total_paid_amount = 0;
        $total_change_amount = 0;
        $credit_order_count = 0;
        $credit_amount = 0;
        $cash_order_count = 0;
        $void_amount = 0;
        $returned_amount = 0;

        if (Schema::hasTable('order_payments') && Schema::hasTable('orders') && Schema::hasTable('payment_methods')) {
            $posted_orders = DB::table('orders')
                ->where('register_session_id', $pos_register_session_id)
                ->where('status', 'posted')
                ->where('is_deleted', 0)
                ->select('order_id', 'total', 'tax_amount', 'discount_amount', 'paid_amount', 'change_amount', 'order_source_id')
                ->get();

            $total_orders = $posted_orders->count();
            $total_sales_amount = (float) $posted_orders->sum('total');
            $total_tax = (float) $posted_orders->sum('tax_amount');
            $total_discount = (float) $posted_orders->sum('discount_amount');
            $total_paid_amount = (float) $posted_orders->sum('paid_amount');
            $total_change_amount = (float) $posted_orders->sum('change_amount');

            $void_amount = (float) DB::table('orders')
                ->where('register_session_id', $pos_register_session_id)
                ->where('status', 'void')
                ->where('is_deleted', 0)
                ->sum('total');

            $returned_amount = (float) DB::table('orders')
                ->where('register_session_id', $pos_register_session_id)
                ->where('status', 'returned')
                ->where('is_deleted', 0)
                ->sum('total');

            $order_source_totals = DB::table('orders')
                ->leftJoin('order_sources', 'order_sources.order_source_id', '=', 'orders.order_source_id')
                ->where('orders.register_session_id', $pos_register_session_id)
                ->where('orders.status', 'posted')
                ->where('orders.is_deleted', 0)
                ->groupBy('orders.order_source_id', 'order_sources.name')
                ->select(
                    'orders.order_source_id',
                    DB::raw('COALESCE(order_sources.name, \'Unknown\') as name'),
                    DB::raw('COUNT(*) as order_count'),
                    DB::raw('SUM(orders.total) as total')
                )
                ->get();

            $payment_rows = DB::table('order_payments')
                ->join('payment_methods', 'payment_methods.payment_method_id', '=', 'order_payments.payment_method_id')
                ->join('orders', 'orders.order_id', '=', 'order_payments.order_id')
                ->where('orders.register_session_id', $pos_register_session_id)
                ->where('orders.status', 'posted')
                ->where('order_payments.is_deleted', 0)
                ->select('payment_methods.payment_method_id', 'payment_methods.name', 'payment_methods.type', 'order_payments.amount', 'order_payments.order_id')
                ->get();

            $payment_method_totals = $payment_rows->groupBy('payment_method_id')->map(function ($rows) {
                return [
                    'payment_method_id' => $rows->first()->payment_method_id,
                    'name' => $rows->first()->name,
                    'type' => $rows->first()->type,
                    'order_count' => $rows->pluck('order_id')->unique()->count(),
                    'total' => (float) $rows->sum('amount'),
                ];
            })->values();

            $cash_rows = $payment_rows->where('type', 'cash');
            $cash_sales = (float) $cash_rows->sum('amount');
            $cash_order_count = $cash_rows->pluck('order_id')->unique()->count();

            $credit_rows = $payment_rows->where('type', 'credit');
            $credit_amount = (float) $credit_rows->sum('amount');
            $credit_order_count = $credit_rows->pluck('order_id')->unique()->count();

            // No refund mechanism exists yet - kept at 0 until a later phase adds it.
            $cash_refunds = 0;
        }

        $cash_movements_in = PosRegisterCashMovement::where('pos_register_session_id', $pos_register_session_id)
            ->where('type', 'in')
            ->where('is_deleted', 0)
            ->sum('amount');

        $cash_movements_out = PosRegisterCashMovement::where('pos_register_session_id', $pos_register_session_id)
            ->where('type', 'out')
            ->where('is_deleted', 0)
            ->sum('amount');

        $expense_totals = Schema::hasTable('expenses')
            ? $this->expense_service->getSessionTotals($pos_register_session_id)
            : ['total_expenses' => 0, 'cash_expenses' => 0, 'expense_count' => 0];

        $opening_cash = $session->opening_cash ?? 0;
        // Change handed back is a cash-drawer outflow that isn't part of any
        // order_payments amount (order_payments/cash_sales already nets it
        // out per order) - it is not subtracted again here. Only posted
        // cash-method expenses reduce the till - bank/cheque/online expenses
        // never touched the drawer.
        $expected_cash = $opening_cash + $cash_sales - $cash_refunds + $cash_movements_in - $cash_movements_out - $expense_totals['cash_expenses'];

        return [
            'opening_cash' => $opening_cash,
            'cash_sales' => $cash_sales,
            'cash_refunds' => $cash_refunds,
            'cash_movements_in' => $cash_movements_in,
            'cash_movements_out' => $cash_movements_out,
            'total_expenses' => $expense_totals['total_expenses'],
            'cash_expenses' => $expense_totals['cash_expenses'],
            'expense_count' => $expense_totals['expense_count'],
            'expected_cash' => $expected_cash,
            'actual_cash' => $session->actual_cash,
            'cash_difference' => $session->cash_difference,
            'payment_method_totals' => $payment_method_totals,

            'total_orders' => $total_orders,
            'total_sales_amount' => $total_sales_amount,
            'total_tax' => $total_tax,
            'total_discount' => $total_discount,
            'total_paid_amount' => $total_paid_amount,
            'total_change_amount' => $total_change_amount,
            'order_source_totals' => $order_source_totals,
            'credit_order_count' => $credit_order_count,
            'credit_amount' => $credit_amount,
            'cash_order_count' => $cash_order_count,
            'void_amount' => $void_amount,
            'returned_amount' => $returned_amount,
        ];
    }

    /**
     * Adds a manual till-cash adjustment (e.g. cash drop, float top-up) against an
     * open session.
     */
    public function addCashMovement($obj)
    {
        $session = $this->model_pos_register_session->find($obj['pos_register_session_id']);

        if ($session->status != 'open') {
            throw new Exception('Cash movements can only be added to an open session.');
        }

        return PosRegisterCashMovement::create([
            'pos_register_cash_movement_id' => generateUuid(),
            'pos_register_session_id' => $obj['pos_register_session_id'],
            'type' => $obj['type'],
            'amount' => $obj['amount'],
            'reason' => $obj['reason'] ?? null,
            'is_deleted' => 0,
            'createdby_id' => Auth::id(),
            'date_created' => now(),
        ]);
    }

    /**
     * Voids a closed session. Registers don't touch stock/accounting directly
     * (only orders do), so this is kept minimal: soft-delete plus an audit note,
     * with no compensating session or stock/JV interaction.
     *
     * TODO: link to a formal audit/status-history table once one exists for
     * register sessions.
     */
    public function reverse($pos_register_session_id, $reason = null)
    {
        $session = $this->model_pos_register_session->find($pos_register_session_id);

        if ($session->status != 'closed') {
            throw new Exception('Only a closed session can be voided.');
        }

        return $this->model_pos_register_session->update([
            'is_deleted' => 1,
            'closing_notes' => trim(($session->closing_notes ?? '') . "\nVoided: " . ($reason ?? '')),
            'deletedby_id' => Auth::id(),
            'date_deleted' => now(),
        ], $pos_register_session_id);
    }

    /**
     * Recent register sessions for a business (any cashier) - used to
     * populate the POS Session picker on the Admin "Expense Detail" CRUD,
     * where an admin can attach an expense to any session/OT.
     */
    public function getByBusiness($business_id, $limit = 100)
    {
        return PosRegisterSession::with(['register', 'branch', 'cashier'])
            ->where('business_id', $business_id)
            ->where('is_deleted', 0)
            ->orderByDesc('opening_datetime')
            ->limit($limit)
            ->get();
    }

    public function getCurrentSession($cashier_id)
    {
        return PosRegisterSession::with(['register', 'branch'])
            ->where('cashier_id', $cashier_id)
            ->where('status', 'open')
            ->where('is_deleted', 0)
            ->first();
    }

    /**
     * Recent sessions opened by $cashier_id (own history only) - used by the
     * in-POS Reports panel, which every POS role can reach regardless of
     * `pos-register-session/data`'s Super Admin/Business Admin-only scope.
     */
    public function getRecentForCashier($cashier_id, $limit = 20)
    {
        return PosRegisterSession::with(['register', 'branch'])
            ->where('cashier_id', $cashier_id)
            ->where('is_deleted', 0)
            ->orderByDesc('opening_datetime')
            ->limit($limit)
            ->get();
    }

    /**
     * Resolves the session a POS screen should use for $user, branching on
     * the business's register_mode:
     * - manual: the user's own open session (they must open one explicitly).
     * - automatic: the single shared branch session for the current business-
     *   hours window, auto-opened/closed here. Returns null when "now" falls
     *   outside the configured open/close window - the POS screen then stays
     *   in browse-only mode (no checkout) exactly like the no-open-register
     *   manual case.
     */
    public function resolveSessionForUser($user)
    {
        $pos_setting = PosSetting::where('business_id', $user->business_id)->first();

        if (($pos_setting->register_mode ?? 'manual') !== 'automatic') {
            return $this->getCurrentSession($user->id);
        }

        return $this->getOrOpenAutomaticSession($user->business_id, $user->branch_id, $user->id);
    }

    /**
     * Automatic-mode session resolution: reuses the still-open session for
     * the current business-hours window bucket if one exists, auto-closes a
     * session left open from a previous window bucket, and otherwise opens a
     * fresh one - all under a row lock on the shared automatic register so
     * concurrent requests from multiple cashiers can't create duplicates
     * (same SELECT ... FOR UPDATE technique generateDailyOrderNumber() uses).
     */
    public function getOrOpenAutomaticSession($business_id, $branch_id, $cashier_id)
    {
        $window = $this->pos_register_service->getEffectiveWindow($business_id, $branch_id);

        if (!$this->pos_register_service->isWithinWindow($window['open_time'], $window['close_time'])) {
            return null;
        }

        $window_start = $this->pos_register_service->currentWindowStart($window['open_time'], $window['close_time']);

        return DB::transaction(function () use ($business_id, $branch_id, $cashier_id, $window_start) {
            $register = $this->pos_register_service->resolveRegisterForUser($business_id, $branch_id, (object) ['id' => $cashier_id]);

            $register = \App\Models\PosRegister::where('pos_register_id', $register->pos_register_id)
                ->lockForUpdate()
                ->first();

            $open_session = PosRegisterSession::where('pos_register_id', $register->pos_register_id)
                ->where('status', 'open')
                ->where('is_deleted', 0)
                ->orderByDesc('opening_datetime')
                ->first();

            if ($open_session) {
                if (Carbon::parse($open_session->opening_datetime)->gte($window_start)) {
                    // Already open for the current window - shared by every
                    // cashier hitting the POS screen during this period.
                    return $open_session->load(['register', 'branch']);
                }

                // Left open from a previous window - auto-close it before
                // opening today's, using the same expected-cash computation a
                // manual close uses (no cash count is possible automatically,
                // so actual_cash is assumed to equal expected_cash).
                $summary = $this->getSummary($open_session->pos_register_session_id);

                $open_session->update([
                    'closing_datetime' => now(),
                    'expected_cash' => $summary['expected_cash'],
                    'actual_cash' => $summary['expected_cash'],
                    'cash_difference' => 0,
                    'closing_notes' => 'Automatically closed - business hours window ended.',
                    'status' => 'closed',
                    'updatedby_id' => $cashier_id,
                    'date_updated' => now(),
                ]);
            }

            // Carry the previous session's counted cash forward as today's
            // opening float when available, otherwise start from zero.
            $previous = PosRegisterSession::where('pos_register_id', $register->pos_register_id)
                ->where('status', 'closed')
                ->where('is_deleted', 0)
                ->orderByDesc('closing_datetime')
                ->first();

            $session = $this->model_pos_register_session->create([
                'pos_register_session_id' => generateUuid(),
                'pos_register_id' => $register->pos_register_id,
                'business_id' => $register->business_id,
                'branch_id' => $register->branch_id,
                'cashier_id' => $cashier_id,
                'opening_datetime' => now(),
                'opening_cash' => $previous->actual_cash ?? 0,
                'opening_notes' => 'Automatically opened - business hours window started.',
                'status' => 'open',
                'is_deleted' => 0,
                'createdby_id' => $cashier_id,
                'date_created' => now(),
            ]);

            return $session->load(['register', 'branch']);
        });
    }
}
