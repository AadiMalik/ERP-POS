<?php

namespace App\Services\Concrete\Api\Mobile;

use App\Services\Concrete\Api\WebsiteCartVoucherService;

/**
 * Mobile cart voucher preview/apply — same flow as the website storefront,
 * but resolves the MOBILE_APP order source instead of WEBSITE.
 */
class MobileCartVoucherService extends WebsiteCartVoucherService
{
    protected function orderSourceCode(): string
    {
        return 'MOBILE_APP';
    }
}
