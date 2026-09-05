<?php

namespace App\Models;

use App\Services\PaymentGateways\PaymentGatewayProviderRegistry;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentGateway extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $primaryKey = 'payment_gateway_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'payment_gateway_id',
        'business_id',
        'provider_code',
        'display_name',
        'description',
        'logo_path',
        'country',
        'is_active',
        'sort_order',
        'website_enabled',
        'mobile_enabled',
        'supported_currencies',
        'supported_payment_methods',
        'active_mode',
        'config_sandbox',
        'config_live',
        'payment_method_id',
        'is_deleted',
        'createdby_id',
        'updatedby_id',
        'deletedby_id',
        'date_created',
        'date_updated',
        'date_deleted',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'website_enabled' => 'boolean',
        'mobile_enabled' => 'boolean',
        'supported_currencies' => 'array',
        'supported_payment_methods' => 'array',
        // Encrypted at rest (APP_KEY), same mechanism as FirebaseSetting::private_key.
        'config_sandbox' => 'encrypted:array',
        'config_live' => 'encrypted:array',
        'date_created' => 'datetime',
        'date_updated' => 'datetime',
    ];

    protected $hidden = [
        'config_sandbox',
        'config_live',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }

    public function transactions()
    {
        return $this->hasMany(PaymentTransaction::class, 'payment_gateway_id');
    }

    /** Config for whichever mode (sandbox/live) is currently active. Never log/echo this. */
    public function activeConfig(): array
    {
        return $this->configFor($this->active_mode);
    }

    public function configFor(string $mode): array
    {
        return ($mode === 'live' ? $this->config_live : $this->config_sandbox) ?? [];
    }

    /**
     * Which required-for-this-mode fields are present, as booleans only -
     * never the values themselves. Drives the CMS edit form's masked display.
     */
    public function maskedConfig(string $mode): array
    {
        $provider = PaymentGatewayProviderRegistry::find($this->provider_code);
        $fields = $provider['config_fields'][$mode] ?? [];
        $config = $this->configFor($mode);

        $masked = [];
        foreach ($fields as $field) {
            $masked[$field['key']] = filled($config[$field['key']] ?? null);
        }

        return $masked;
    }

    /** True when every field the provider requires for the active mode is present. */
    public function isReadyForCheckout(): bool
    {
        $provider = PaymentGatewayProviderRegistry::find($this->provider_code);
        if (!$provider) {
            return false;
        }

        $fields = $provider['config_fields'][$this->active_mode] ?? [];
        $config = $this->activeConfig();

        foreach ($fields as $field) {
            if (!empty($field['required']) && !filled($config[$field['key']] ?? null)) {
                return false;
            }
        }

        return true;
    }
}
