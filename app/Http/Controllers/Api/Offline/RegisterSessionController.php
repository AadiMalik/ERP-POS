<?php

namespace App\Http\Controllers\Api\Offline;

use App\Http\Controllers\Controller;
use App\Models\PosRegisterSession;
use App\Services\Concrete\Admin\PosRegisterSessionService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class RegisterSessionController extends Controller
{
    use ResponseAPI;

    protected $session_service;

    public function __construct(PosRegisterSessionService $session_service)
    {
        $this->middleware('permission:pos.access')->only(['open']);
        $this->middleware('permission:pos.register.close|pos.access')->only(['close']);

        $this->session_service = $session_service;
    }

    public function open(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'pos_register_id' => ['nullable', 'string'],
            'opening_cash' => ['required', 'numeric', 'min:0'],
            'opening_notes' => ['nullable', 'string'],
            'idempotency_key' => ['nullable', 'string', 'max:64'],
        ]);

        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        try {
            $device = $request->attributes->get('pos_device');
            $key = $request->input('idempotency_key');

            if ($key) {
                $existing = PosRegisterSession::where('business_id', $device->business_id)
                    ->where('offline_local_id', $key)
                    ->first();
                if ($existing) {
                    return $this->success('Session already synced.', $existing);
                }
            }

            $payload = $request->all();
            $payload['business_id'] = $device->business_id;
            $payload['branch_id'] = $device->branch_id;
            $payload['pos_device_id'] = $device->pos_device_id;
            $payload['offline_local_id'] = $key ?: $request->input('local_id');

            $session = $this->session_service->open($payload);

            return $this->success('Register session opened.', $session);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function close(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'pos_register_session_id' => ['required', 'string'],
            'actual_cash' => ['required', 'numeric', 'min:0'],
            'closing_notes' => ['nullable', 'string'],
        ]);

        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        $session = PosRegisterSession::find($request->pos_register_session_id);

        if (!$session) {
            return $this->error('This register session was not found.');
        }

        if (!$this->authorizedForSession($session, 'pos.register.close')) {
            return $this->error('You do not have permission to close this register session.', 403);
        }

        try {
            $session = $this->session_service->close($request->all());

            return $this->success('Register session closed.', $session);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function cashMovement(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'pos_register_session_id' => ['required', 'string'],
            'type' => ['required', 'in:in,out'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reason' => ['required', 'string', 'max:255'],
            'idempotency_key' => ['nullable', 'string', 'max:64'],
        ]);

        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        $session = PosRegisterSession::find($request->pos_register_session_id);

        if (!$session) {
            return $this->error('This register session was not found.');
        }

        if (!$this->authorizedForSession($session, 'pos.register.cash-movement.manage')) {
            return $this->error('You do not have permission to record cash movements for this register session.', 403);
        }

        try {
            $payload = $request->all();
            $payload['offline_local_id'] = $request->input('idempotency_key') ?: $request->input('local_id');

            $movement = $this->session_service->addCashMovement($payload);

            return $this->success('Cash movement recorded.', $movement);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Same ownership rule as the web PosRegisterSessionController: the acting
     * cashier's own session, or - within their own business only - a user
     * holding $permission. Mirrored here rather than shared because this
     * controller authenticates via device token + Sanctum (see
     * EnsureOfflinePosDevice) rather than the web session guard.
     */
    protected function authorizedForSession(PosRegisterSession $session, string $permission): bool
    {
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        if ($user->id == $session->cashier_id) {
            return true;
        }

        $same_business = getRoleName() == \App\Enums\RoleNames::SUPERADMIN || $user->business_id == $session->business_id;

        return $same_business && $user->can($permission);
    }
}
