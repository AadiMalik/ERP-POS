<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\PosRegisterSessionService;
use App\Services\Concrete\Admin\ThermalPrintSettingResolverService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PosRegisterSessionController extends Controller
{
    use ResponseAPI;

    protected $pos_register_session_service;
    protected $business_service;
    protected $thermal_print_setting_resolver;

    public function __construct(
        PosRegisterSessionService $pos_register_session_service,
        BusinessService $business_service,
        ThermalPrintSettingResolverService $thermal_print_setting_resolver
    ) {
        $this->pos_register_session_service = $pos_register_session_service;
        $this->business_service = $business_service;
        $this->thermal_print_setting_resolver = $thermal_print_setting_resolver;
    }

    public function index()
    {
        $business = $this->business_service->getAllActive();
        return view('admin.pos.register-session.index', compact('business'));
    }

    public function getData(Request $request)
    {
        return $this->pos_register_session_service->getData($request->all());
    }

    public function open(Request $request)
    {
        $rules = [
            'opening_cash' => ['required', 'numeric', 'min:0'],
        ];

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        $obj = $request->only([
            'pos_register_id',
            'cashier_id',
            'opening_cash',
            'opening_notes',
            'business_id',
            'branch_id',
        ]);
        $obj['cashier_id'] = $obj['cashier_id'] ?? Auth::id();

        try {
            $session = $this->pos_register_session_service->open($obj);
            return $this->success(Message::SAVE, $session);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function close(Request $request)
    {
        $rules = [
            'pos_register_session_id' => ['required', 'string'],
            'actual_cash' => ['required', 'numeric', 'min:0'],
        ];

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        $session = \App\Models\PosRegisterSession::find($request->pos_register_session_id);

        if (!$session) {
            return $this->error('This register session was not found.');
        }

        if (Auth::id() != $session->cashier_id && !Auth::user()->can('pos.register.close')) {
            return $this->error('You do not have permission to close this register session.', 403);
        }

        $obj = $request->only([
            'pos_register_session_id',
            'actual_cash',
            'closing_notes',
        ]);

        try {
            $session = $this->pos_register_session_service->close($obj);
            return $this->success(Message::UPDATE, $session);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function summary($pos_register_session_id)
    {
        $session = \App\Models\PosRegisterSession::find($pos_register_session_id);

        if (!$session) {
            return $this->error('This register session was not found.');
        }

        $user = Auth::user();
        $same_business = getRoleName() == \App\Enums\RoleNames::SUPERADMIN || $user->business_id == $session->business_id;

        if (
            Auth::id() != $session->cashier_id
            && (!$same_business || (!$user->can('pos.register.close') && !$user->can('pos.register.report.view')))
        ) {
            return $this->error('You do not have permission to view this register session.', 403);
        }

        try {
            $summary = $this->pos_register_session_service->getSummary($pos_register_session_id);
            return $this->success(Message::FETCH, $summary);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Thermal-print rendition of the same session summary as summary() -
     * duplicates its authorization check (kept separate rather than shared,
     * to avoid any risk to the working JSON endpoint) since it exposes the
     * same sensitive session data as a printable HTML page instead of JSON.
     */
    public function printSummary($pos_register_session_id)
    {
        $session = \App\Models\PosRegisterSession::find($pos_register_session_id);

        if (!$session) {
            abort(404, 'This register session was not found.');
        }

        $user = Auth::user();
        $same_business = getRoleName() == \App\Enums\RoleNames::SUPERADMIN || $user->business_id == $session->business_id;

        if (
            Auth::id() != $session->cashier_id
            && (!$same_business || (!$user->can('pos.register.close') && !$user->can('pos.register.report.view')))
        ) {
            abort(403, 'You are not authorized to view this register session.');
        }

        $summary = $this->pos_register_session_service->getSummary($pos_register_session_id);
        $thermal_config = $this->thermal_print_setting_resolver->resolve($session->business_id);
        $business = $this->business_service->getById($session->business_id);
        $printed_at = now();

        return view('admin.pos.register-session.print.thermal-session-summary', compact('session', 'summary', 'thermal_config', 'business', 'printed_at'));
    }

    /**
     * The acting cashier's own recent register sessions - used by the in-POS
     * Reports panel. Deliberately separate from getData()/index(), which are
     * restricted to Super Admin/Business Admin for the full cross-cashier
     * admin listing.
     */
    public function myHistory()
    {
        try {
            $sessions = $this->pos_register_session_service->getRecentForCashier(Auth::id());
            return $this->success(Message::FETCH, $sessions);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function addCashMovement(Request $request)
    {
        $rules = [
            'pos_register_session_id' => ['required', 'string'],
            'type' => ['required', 'in:in,out'],
            'amount' => ['required', 'numeric', 'min:0.01'],
        ];

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        $obj = $request->only([
            'pos_register_session_id',
            'type',
            'amount',
            'reason',
        ]);

        try {
            $movement = $this->pos_register_session_service->addCashMovement($obj);
            return $this->success(Message::SAVE, $movement);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function current()
    {
        try {
            $session = $this->pos_register_session_service->resolveSessionForUser(Auth::user());
            return $this->success(Message::FETCH, $session);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
