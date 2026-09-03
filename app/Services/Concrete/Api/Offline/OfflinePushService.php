<?php

namespace App\Services\Concrete\Api\Offline;

use App\Enums\RoleNames;
use App\Enums\Status;
use App\Models\Expense;
use App\Models\Order;
use App\Models\OrderSource;
use App\Models\PosDevice;
use App\Models\PosRegisterCashMovement;
use App\Models\PosRegisterSession;
use App\Models\Role;
use App\Services\Concrete\Admin\CustomerService;
use App\Services\Concrete\Admin\ExpenseService;
use App\Services\Concrete\Admin\OrderService;
use App\Services\Concrete\Admin\PosRegisterSessionService;
use App\Services\Concrete\Admin\UserService;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Accepts batched offline transactions from desktop POS and applies them
 * idempotently via existing OrderService / PosRegisterSessionService.
 */
class OfflinePushService
{
    protected $order_service;
    protected $session_service;
    protected $device_service;
    protected $user_service;
    protected $customer_service;
    protected $expense_service;

    public function __construct(
        OrderService $order_service,
        PosRegisterSessionService $session_service,
        OfflineDeviceService $device_service,
        UserService $user_service,
        CustomerService $customer_service,
        ExpenseService $expense_service
    ) {
        $this->order_service = $order_service;
        $this->session_service = $session_service;
        $this->device_service = $device_service;
        $this->user_service = $user_service;
        $this->customer_service = $customer_service;
        $this->expense_service = $expense_service;
    }

    public function push(PosDevice $device, array $batch): array
    {
        $results = [];

        foreach ($batch as $item) {
            $type = $item['type'] ?? '';
            $local_id = $item['local_id'] ?? null;
            $idempotency_key = $item['idempotency_key'] ?? $local_id;

            try {
                $results[] = match ($type) {
                    'order.save' => $this->pushOrderSave($device, $item['payload'] ?? [], $idempotency_key, $local_id),
                    'order.complete' => $this->pushOrderComplete($device, $item['payload'] ?? [], $idempotency_key, $local_id),
                    'order.hold' => $this->pushOrderHold($device, $item['payload'] ?? [], $idempotency_key, $local_id),
                    'session.open' => $this->pushSessionOpen($device, $item['payload'] ?? [], $idempotency_key, $local_id),
                    'session.close' => $this->pushSessionClose($device, $item['payload'] ?? [], $idempotency_key, $local_id),
                    'session.cash_movement' => $this->pushCashMovement($device, $item['payload'] ?? [], $idempotency_key, $local_id),
                    'customer.add' => $this->pushCustomer($device, $item['payload'] ?? [], $idempotency_key, $local_id),
                    'expense.add' => $this->pushExpense($device, $item['payload'] ?? [], $idempotency_key, $local_id),
                    default => [
                        'local_id' => $local_id,
                        'status' => 'failed',
                        'error' => 'Unknown transaction type: ' . $type,
                    ],
                };
            } catch (Exception $e) {
                $results[] = [
                    'local_id' => $local_id,
                    'idempotency_key' => $idempotency_key,
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                ];
            }
        }

        if (!empty($batch['cursors'])) {
            $this->device_service->updateCursors($device, $batch['cursors']);
        }

        return $results;
    }

    protected function findByIdempotency(string $business_id, ?string $key): ?Order
    {
        if (empty($key)) {
            return null;
        }

        return Order::where('business_id', $business_id)
            ->where('client_request_id', $key)
            ->first();
    }

    /**
     * The desktop client only knows its own local (offline_local_id) session
     * id until that session's own 'session.open' transaction has synced -
     * every other transaction referencing a session (orders, close, cash
     * movements) sends whichever id it has, server or local. Resolves either
     * to the real pos_register_session_id.
     */
    protected function resolveSessionServerId(PosDevice $device, ?string $id): ?string
    {
        if (empty($id)) {
            return null;
        }

        return PosRegisterSession::where('business_id', $device->business_id)
            ->where(function ($query) use ($id) {
                $query->where('pos_register_session_id', $id)->orWhere('offline_local_id', $id);
            })
            ->value('pos_register_session_id');
    }

    protected function pushOrderSave(PosDevice $device, array $payload, ?string $idempotency_key, ?string $local_id): array
    {
        $existing = $this->findByIdempotency($device->business_id, $idempotency_key);
        if ($existing) {
            return $this->orderResult($existing, $local_id, $idempotency_key, 'synced');
        }

        $payload['client_request_id'] = $idempotency_key ?: (string) Str::uuid();
        $payload['pos_device_id'] = $device->pos_device_id;
        $payload['offline_local_id'] = $local_id;
        $payload['register_session_id'] = $this->resolveSessionServerId(
            $device,
            $payload['register_session_id'] ?? $payload['register_session_local_id'] ?? null
        );
        $payload['order_source_id'] = $payload['order_source_id'] ?? $this->resolvePosOrderSourceId($device->business_id);

        try {
            $order = $this->order_service->save($payload);
        } catch (QueryException $e) {
            // The periodic auto-sync scheduler and a manual retry (or two
            // overlapping batches) can both reach here for the same order
            // before either commits - the client_request_id unique
            // constraint is the real guard against a true duplicate, so
            // treat its violation as "someone else already created it"
            // rather than a hard failure.
            $order = $this->isDuplicateClientRequestId($e)
                ? $this->findByIdempotency($device->business_id, $idempotency_key)
                : null;

            if (!$order) {
                throw $e;
            }
        }

        return $this->orderResult($order, $local_id, $idempotency_key, 'synced');
    }

    protected function isDuplicateClientRequestId(QueryException $e): bool
    {
        return (int) $e->getCode() === 23000
            && str_contains($e->getMessage(), 'orders_business_client_request_unique');
    }

    /**
     * Mirrors PosScreenController::resolvePosOrderSourceId() - the web POS
     * always tags its orders with the business's seeded 'POS' order source
     * rather than relying on pos_setting->default_order_source_id (which is
     * commonly left unset), and the desktop client never sends order_source_id
     * itself, so it needs the same resolution here.
     */
    protected function resolvePosOrderSourceId(string $business_id): ?string
    {
        $sources = OrderSource::where('business_id', $business_id)
            ->where('is_deleted', 0)
            ->where('status', Status::ACTIVE)
            ->get();

        $source = $sources->firstWhere('code', 'POS')
            ?? $sources->firstWhere('is_default', true)
            ?? $sources->first();

        return optional($source)->order_source_id;
    }

    protected function pushOrderComplete(PosDevice $device, array $payload, ?string $idempotency_key, ?string $local_id): array
    {
        // Keyed by $local_id (stable per order), not the transaction's own
        // idempotency_key (":hold" vs ":complete" depending which queue row
        // triggered it) - both this and pushOrderHold() need to agree on
        // the same underlying draft order.
        $existing = $this->findByIdempotency($device->business_id, $local_id);
        if ($existing && $existing->status === 'posted') {
            return $this->orderResult($existing, $local_id, $idempotency_key, 'synced');
        }

        $order_id = $existing->order_id ?? null;
        if (empty($order_id) && !empty($payload['save_payload'])) {
            $save = $this->pushOrderSave($device, $payload['save_payload'], $local_id, $local_id);
            $order_id = $save['server_id'] ?? null;
        }

        // OrderService::post() needs the full save payload (payments,
        // credit terms, ...) - the queue row's outer payload only carries
        // { save_payload, order_id, local_id, idempotency_key }, so
        // payments living inside save_payload were never reaching post(),
        // which then rejected the order for having none.
        $post_payload = $payload['save_payload'] ?? [];
        $post_payload['order_id'] = $order_id;

        $order = $this->order_service->post($post_payload);

        return $this->orderResult($order, $local_id, $idempotency_key, 'synced');
    }

    protected function pushOrderHold(PosDevice $device, array $payload, ?string $idempotency_key, ?string $local_id): array
    {
        $existing = $this->findByIdempotency($device->business_id, $local_id);
        if ($existing && $existing->status === 'hold') {
            return $this->orderResult($existing, $local_id, $idempotency_key, 'synced');
        }

        $order_id = $existing->order_id ?? null;
        if (empty($order_id) && !empty($payload['save_payload'])) {
            $save = $this->pushOrderSave($device, $payload['save_payload'], $local_id, $local_id);
            $order_id = $save['server_id'] ?? null;
        }

        $order = $this->order_service->hold($order_id);

        return $this->orderResult($order, $local_id, $idempotency_key, 'synced');
    }

    protected function pushSessionOpen(PosDevice $device, array $payload, ?string $idempotency_key, ?string $local_id): array
    {
        if (!empty($idempotency_key)) {
            $existing = PosRegisterSession::where('business_id', $device->business_id)
                ->where('offline_local_id', $idempotency_key)
                ->first();
            if ($existing) {
                return $this->sessionResult($existing, $local_id, $idempotency_key, 'synced');
            }
        }

        $payload['offline_local_id'] = $idempotency_key ?: $local_id;
        $payload['pos_device_id'] = $device->pos_device_id;

        $session = $this->session_service->open($payload);

        return $this->sessionResult($session, $local_id, $idempotency_key, 'synced');
    }

    protected function pushSessionClose(PosDevice $device, array $payload, ?string $idempotency_key, ?string $local_id): array
    {
        $payload['pos_register_session_id'] = $this->resolveSessionServerId(
            $device,
            $payload['pos_register_session_id'] ?? $payload['local_id'] ?? $local_id
        );

        if (empty($payload['pos_register_session_id'])) {
            throw new Exception('Register session has not synced yet. Will retry.');
        }

        $existing_session = PosRegisterSession::find($payload['pos_register_session_id']);

        if (!$existing_session || !$this->authorizedForSession($existing_session, 'pos.register.close')) {
            throw new Exception('You do not have permission to close this register session.');
        }

        $session = $this->session_service->close($payload);

        return $this->sessionResult($session, $local_id, $idempotency_key, 'synced');
    }

    /**
     * Same cashier-owns-their-shift rule as
     * PosRegisterSessionController::close()/addCashMovement(): the acting
     * user's own session, or - within their own business only - a user
     * holding $permission. Applied here too since the queued offline sync
     * path is a second route to the same close/cash-movement actions and
     * must not be a way around the web controller's authorization.
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

        $same_business = getRoleName() == RoleNames::SUPERADMIN || $user->business_id == $session->business_id;

        return $same_business && $user->can($permission);
    }

    protected function pushCashMovement(PosDevice $device, array $payload, ?string $idempotency_key, ?string $local_id): array
    {
        if (!empty($idempotency_key)) {
            $existing = PosRegisterCashMovement::where('offline_local_id', $idempotency_key)->first();
            if ($existing) {
                return [
                    'local_id' => $local_id,
                    'idempotency_key' => $idempotency_key,
                    'server_id' => $existing->pos_register_cash_movement_id,
                    'status' => 'synced',
                ];
            }
        }

        $payload['pos_register_session_id'] = $this->resolveSessionServerId($device, $payload['pos_register_session_id'] ?? null);

        if (empty($payload['pos_register_session_id'])) {
            throw new Exception('Register session has not synced yet. Will retry.');
        }

        $session = PosRegisterSession::find($payload['pos_register_session_id']);

        if (!$session || !$this->authorizedForSession($session, 'pos.register.cash-movement.manage')) {
            throw new Exception('You do not have permission to record cash movements for this register session.');
        }

        $payload['offline_local_id'] = $idempotency_key ?: $local_id;
        $movement = $this->session_service->addCashMovement($payload);

        return [
            'local_id' => $local_id,
            'idempotency_key' => $idempotency_key,
            'server_id' => $movement->pos_register_cash_movement_id ?? null,
            'status' => 'synced',
        ];
    }

    /**
     * Mirrors CustomerController::store() - the customer role/profile is
     * created the same way here as it is for a synchronous
     * POST /api/offline/customers call, since the desktop queues this while
     * offline instead of calling that endpoint directly. UserService::save()
     * already dedupes by email for the customer role, so a retried push is
     * naturally idempotent.
     */
    protected function pushCustomer(PosDevice $device, array $payload, ?string $idempotency_key, ?string $local_id): array
    {
        $role_id = Role::where('name', RoleNames::USER)->whereNull('business_id')->value('id');

        if (empty($role_id)) {
            throw new Exception('Customer role is not configured.');
        }

        $customer = $this->user_service->save([
            'name' => $payload['name'] ?? '',
            'email' => $payload['email'] ?? '',
            'phone' => $payload['phone'] ?? null,
            'role_id' => $role_id,
            'business_id' => $device->business_id,
            'status' => 'active',
        ]);

        return [
            'local_id' => $local_id,
            'idempotency_key' => $idempotency_key,
            'server_id' => $customer->id,
            'status' => 'synced',
        ];
    }

    /**
     * Mirrors PosScreenController::quickCreateExpense() - the POS "Add
     * Expense" popup saves and posts (generating its JV) in one step since
     * the till cash changes the moment it's logged.
     */
    protected function pushExpense(PosDevice $device, array $payload, ?string $idempotency_key, ?string $local_id): array
    {
        if (!empty($idempotency_key)) {
            $existing = Expense::where('offline_local_id', $idempotency_key)->first();
            if ($existing) {
                return [
                    'local_id' => $local_id,
                    'idempotency_key' => $idempotency_key,
                    'server_id' => $existing->expense_id,
                    'status' => 'synced',
                ];
            }
        }

        $session_id = $this->resolveSessionServerId($device, $payload['pos_register_session_id'] ?? null);

        $expense = $this->expense_service->save([
            'pos_register_session_id' => $session_id,
            'expense_category_id' => $payload['expense_category_id'] ?? null,
            'amount' => $payload['amount'] ?? 0,
            'payment_method' => 'cash',
            'expense_date' => now(),
            'description' => $payload['description'] ?? null,
            'source' => 'pos',
            'auto_post' => true,
            'business_id' => $device->business_id,
            'offline_local_id' => $idempotency_key ?: $local_id,
        ]);

        return [
            'local_id' => $local_id,
            'idempotency_key' => $idempotency_key,
            'server_id' => $expense->expense_id,
            'status' => 'synced',
        ];
    }

    protected function orderResult($order, ?string $local_id, ?string $idempotency_key, string $status): array
    {
        return [
            'local_id' => $local_id,
            'idempotency_key' => $idempotency_key,
            'server_id' => $order->order_id ?? null,
            'daily_order_id' => $order->daily_order_id ?? null,
            'status' => $status,
        ];
    }

    protected function sessionResult($session, ?string $local_id, ?string $idempotency_key, string $status): array
    {
        return [
            'local_id' => $local_id,
            'idempotency_key' => $idempotency_key,
            'server_id' => $session->pos_register_session_id ?? null,
            'status' => $status,
        ];
    }
}
