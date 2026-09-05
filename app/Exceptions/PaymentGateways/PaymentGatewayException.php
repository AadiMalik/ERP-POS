<?php

namespace App\Exceptions\PaymentGateways;

use Exception;

/**
 * General provider-adapter failure (API call failed, not yet implemented,
 * refund/webhook unsupported by this provider, etc). Message is always safe
 * to show an admin/customer - never put a raw credential or secret in it.
 */
class PaymentGatewayException extends Exception
{
}
