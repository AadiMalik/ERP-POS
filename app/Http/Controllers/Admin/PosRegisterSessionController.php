<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\PosRegisterSessionService;
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

    public function __construct(PosRegisterSessionService $pos_register_session_service, BusinessService $business_service)
    {
        $this->pos_register_session_service = $pos_register_session_service;
        $this->business_service = $business_service;
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
        try {
            $summary = $this->pos_register_session_service->getSummary($pos_register_session_id);
            return $this->success(Message::FETCH, $summary);
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
