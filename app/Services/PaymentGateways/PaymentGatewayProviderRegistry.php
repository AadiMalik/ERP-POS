<?php

namespace App\Services\PaymentGateways;

use App\Services\PaymentGateways\Providers\DummyTestProvider;
use App\Services\PaymentGateways\Providers\EasypaisaProvider;
use App\Services\PaymentGateways\Providers\JazzCashProvider;
use App\Services\PaymentGateways\Providers\NayaPayProvider;
use App\Services\PaymentGateways\Providers\PayFastProvider;
use App\Services\PaymentGateways\Providers\PayPalProvider;
use App\Services\PaymentGateways\Providers\SadaPayProvider;
use App\Services\PaymentGateways\Providers\SafepayProvider;
use App\Services\PaymentGateways\Providers\StripeProvider;

/**
 * Single source of truth for every known payment gateway provider - which
 * adapter class implements it, which countries/currencies/payment methods it
 * supports, whether it supports refunds/webhooks, and which config fields it
 * needs in Sandbox vs Live mode. Consumed by the CMS gateway CRUD (dynamic
 * config form + validation) and PaymentGatewayManager (adapter resolution).
 *
 * To add a new gateway: implement PaymentGatewayProviderContract in
 * Providers/<Name>Provider.php, then add one entry here. Never touch
 * checkout/webhook/refund logic elsewhere.
 */
class PaymentGatewayProviderRegistry
{
    public static function providers(): array
    {
        return [
            'jazzcash' => [
                'label' => 'JazzCash',
                'adapter' => JazzCashProvider::class,
                'countries' => ['PK'],
                'currencies' => ['PKR'],
                'payment_methods' => ['mobile_wallet', 'card'],
                'supports_refund' => false,
                'supports_webhook' => true,
                'config_fields' => self::sameForBothModes([
                    ['key' => 'merchant_id', 'label' => 'Merchant ID', 'required' => true, 'secret' => false],
                    ['key' => 'password', 'label' => 'Password', 'required' => true, 'secret' => true],
                    ['key' => 'integrity_salt', 'label' => 'Integrity Salt', 'required' => true, 'secret' => true],
                    ['key' => 'return_url', 'label' => 'Return URL', 'required' => true, 'secret' => false],
                ]),
            ],

            'stripe' => [
                'label' => 'Stripe',
                'adapter' => StripeProvider::class,
                'countries' => null, // international
                'currencies' => ['USD', 'GBP', 'EUR', 'AED', 'PKR'],
                'payment_methods' => ['card', 'google_pay', 'apple_pay'],
                'supports_refund' => true,
                'supports_webhook' => true,
                'config_fields' => self::sameForBothModes([
                    ['key' => 'publishable_key', 'label' => 'Publishable Key', 'required' => true, 'secret' => false],
                    ['key' => 'secret_key', 'label' => 'Secret Key', 'required' => true, 'secret' => true],
                    ['key' => 'webhook_secret', 'label' => 'Webhook Signing Secret', 'required' => true, 'secret' => true],
                ]),
            ],

            'easypaisa' => [
                'label' => 'Easypaisa',
                'adapter' => EasypaisaProvider::class,
                'countries' => ['PK'],
                'currencies' => ['PKR'],
                'payment_methods' => ['mobile_wallet'],
                'supports_refund' => false,
                'supports_webhook' => true,
                'config_fields' => self::sameForBothModes([
                    ['key' => 'store_id', 'label' => 'Store ID', 'required' => true, 'secret' => false],
                    ['key' => 'hash_key', 'label' => 'Hash Key', 'required' => true, 'secret' => true],
                    ['key' => 'return_url', 'label' => 'Return URL', 'required' => true, 'secret' => false],
                ]),
            ],

            'payfast' => [
                'label' => 'PayFast',
                'adapter' => PayFastProvider::class,
                'countries' => ['PK'],
                'currencies' => ['PKR'],
                'payment_methods' => ['card', 'bank_account', 'paypak'],
                'supports_refund' => true,
                'supports_webhook' => true,
                'config_fields' => self::sameForBothModes([
                    ['key' => 'merchant_id', 'label' => 'Merchant ID', 'required' => true, 'secret' => false],
                    ['key' => 'secured_key', 'label' => 'Secured Key', 'required' => true, 'secret' => true],
                    ['key' => 'store_id', 'label' => 'Store ID', 'required' => true, 'secret' => false],
                    ['key' => 'return_url', 'label' => 'Return URL', 'required' => true, 'secret' => false],
                ]),
            ],

            'safepay' => [
                'label' => 'Safepay',
                'adapter' => SafepayProvider::class,
                'countries' => ['PK'],
                'currencies' => ['PKR'],
                'payment_methods' => ['card', 'mobile_wallet'],
                'supports_refund' => true,
                'supports_webhook' => true,
                'config_fields' => self::sameForBothModes([
                    ['key' => 'api_key', 'label' => 'API Key (Client)', 'required' => true, 'secret' => true],
                    ['key' => 'api_secret', 'label' => 'API Secret', 'required' => true, 'secret' => true],
                    ['key' => 'webhook_secret', 'label' => 'Webhook Secret', 'required' => true, 'secret' => true],
                    ['key' => 'redirect_url', 'label' => 'Redirect URL', 'required' => true, 'secret' => false],
                ]),
            ],

            'nayapay' => [
                'label' => 'NayaPay',
                'adapter' => NayaPayProvider::class,
                'countries' => ['PK'],
                'currencies' => ['PKR'],
                'payment_methods' => ['bank_account'],
                'supports_refund' => false,
                'supports_webhook' => true,
                'config_fields' => self::sameForBothModes([
                    ['key' => 'client_id', 'label' => 'Client ID', 'required' => true, 'secret' => false],
                    ['key' => 'client_secret', 'label' => 'Client Secret', 'required' => true, 'secret' => true],
                ]),
            ],

            'sadapay' => [
                'label' => 'SadaPay',
                'adapter' => SadaPayProvider::class,
                'countries' => ['PK'],
                'currencies' => ['PKR'],
                'payment_methods' => ['bank_account', 'card'],
                'supports_refund' => false,
                'supports_webhook' => true,
                'config_fields' => self::sameForBothModes([
                    ['key' => 'merchant_id', 'label' => 'Merchant ID', 'required' => true, 'secret' => false],
                    ['key' => 'api_key', 'label' => 'API Key', 'required' => true, 'secret' => true],
                ]),
            ],

            'paypal' => [
                'label' => 'PayPal',
                'adapter' => PayPalProvider::class,
                'countries' => null,
                'currencies' => ['USD', 'GBP', 'EUR'],
                'payment_methods' => ['card', 'paypal_wallet'],
                'supports_refund' => true,
                'supports_webhook' => true,
                'config_fields' => self::sameForBothModes([
                    ['key' => 'client_id', 'label' => 'Client ID', 'required' => true, 'secret' => false],
                    ['key' => 'client_secret', 'label' => 'Client Secret', 'required' => true, 'secret' => true],
                    ['key' => 'webhook_id', 'label' => 'Webhook ID', 'required' => true, 'secret' => false],
                    ['key' => 'return_url', 'label' => 'Return URL', 'required' => true, 'secret' => false],
                    ['key' => 'cancel_url', 'label' => 'Cancel URL', 'required' => false, 'secret' => false],
                ]),
            ],

            // Test-only fake gateway backing the automated lifecycle test
            // suite - never shown in the CMS provider picker (see forSelect()).
            'dummy_test' => [
                'label' => 'Dummy Test Gateway',
                'adapter' => DummyTestProvider::class,
                'countries' => null,
                'currencies' => ['PKR', 'USD'],
                'payment_methods' => ['card'],
                'supports_refund' => true,
                'supports_webhook' => true,
                'internal' => true,
                'config_fields' => self::sameForBothModes([
                    ['key' => 'webhook_secret', 'label' => 'Webhook Secret', 'required' => true, 'secret' => true],
                ]),
            ],
        ];
    }

    public static function find(string $code): ?array
    {
        return self::providers()[$code] ?? null;
    }

    /** Provider codes selectable from the CMS "Add Gateway" screen (excludes internal/test-only providers). */
    public static function forSelect(): array
    {
        return array_filter(self::providers(), fn ($p) => empty($p['internal']));
    }

    private static function sameForBothModes(array $fields): array
    {
        return ['sandbox' => $fields, 'live' => $fields];
    }
}
