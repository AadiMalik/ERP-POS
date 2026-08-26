<?php

namespace App\Services\Concrete\Admin;

/**
 * Composes the single optimized GET /api/v1/website-home/{business_id}
 * payload out of the existing per-domain services - no data is duplicated
 * or re-queried here, this only assembles what each service already knows
 * how to build for its own domain.
 */
class WebsiteHomeService
{
    protected $setting_service;
    protected $category_service;
    protected $product_service;
    protected $section_service;
    protected $faq_service;
    protected $social_service;
    protected $review_service;
    protected $hero_stat_service;
    protected $benefit_service;

    public function __construct(
        SettingService $setting_service,
        CategoryService $category_service,
        ProductService $product_service,
        WebsiteSectionService $section_service,
        WebsiteFaqService $faq_service,
        SocialMediaLinkService $social_service,
        ProductReviewService $review_service,
        WebsiteHeroStatService $hero_stat_service,
        WebsiteBenefitService $benefit_service
    ) {
        $this->setting_service = $setting_service;
        $this->category_service = $category_service;
        $this->product_service = $product_service;
        $this->section_service = $section_service;
        $this->faq_service = $faq_service;
        $this->social_service = $social_service;
        $this->review_service = $review_service;
        $this->hero_stat_service = $hero_stat_service;
        $this->benefit_service = $benefit_service;
    }

    /**
     * Singleton-style sections (0 or 1 expected) - the first active row of
     * the given type, or null if none/no content, so the frontend can
     * cleanly hide the section.
     */
    private function singleSection(array $sections, string $type): ?array
    {
        $match = collect($sections)->firstWhere('type', $type);
        return $match && !empty($match['heading'] ?? $match['image'] ?? null) ? $match : ($match ?: null);
    }

    private function listSections(array $sections, string $type): array
    {
        return collect($sections)->where('type', $type)->values()->all();
    }

    public function build(string $business_id): array
    {
        $public_settings = $this->setting_service->getWebsitePublicSettings($business_id);
        $website_theme_setting = $this->setting_service->getWebsiteThemeSetting($business_id);
        $theme = $this->setting_service->resolveWebsiteThemeConfig($website_theme_setting);

        $categories = $this->category_service->getActivePublicByBusiness($business_id);

        $all_sections = $this->section_service->getActivePublicByBusiness($business_id)->all();

        $product_data = $this->product_service->getWebsiteListing($business_id, ['per_page' => 1, 'page' => 1]);
        $product_groups = $product_data['sections'] ?? [];

        $group_config = function (string $type) use ($all_sections, $product_groups) {
            $config = $this->singleSection($all_sections, $type);
            $products = $product_groups[$type] ?? [];
            // No CMS row yet for this group type is not an error - fall back
            // to a sensible default heading so the section still renders
            // when there are products, and hide it entirely when empty.
            return [
                'enabled' => !empty($products),
                'heading' => $config['heading'] ?? ucwords(str_replace('_', ' ', $type)),
                'heading_icon' => $config['heading_icon'] ?? null,
                'description' => $config['description'] ?? null,
                'sort_order' => $config['sort_order'] ?? 0,
                'products' => $products,
            ];
        };

        // Social links come exclusively from the Social Media CRUD - no
        // fallback to the legacy website_theme_settings.social_links column.
        $social_links = $this->social_service->getActivePublicByBusiness($business_id);

        return [
            'business' => $public_settings['business'],
            'currency' => $public_settings['currency'],
            'seo' => $public_settings['seo'],
            'favicon' => $public_settings['favicon'],
            'business_hours' => $public_settings['business_hours'],
            'whatsapp_number' => $public_settings['whatsapp_number'],
            'free_delivery' => $public_settings['free_delivery'],
            'theme' => $theme,
            'navigation' => [
                'categories' => $categories,
            ],
            'social_links' => $social_links,
            'sections' => [
                'hero' => $this->singleSection($all_sections, 'hero'),
                'hero_stats' => $this->hero_stat_service->getActivePublicByBusiness($business_id),
                'about_us' => $this->singleSection($all_sections, 'about_us'),
                'contact_us' => $this->singleSection($all_sections, 'contact_us'),
                'why_shop_with_us' => $this->singleSection($all_sections, 'why_shop_with_us'),
                'why_shop_benefits' => $this->benefit_service->getActivePublicByBusiness($business_id, 'why_shop_with_us'),
                'promo_banners' => $this->listSections($all_sections, 'promo_banner'),
                'discount_banners' => $this->listSections($all_sections, 'discount_banner'),
                'product_groups' => [
                    'featured_products' => $group_config('featured_products'),
                    'discounted_products' => $group_config('discounted_products'),
                    'trending_products' => $group_config('trending_products'),
                    'new_arrivals' => $group_config('new_arrivals'),
                    'best_sellers' => $group_config('best_sellers'),
                ],
            ],
            'faqs' => $this->faq_service->getActivePublicByBusiness($business_id),
            'reviews' => $this->review_service->getLatestPublished($business_id),
        ];
    }
}
