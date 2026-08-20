<?php

namespace App\Http\Controllers\Admin\Accounting;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\AccountingPeriodService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AccountingPeriodController extends Controller
{
    use ResponseAPI;

    protected $accounting_period_service;

    public function __construct(AccountingPeriodService $accounting_period_service)
    {
        $this->middleware('permission:accounting-period.view')->only(['index', 'getData', 'issues']);
        $this->middleware('permission:accounting-period.open')->only(['open']);
        $this->middleware('permission:accounting-period.close')->only(['close']);
        $this->middleware('permission:accounting-period.reopen')->only(['reopen']);
        $this->middleware(function ($request, $next) {
            if (!businessAccountingAdvancedModeEnabled()) {
                abort(403, 'Advanced Accounting Mode is not enabled for this business.');
            }

            return $next($request);
        });

        $this->accounting_period_service = $accounting_period_service;
    }

    public function index()
    {
        return view('admin.accounting_period.index');
    }

    public function getData(Request $request)
    {
        $obj = $request->all();
        $obj['business_id'] = $obj['business_id'] ?? Auth::user()->business_id;

        return $this->success(Message::FETCH, $this->accounting_period_service->getData($obj));
    }

    /**
     * The blocking issues found on the latest closing attempt for a period
     * (empty if it closed cleanly or no attempt has run yet) - what the
     * "why isn't this closed" panel renders.
     */
    public function issues($accounting_period_id)
    {
        try {
            return $this->success(Message::FETCH, $this->accounting_period_service->latestIssues($accounting_period_id));
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function open($accounting_period_id)
    {
        try {
            return $this->success(Message::SUCCESS, $this->accounting_period_service->manualOpen($accounting_period_id));
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function close(Request $request, $accounting_period_id)
    {
        try {
            $override = (bool) $request->boolean('override');
            $period = $this->accounting_period_service->manualClose($accounting_period_id, $request->reason, $override);

            return $this->success(Message::SUCCESS, $period);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function reopen(Request $request, $accounting_period_id)
    {
        if (empty(trim((string) $request->reason))) {
            return $this->validationResponse('A reason is required to reopen this period.');
        }

        try {
            return $this->success(Message::SUCCESS, $this->accounting_period_service->manualReopen($accounting_period_id, $request->reason));
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
