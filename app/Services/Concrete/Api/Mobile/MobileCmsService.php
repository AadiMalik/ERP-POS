<?php

namespace App\Services\Concrete\Api\Mobile;

use App\Services\Concrete\Admin\ContactMessageService;
use App\Services\Concrete\Admin\NewsletterSubscriberService;
use App\Services\Concrete\Admin\ProductReviewService;
use App\Services\Concrete\Admin\SocialMediaLinkService;
use App\Services\Concrete\Admin\WebsiteBenefitService;
use App\Services\Concrete\Admin\WebsiteFaqService;
use App\Services\Concrete\Admin\WebsiteHeroStatService;
use App\Services\Concrete\Admin\WebsiteHomeService;
use App\Services\Concrete\Admin\WebsitePageService;
use App\Services\Concrete\Admin\WebsiteSectionService;
use App\Services\Concrete\Admin\WebsiteTestimonialService;

/**
 * Mobile CMS / content reads and public submissions (contact, newsletter,
 * reviews) — same Admin helpers as the website storefront API.
 */
class MobileCmsService
{
    protected $home_service;
    protected $section_service;
    protected $faq_service;
    protected $social_service;
    protected $hero_stat_service;
    protected $benefit_service;
    protected $testimonial_service;
    protected $page_service;
    protected $contact_service;
    protected $newsletter_service;
    protected $review_service;

    public function __construct(
        WebsiteHomeService $home_service,
        WebsiteSectionService $section_service,
        WebsiteFaqService $faq_service,
        SocialMediaLinkService $social_service,
        WebsiteHeroStatService $hero_stat_service,
        WebsiteBenefitService $benefit_service,
        WebsiteTestimonialService $testimonial_service,
        WebsitePageService $page_service,
        ContactMessageService $contact_service,
        NewsletterSubscriberService $newsletter_service,
        ProductReviewService $review_service
    ) {
        $this->home_service = $home_service;
        $this->section_service = $section_service;
        $this->faq_service = $faq_service;
        $this->social_service = $social_service;
        $this->hero_stat_service = $hero_stat_service;
        $this->benefit_service = $benefit_service;
        $this->testimonial_service = $testimonial_service;
        $this->page_service = $page_service;
        $this->contact_service = $contact_service;
        $this->newsletter_service = $newsletter_service;
        $this->review_service = $review_service;
    }

    public function home(string $business_id)
    {
        return $this->home_service->build($business_id);
    }

    public function section(string $business_id, string $type)
    {
        return $this->section_service->getActivePublicByBusiness($business_id, $type)->first();
    }

    public function faqs(string $business_id)
    {
        return $this->faq_service->getActivePublicByBusiness($business_id);
    }

    public function socialLinks(string $business_id)
    {
        return $this->social_service->getActivePublicByBusiness($business_id);
    }

    public function heroStats(string $business_id)
    {
        return $this->hero_stat_service->getActivePublicByBusiness($business_id);
    }

    public function contentItems(string $business_id, ?string $group = null)
    {
        return $this->benefit_service->getActivePublicByBusiness($business_id, $group);
    }

    public function testimonials(string $business_id)
    {
        return $this->testimonial_service->getActivePublicByBusiness($business_id);
    }

    public function pages(string $business_id)
    {
        return $this->page_service->getAllPublic($business_id);
    }

    public function page(string $business_id, string $slug)
    {
        return $this->page_service->getPublicBySlug($business_id, $slug);
    }

    public function submitContact(string $business_id, array $data): void
    {
        $this->contact_service->submit($business_id, $data);
    }

    public function subscribeNewsletter(string $business_id, string $email, ?string $source = null): void
    {
        $this->newsletter_service->subscribe($business_id, $email, $source);
    }

    public function reviews(string $business_id, string $product_id)
    {
        return $this->review_service->getPublicByProduct($business_id, $product_id);
    }

    public function submitReview(string $business_id, array $data)
    {
        return $this->review_service->submit($business_id, $data);
    }
}
