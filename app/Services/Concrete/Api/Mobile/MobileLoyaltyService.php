<?php

namespace App\Services\Concrete\Api\Mobile;

use App\Services\Concrete\Api\CustomerLoyaltyService;

/**
 * Mobile Loyalty Program reads - same behaviour as the storefront
 * CustomerLoyaltyService; kept as a separate class so the mobile app can
 * diverge later without touching the website API.
 */
class MobileLoyaltyService extends CustomerLoyaltyService
{
}
