<?php

namespace App\Services\Concrete\Api\Mobile;

use App\Services\Concrete\Api\CustomerAccountService;

/**
 * Mobile customer account helpers — same behaviour as the storefront
 * CustomerAccountService; kept as a separate class so the mobile app can
 * diverge later without touching the website API.
 */
class MobileCustomerAccountService extends CustomerAccountService
{
}
