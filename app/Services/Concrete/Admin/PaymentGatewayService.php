<?php

namespace App\Services\Concrete\Admin;

use App\Contracts\PaymentGatewayConnectionTestable;
use App\Enums\Filter;
use App\Enums\RoleNames;
use App\Enums\Status;
use App\Models\PaymentGateway;
use App\Models\PaymentMethod;
use App\Repository\Repository;
use App\Services\PaymentGateways\PaymentGatewayManager;
use App\Services\PaymentGateways\PaymentGatewayProviderRegistry;
use App\Traits\Auditable;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

class PaymentGatewayService
{
    use Auditable;

    protected $model_payment_gateway;
    protected $payment_gateway_manager;

    public function __construct(PaymentGatewayManager $payment_gateway_manager)
    {
        $this->model_payment_gateway = new Repository(new PaymentGateway());
        $this->payment_gateway_manager = $payment_gateway_manager;
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

        $allow_roles = [RoleNames::SUPERADMIN, RoleNames::BUSINESSADMIN];
        $datatable = $this->model_payment_gateway->getModel()::where($wh)
            ->where('is_deleted', 0)
            ->orderBy('sort_order', 'asc')
            ->orderBy('display_name', $orderBy);
        $datatable = applyRoleScope($datatable, $allow_roles);

        return DataTables::of($datatable)
            ->addColumn('provider', function ($item) {
                return PaymentGatewayProviderRegistry::find($item->provider_code)['label'] ?? $item->provider_code;
            })
            ->addColumn('mode', function ($item) {
                return $item->active_mode === 'live'
                    ? '<span class="badge bg-label-success">Live</span>'
                    : '<span class="badge bg-label-warning">Sandbox</span>';
            })
            ->addColumn('platforms', function ($item) {
                $badges = [];
                if ($item->website_enabled) {
                    $badges[] = '<span class="badge bg-label-info">Website</span>';
                }
                if ($item->mobile_enabled) {
                    $badges[] = '<span class="badge bg-label-info">Mobile</span>';
                }
                return implode(' ', $badges) ?: '-';
            })
            ->addColumn('status', function ($item) {
                $checked = $item->is_active ? 'checked' : '';
                return '
                <div class="form-check form-switch mb-0">
                    <input
                        class="form-check-input statusPaymentGateway"
                        type="checkbox"
                        data-id="' . $item->payment_gateway_id . '"
                        ' . $checked . '>
                </div>
            ';
            })
            ->addColumn('action', function ($item) {
                return "
                    <a class='btn btn-icon btn-outline-primary mr-2'
                     href='" . route('payment-gateway.edit', $item->payment_gateway_id) . "'
                    id='editPaymentGateway'>
                    <i class='fa fa-pencil'></i>
                    </a>
                    <button type='button' class='btn btn-icon btn-outline-secondary mr-2'
                    id='testPaymentGateway' data-id='{$item->payment_gateway_id}'>
                    <i class='fa fa-plug'></i>
                    </button>
                    <a class='btn btn-icon btn-outline-danger'
                    id='deletePaymentGateway'
                    data-id='{$item->payment_gateway_id}'>
                    <i class='fa fa-trash'></i>
                    </a>
                ";
            })
            ->rawColumns(['mode', 'platforms', 'status', 'action'])
            ->make(true);
    }

    /**
     * $obj carries plain arrays 'config_sandbox'/'config_live' of submitted
     * field values. Blank/missing fields never overwrite an existing secret -
     * only a non-empty submitted value replaces the previously stored one
     * (same guard FirebaseSetting's hasPrivateKey() provides for its single
     * field, generalized per-field here).
     */
    public function save(array $obj)
    {
        $business_id = $obj['business_id'] ?? Auth::user()->business_id;
        $existing = !empty($obj['payment_gateway_id']) ? $this->model_payment_gateway->getModel()::find($obj['payment_gateway_id']) : null;

        // The provider can't be changed once a gateway exists - resolve it
        // from the existing row first so an edit that (correctly) omits
        // provider_code never fails this lookup.
        $provider_code = $existing->provider_code ?? ($obj['provider_code'] ?? null);
        $provider = $provider_code ? PaymentGatewayProviderRegistry::find($provider_code) : null;
        if (!$provider) {
            throw new Exception('Unknown payment gateway provider.');
        }

        // Only one gateway per (business, provider) may be active at a time
        // - a business can keep more than one row for the same provider
        // (e.g. rotating credentials), but adding a new one is blocked while
        // an active one already exists; deactivate or delete it first.
        if (!$existing && $this->hasActiveGateway($business_id, $provider_code)) {
            throw new Exception(($provider['label'] ?? $provider_code) . ' is already active for this business. Deactivate or delete the existing one before adding another.');
        }

        $data = [
            'business_id' => $business_id,
            'provider_code' => $provider_code,
            'display_name' => $obj['display_name'],
            'description' => $obj['description'] ?? null,
            'country' => $obj['country'] ?? null,
            'website_enabled' => !empty($obj['website_enabled']),
            'mobile_enabled' => !empty($obj['mobile_enabled']),
            'supported_currencies' => $obj['supported_currencies'] ?? $provider['currencies'] ?? [],
            'supported_payment_methods' => $obj['supported_payment_methods'] ?? $provider['payment_methods'] ?? [],
            'active_mode' => $obj['active_mode'] ?? 'sandbox',
            'sort_order' => $obj['sort_order'] ?? 0,
            'config_sandbox' => $this->mergeConfig($existing?->configFor('sandbox') ?? [], $obj['config_sandbox'] ?? []),
            'config_live' => $this->mergeConfig($existing?->configFor('live') ?? [], $obj['config_live'] ?? []),
        ];

        DB::beginTransaction();
        try {
            if ($existing) {
                $old = $existing->only(['display_name', 'is_active', 'active_mode', 'website_enabled', 'mobile_enabled']);
                $data['updatedby_id'] = Auth::id();
                $data['date_updated'] = now();
                $this->model_payment_gateway->update($data, $existing->payment_gateway_id);
                $gateway = $this->model_payment_gateway->find($existing->payment_gateway_id);
                $this->logActivity('payment-gateway', $gateway->payment_gateway_id, 'updated', $old, $gateway->only(['display_name', 'is_active', 'active_mode', 'website_enabled', 'mobile_enabled']), null, $business_id);
            } else {
                $data['payment_gateway_id'] = generateUuid();
                $data['is_active'] = false;
                $data['createdby_id'] = Auth::id();
                $data['date_created'] = now();
                $gateway = $this->model_payment_gateway->create($data);
                $this->ensureLinkedPaymentMethod($gateway);
                // ensureLinkedPaymentMethod() persists payment_method_id via
                // a separate update() against a freshly-fetched record, so
                // reload before returning or the caller sees a stale null.
                $gateway = $gateway->fresh();
                $this->logActivity('payment-gateway', $gateway->payment_gateway_id, 'created', null, $gateway->only(['provider_code', 'display_name']), null, $business_id);
            }

            DB::commit();
            return $gateway;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function getById($payment_gateway_id)
    {
        return $this->model_payment_gateway->find($payment_gateway_id);
    }

    public function status($payment_gateway_id)
    {
        $gateway = $this->model_payment_gateway->find($payment_gateway_id);
        $new_status = !$gateway->is_active;

        // Activating one gateway is blocked while another row for the same
        // (business, provider) is already active - deactivate that one
        // first. Deactivating is always allowed.
        if ($new_status && $this->hasActiveGateway($gateway->business_id, $gateway->provider_code, $gateway->payment_gateway_id)) {
            $provider = PaymentGatewayProviderRegistry::find($gateway->provider_code);
            throw new Exception('Another ' . ($provider['label'] ?? $gateway->provider_code) . ' gateway is already active for this business. Deactivate it first.');
        }

        DB::beginTransaction();
        try {
            $this->model_payment_gateway->update([
                'is_active' => $new_status,
                'updatedby_id' => Auth::id(),
                'date_updated' => now(),
            ], $payment_gateway_id);

            $gateway = $this->model_payment_gateway->find($payment_gateway_id);
            $this->ensureLinkedPaymentMethod($gateway);

            $this->logActivity('payment-gateway', $payment_gateway_id, 'status_changed', ['is_active' => !$new_status], ['is_active' => $new_status], null, $gateway->business_id);

            DB::commit();
            return $gateway;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function delete($payment_gateway_id)
    {
        $gateway = $this->model_payment_gateway->find($payment_gateway_id);

        DB::beginTransaction();
        try {
            $this->model_payment_gateway->update([
                'is_deleted' => 1,
                'is_active' => false,
                'deletedby_id' => Auth::id(),
                'date_deleted' => now(),
            ], $payment_gateway_id);

            // Never hard-delete the linked payment_methods row - historical
            // order_payments reference it. Just deactivate it.
            if ($gateway->payment_method_id) {
                PaymentMethod::where('payment_method_id', $gateway->payment_method_id)->update([
                    'status' => Status::INACTIVE,
                    'updatedby_id' => Auth::id(),
                    'date_updated' => now(),
                ]);
            }

            $this->logActivity('payment-gateway', $payment_gateway_id, 'deleted', null, null, null, $gateway->business_id);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /** @return array{success: bool, message: string} */
    public function testConnection($payment_gateway_id): array
    {
        $gateway = $this->model_payment_gateway->find($payment_gateway_id);
        $adapter = $this->payment_gateway_manager->adapterFor($gateway);

        if ($adapter instanceof PaymentGatewayConnectionTestable) {
            return $adapter->testConnection($gateway);
        }

        return $gateway->isReadyForCheckout()
            ? ['success' => true, 'message' => 'Configuration looks complete. This provider has no safe live connectivity check - verify by completing a real Sandbox payment.']
            : ['success' => false, 'message' => 'One or more required configuration fields are missing for the active mode.'];
    }

    /**
     * Creates (or refreshes) the payment_methods row every active gateway
     * needs to plug into the existing order_payments/accounting pipeline -
     * see 2026_09_05_130100_add_gateway_type_to_payment_methods_table. Always
     * is_website_only so it can never surface in POS (PaymentMethodService::
     * getAllActive() already excludes is_website_only methods).
     */
    protected function ensureLinkedPaymentMethod(PaymentGateway $gateway): void
    {
        if ($gateway->payment_method_id) {
            PaymentMethod::where('payment_method_id', $gateway->payment_method_id)->update([
                'name' => $gateway->display_name,
                'status' => $gateway->is_active ? Status::ACTIVE : Status::INACTIVE,
                'updatedby_id' => Auth::id(),
                'date_updated' => now(),
            ]);
            return;
        }

        $payment_method_id = generateUuid();
        PaymentMethod::create([
            'payment_method_id' => $payment_method_id,
            'business_id' => $gateway->business_id,
            'name' => $gateway->display_name,
            'code' => strtoupper($gateway->provider_code) . '-' . substr($gateway->payment_gateway_id, 0, 8),
            'account_id' => null,
            'payment_gateway_id' => $gateway->payment_gateway_id,
            'type' => 'gateway',
            'is_default' => 0,
            'is_website_only' => 1,
            'status' => $gateway->is_active ? Status::ACTIVE : Status::INACTIVE,
            'sort_order' => $gateway->sort_order,
            'is_deleted' => 0,
            'createdby_id' => Auth::id(),
            'date_created' => now(),
        ]);

        $this->model_payment_gateway->update(['payment_method_id' => $payment_method_id], $gateway->payment_gateway_id);
    }

    /**
     * Whether an active, non-deleted gateway row already exists for this
     * (business, provider) - optionally excluding one row (used by status()
     * so a row doesn't block itself). A business may keep several rows per
     * provider (e.g. rotating credentials), but only one may be active.
     */
    private function hasActiveGateway(string $business_id, string $provider_code, ?string $exceptGatewayId = null): bool
    {
        return $this->model_payment_gateway->getModel()::where('business_id', $business_id)
            ->where('provider_code', $provider_code)
            ->where('is_active', 1)
            ->where('is_deleted', 0)
            ->when($exceptGatewayId, fn ($q) => $q->where('payment_gateway_id', '!=', $exceptGatewayId))
            ->exists();
    }

    private function mergeConfig(array $existing, array $submitted): array
    {
        foreach ($submitted as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $existing[$key] = $value;
        }
        return $existing;
    }
}
