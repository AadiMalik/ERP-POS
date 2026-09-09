<?php

namespace App\Services\Concrete\Api\Mobile;

use App\Services\Concrete\Api\WebsiteCheckoutService;

/**
 * Mobile checkout / place-order — same flow as the website storefront, but
 * orders are hardcoded to the MOBILE_APP order source instead of WEBSITE.
 */
class MobileCheckoutService extends WebsiteCheckoutService
{
    protected function orderSourceCode(): string
    {
        return 'MOBILE_APP';
    }
}
