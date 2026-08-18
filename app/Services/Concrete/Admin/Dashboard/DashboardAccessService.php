<?php

namespace App\Services\Concrete\Admin\Dashboard;

use App\Enums\RoleNames;
use App\Enums\Status;
use App\Models\Branch;
use App\Models\OrderSource;
use App\Models\OrderType;
use App\Models\PaymentMethod;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Resolves the Business Dashboard's access scope for the logged-in user:
 * which tier they see (full operational dashboard vs sales-only vs none),
 * which branch(es) they may view, and which date range applies - all
 * validated/whitelisted server-side so query-param tampering can never
 * widen what a request actually returns. Every dashboard sub-service
 * receives only this resolved scope, never raw request input.
 */
class DashboardAccessService
{
    /**
     * Mirrors applyRoleScope()'s own internal partition
     * (app/Helpers/CommonFunctions.php) exactly - deliberately NOT
     * RoleNames::businessLevelRoles()/branchLevelRoles(), which group Sale
     * Manager/Accountant/Operation Manager differently and would
     * misrepresent how those roles are actually scoped everywhere else in
     * the app.
     */
    protected const BUSINESS_ROLES = [
        RoleNames::BUSINESSADMIN,
        RoleNames::GENERALMANAGER,
        RoleNames::FINANCEMANAGER,
        RoleNames::HRMANAGER,
        RoleNames::REPORTINGANALYST,
        RoleNames::MARKITINGMANAGER,
        RoleNames::INVENTORYMANAGER,
        RoleNames::PURCHASEMANAGER,
    ];

    protected const MIXED_ROLES = [
        RoleNames::SALEMANAGER,
        RoleNames::ACCOUNTANT,
        RoleNames::OPERATIONMANAGER,
    ];

    protected const TIER2_ROLES = [
        RoleNames::ORDERTAKER,
        RoleNames::POSMANAGER,
    ];

    /**
     * Every role that gets the full operational dashboard (everything
     * except Super Admin's separate subscription-dashboard redirect, the
     * customer-facing "User" role, and the two Tier-2 POS roles). Exposed
     * publicly so other Dashboard services (e.g. purchase visibility) share
     * this exact list rather than redefining it.
     */
    public const TIER1_ROLES = [
        RoleNames::SUPERADMIN,
        RoleNames::BUSINESSADMIN,
        RoleNames::BRANCHADMIN,
        RoleNames::GENERALMANAGER,
        RoleNames::OPERATIONMANAGER,
        RoleNames::INVENTORYMANAGER,
        RoleNames::FINANCEMANAGER,
        RoleNames::SALEMANAGER,
        RoleNames::PURCHASEMANAGER,
        RoleNames::MARKITINGMANAGER,
        RoleNames::ACCOUNTANT,
        RoleNames::HRMANAGER,
        RoleNames::REPORTINGANALYST,
        RoleNames::STAFF,
    ];

    /**
     * Roles allowed to see the financial-statement-derived widgets
     * (Profit/Loss, Expenses, Cash/Bank, Receivables, Payables, COA
     * Summary, Ledger Activity, Recent Payments) - identical to the
     * hardcoded allow_roles already on ProfitLossReportService,
     * BalanceSheetReportService and ExpenseReportService, so the dashboard
     * introduces zero new privilege beyond what those report pages already
     * grant.
     */
    public const FINANCE_ROLES = [
        RoleNames::BUSINESSADMIN,
        RoleNames::FINANCEMANAGER,
        RoleNames::ACCOUNTANT,
        RoleNames::REPORTINGANALYST,
    ];

    public function resolveScope(Request $request): array
    {
        $user = Auth::user();
        $role = getRoleName();

        if (empty($role) || $role === RoleNames::USER) {
            return ['allowed' => false];
        }

        if (in_array($role, self::TIER2_ROLES) && !$user->can('dashboard.view')) {
            return ['allowed' => false];
        }

        $tier = in_array($role, self::TIER2_ROLES) ? 2 : 1;

        [$can_select_branch, $branch_options] = $this->resolveBranchOptions($user, $role);
        $effective_branch_id = $this->resolveEffectiveBranch($user, $can_select_branch, $branch_options, $request->input('branch_id'));

        [$start_date, $end_date, $can_use_date_filter] = $this->resolveDateRange($role, $request);

        return [
            'allowed' => true,
            'tier' => $tier,
            'role' => $role,
            'user' => $user,
            'business_id' => $user->business_id,
            'is_finance' => in_array($role, self::FINANCE_ROLES),
            'can_select_branch' => $can_select_branch,
            'branch_options' => $branch_options,
            'effective_branch_id' => $effective_branch_id,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'can_use_date_filter' => $can_use_date_filter,
            'date_filter' => $request->input('date_filter', 'this_month'),
            'order_type_id' => $this->resolveLookupId(OrderType::class, 'order_type_id', $user->business_id, $request->input('order_type_id')),
            'order_source_id' => $this->resolveLookupId(OrderSource::class, 'order_source_id', $user->business_id, $request->input('order_source_id')),
            'payment_method_id' => $this->resolveLookupId(PaymentMethod::class, 'payment_method_id', $user->business_id, $request->input('payment_method_id')),
        ];
    }

    /**
     * Whether this role is ever eligible for the branch selector, and the
     * branch list to populate it with. Business-level roles always get it;
     * "mixed" roles only when they have no branch_id of their own assigned
     * (matching applyRoleScope()'s conditional branch filter for them
     * exactly); branch-level roles never do.
     */
    protected function resolveBranchOptions($user, string $role): array
    {
        if (in_array($role, self::BUSINESS_ROLES)) {
            return [true, $this->branchesForBusiness($user->business_id)];
        }

        if (in_array($role, self::MIXED_ROLES) && empty($user->branch_id)) {
            return [true, $this->branchesForBusiness($user->business_id)];
        }

        return [false, collect()];
    }

    protected function branchesForBusiness($business_id)
    {
        return Branch::where('business_id', $business_id)
            ->where('is_deleted', 0)
            ->where('status', Status::ACTIVE)
            ->orderBy('name')
            ->get(['branch_id', 'name']);
    }

    /**
     * The single anti-tampering choke point: branch-locked users never have
     * their request's branch_id read at all. Selector-eligible users only
     * get a requested branch_id honored if it belongs to their own
     * business's branch list; anything else (foreign business, garbage,
     * tampering) silently falls back to "All Branches".
     */
    protected function resolveEffectiveBranch($user, bool $can_select_branch, $branch_options, $requested_branch_id): ?string
    {
        if (!$can_select_branch) {
            return $user->branch_id;
        }

        if ($requested_branch_id && $branch_options->pluck('branch_id')->contains($requested_branch_id)) {
            return $requested_branch_id;
        }

        return null;
    }

    /**
     * Order Taker is hard-locked to today regardless of any date params
     * sent (also independently re-enforced inside
     * OrderService::applyHistoryFilters() itself). Every other role gets
     * the normal client-supplied range, defaulting to "This Month".
     */
    protected function resolveDateRange(string $role, Request $request): array
    {
        if ($role === RoleNames::ORDERTAKER) {
            $today = Carbon::today();

            return [$today->copy()->startOfDay(), $today->copy()->endOfDay(), false];
        }

        $start = $request->filled('start_date')
            ? Carbon::parse($request->input('start_date'))->startOfDay()
            : Carbon::now()->startOfMonth();

        $end = $request->filled('end_date')
            ? Carbon::parse($request->input('end_date'))->endOfDay()
            : Carbon::now()->endOfMonth();

        return [$start, $end, true];
    }

    /**
     * Whitelists a submitted lookup-table id (order type / order source /
     * payment method) against the user's own business - invalid or foreign
     * ids are dropped, falling back to "all" rather than erroring.
     */
    protected function resolveLookupId(string $model, string $key, $business_id, $requested_id): ?string
    {
        if (empty($requested_id)) {
            return null;
        }

        $exists = $model::where($key, $requested_id)
            ->where('business_id', $business_id)
            ->exists();

        return $exists ? $requested_id : null;
    }
}
