<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Api\PaymentController as WebsitePaymentController;

/**
 * Mobile app Payment Gateway API - identical to the website's PaymentController
 * (same App\Services\Concrete\Api\PaymentService, same PaymentGateway rows),
 * only the platform flag differs so gateway availability respects each
 * gateway's mobile_enabled flag. Mirrors MobileCheckoutService's bare-subclass
 * relationship to WebsiteCheckoutService.
 */
class PaymentController extends WebsitePaymentController
{
    protected $platform = 'mobile';
}
