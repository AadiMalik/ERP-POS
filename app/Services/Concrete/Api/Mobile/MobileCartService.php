<?php

namespace App\Services\Concrete\Api\Mobile;

use App\Services\Concrete\Api\WebsiteCartService;

/**
 * Mobile cart — same server-authoritative cart as the website storefront.
 * Separate class so mobile-specific cart rules can be added later.
 */
class MobileCartService extends WebsiteCartService
{
}
