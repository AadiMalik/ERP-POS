<?php

namespace App\Services\Concrete\Api\Mobile;

use App\Services\Concrete\Admin\ProductVariationStockService;
use App\Services\Concrete\Admin\TaxSettingResolverService;
use App\Services\Concrete\Admin\VariationPricingService;
use App\Services\Concrete\Api\WebsiteCartService;

/**
 * Mobile cart — same server-authoritative cart as the website storefront.
 * Separate class so mobile-specific cart rules can be added later. Only
 * overrides the constructor to swap in MobileCartVoucherService, so voucher
 * eligibility resolves the MOBILE_APP order source instead of WEBSITE.
 */
class MobileCartService extends WebsiteCartService
{
    public function __construct(
        VariationPricingService $pricing_engine,
        MobileCartVoucherService $voucher_service,
        ProductVariationStockService $stock_service,
        TaxSettingResolverService $tax_resolver
    ) {
        parent::__construct($pricing_engine, $voucher_service, $stock_service, $tax_resolver);
    }
}
