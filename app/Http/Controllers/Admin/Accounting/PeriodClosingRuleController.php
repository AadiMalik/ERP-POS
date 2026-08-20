<?php

namespace App\Http\Controllers\Admin\Accounting;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Models\PeriodClosingRule;
use App\Traits\ResponseAPI;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Single-row-per-business form (same shape as SettingController) for which
 * closing-checklist checks run before an auto/manual close is allowed to
 * proceed. Deliberately no "unreconciled bank items" toggle - the app has
 * no reconciliation feature yet.
 */
class PeriodClosingRuleController extends Controller
{
    use ResponseAPI;

    public function __construct()
    {
        $this->middleware('permission:period-closing-rule.manage');
        $this->middleware(function ($request, $next) {
            if (!businessAccountingAdvancedModeEnabled()) {
                abort(403, 'Advanced Accounting Mode is not enabled for this business.');
            }

            return $next($request);
        });
    }

    public function edit()
    {
        $business_id = Auth::user()->business_id;

        $rule = PeriodClosingRule::firstOrCreate(['business_id' => $business_id], [
            'period_closing_rule_id' => generateUuid(),
            'date_created'           => now(),
        ]);

        return view('admin.period_closing_rule.index', compact('rule'));
    }

    public function update(Request $request)
    {
        $business_id = Auth::user()->business_id;

        $rule = PeriodClosingRule::firstOrCreate(['business_id' => $business_id], [
            'period_closing_rule_id' => generateUuid(),
            'date_created'           => now(),
        ]);

        $rule->update([
            'check_unposted_journal_entries'  => $request->boolean('check_unposted_journal_entries'),
            'check_pending_purchase_returns'  => $request->boolean('check_pending_purchase_returns'),
            'check_pending_leave_requests'    => $request->boolean('check_pending_leave_requests'),
            'check_pending_employee_advances' => $request->boolean('check_pending_employee_advances'),
            'check_pending_employee_exits'    => $request->boolean('check_pending_employee_exits'),
            'updatedby_id'                    => Auth::id(),
            'date_updated'                    => now(),
        ]);

        return $this->success(Message::UPDATE, $rule);
    }
}
