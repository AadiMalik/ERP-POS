<?php

namespace App\Services\Concrete\Api\Intro;

use App\Enums\Status;
use App\Models\IntroBlog;
use App\Models\IntroBlogCategory;
use App\Models\IntroBlogTag;
use App\Models\IntroHomepageSection;
use App\Models\IntroModule;
use App\Models\IntroNavigationItem;
use App\Models\IntroPage;
use App\Models\IntroTestimonial;
use App\Models\Package;
use App\Services\Concrete\Admin\Intro\BlogCommentService;
use App\Services\Concrete\Admin\Intro\BusinessRegistrationService;
use App\Services\Concrete\Admin\Intro\ContactInquiryService;
use App\Services\Concrete\Admin\Intro\WebsiteSettingService;
use App\Support\Subscription\SubscriptionModuleRegistry;
use Exception;

class IntroPublicService
{
    protected $settings;
    protected $comments;
    protected $contact;
    protected $registrations;

    public function __construct(
        WebsiteSettingService $settings,
        BlogCommentService $comments,
        ContactInquiryService $contact,
        BusinessRegistrationService $registrations
    ) {
        $this->settings = $settings;
        $this->comments = $comments;
        $this->contact = $contact;
        $this->registrations = $registrations;
    }

    public function packages()
    {
        return Package::with('modules')
            ->where('is_deleted', 0)
            ->where('status', 1)
            ->orderBy('order')
            ->get()
            ->map(fn (Package $p) => $this->mapPackage($p))
            ->values();
    }

    public function package($packageId)
    {
        $p = Package::with('modules')
            ->where('package_id', $packageId)
            ->where('is_deleted', 0)
            ->where('status', 1)
            ->firstOrFail();
        return $this->mapPackage($p);
    }

    protected function mapPackage(Package $p): array
    {
        $moduleCatalog = $this->mapPackageModuleCatalog($p);
        $listPrice = $p->listPrice();
        $effective = $p->effectivePrice();
        $discount = $p->discountPercent();
        $duration = $p->duration_type ?: 'monthly';

        return [
            'package_id' => $p->package_id,
            'id' => strtolower(str_replace(' ', '-', $p->name ?: ($p->code ?: 'package'))),
            'code' => $p->code,
            'name' => $p->name,
            'description' => $p->description,
            'tagline' => $p->tagline,
            'badge' => $p->badge,
            'best_for' => $p->best_for,
            'price' => $listPrice,
            'price_list' => $listPrice,
            'price_effective' => $effective,
            'discount' => $discount,
            'price_monthly' => $duration === 'monthly' ? $effective : $p->monthlyEquivalent(),
            'price_yearly' => $duration === 'yearly' ? $effective : null,
            'price_yearly_monthly' => $duration === 'yearly' ? $p->monthlyEquivalent() : null,
            'currency' => $p->currency ?: 'PKR',
            'support' => $p->support,
            'cta' => $p->cta ?: ('Choose ' . ($p->name ?: 'Plan')),
            'is_custom' => false,
            'order' => $p->order,
            'duration_type' => $duration,
            'duration_days' => $p->duration_days,
            'trial_days' => $p->trial_days,
            'billing_cycles' => $effective !== null ? [$duration] : [],
            'modules' => $p->modules->map(fn ($m) => [
                'module_key' => $m->module_key,
                'is_enabled' => (bool) $m->is_enabled,
                'is_unlimited' => (bool) $m->is_unlimited,
                'limit_value' => $m->limit_value,
            ])->values(),
            'module_groups' => $moduleCatalog['groups'],
            'included_modules' => $moduleCatalog['included'],
            'excluded_modules' => $moduleCatalog['excluded'],
            'highlights' => $moduleCatalog['highlights'],
        ];
    }

    /**
     * Build included / excluded / grouped module lists from package_modules
     * + SubscriptionModuleRegistry for Intro marketing pages.
     */
    protected function mapPackageModuleCatalog(Package $p): array
    {
        $byKey = $p->modules->keyBy('module_key');
        $groups = [];
        $included = [];
        $excluded = [];

        foreach (SubscriptionModuleRegistry::grouped() as $category => $modules) {
            $items = [];
            foreach ($modules as $key => $meta) {
                $row = $byKey->get($key);
                $parent = $meta['parent'] ?? null;
                $parentOn = !$parent || ($byKey->get($parent)?->is_enabled ?? false);
                $enabled = $row && $row->is_enabled && $parentOn;
                $unlimited = $enabled && ($row->is_unlimited ?? false);
                $limitValue = null;
                $limitLabel = null;

                if ($meta['type'] === 'limited') {
                    if (!$enabled) {
                        $limitLabel = 'Not included';
                    } elseif ($unlimited) {
                        $limitLabel = 'Unlimited';
                    } else {
                        $limitValue = $row->limit_value !== null
                            ? (int) $row->limit_value
                            : (int) ($meta['default_limit'] ?? 0);
                        $limitLabel = (string) $limitValue;
                    }
                } else {
                    $limitLabel = $enabled ? 'Included' : 'Not included';
                }

                $item = [
                    'module_key' => $key,
                    'label' => $meta['label'],
                    'category' => $category,
                    'type' => $meta['type'],
                    'is_enabled' => (bool) $enabled,
                    'is_unlimited' => (bool) $unlimited,
                    'limit_value' => $limitValue,
                    'limit_label' => $limitLabel,
                ];
                $items[] = $item;
                if ($enabled) {
                    $included[] = $item;
                } else {
                    $excluded[] = $item;
                }
            }
            $groups[] = [
                'category' => $category,
                'modules' => $items,
            ];
        }

        $highlightKeys = [
            'inventory', 'pos', 'accounting', 'hrm', 'payroll', 'service-management',
            'branch', 'user', 'warehouse', 'product', 'order', 'customer', 'employee',
        ];
        $flat = collect($groups)->pluck('modules')->flatten(1)->keyBy('module_key');
        $highlights = [];
        foreach ($highlightKeys as $key) {
            if ($flat->has($key)) {
                $highlights[] = $flat->get($key);
            }
        }

        return compact('groups', 'included', 'excluded', 'highlights');
    }

    public function modules(?string $category = null, ?bool $featured = null)
    {
        $q = IntroModule::where('is_deleted', 0)->where('status', Status::ACTIVE)->orderBy('display_order');
        if ($category) {
            $q->where('category', $category);
        }
        if ($featured !== null) {
            $q->where('is_featured', $featured);
        }
        return $q->get();
    }

    public function moduleBySlug(string $slug)
    {
        return IntroModule::where('slug', $slug)->where('is_deleted', 0)->where('status', Status::ACTIVE)->firstOrFail();
    }

    public function blogs(array $filters = [])
    {
        $q = IntroBlog::with(['category', 'tags', 'author'])
            ->where('is_deleted', 0)
            ->where('status', 'published')
            ->where(function ($w) {
                $w->whereNull('published_at')->orWhere('published_at', '<=', now());
            })
            ->orderByDesc('published_at');

        if (!empty($filters['category'])) {
            $q->whereHas('category', fn ($c) => $c->where('slug', $filters['category'])->orWhere('name', $filters['category']));
        }
        if (!empty($filters['tag'])) {
            $q->whereHas('tags', fn ($t) => $t->where('slug', $filters['tag'])->orWhere('name', $filters['tag']));
        }
        if (!empty($filters['featured'])) {
            $q->where('is_featured', 1);
        }
        if (!empty($filters['q'])) {
            $s = $filters['q'];
            $q->where(function ($w) use ($s) {
                $w->where('title', 'like', "%{$s}%")
                    ->orWhere('excerpt', 'like', "%{$s}%")
                    ->orWhere('content', 'like', "%{$s}%")
                    ->orWhere('meta_keywords', 'like', "%{$s}%");
            });
        }

        return $q->get()->map(fn ($b) => $this->mapBlog($b, false));
    }

    public function blogBySlug(string $slug)
    {
        $blog = IntroBlog::with(['category', 'tags', 'author'])
            ->where('slug', $slug)
            ->where('is_deleted', 0)
            ->where('status', 'published')
            ->firstOrFail();

        $data = $this->mapBlog($blog, true);
        $data['comments'] = $this->comments->approvedForBlog($blog->intro_blog_id)->map(fn ($c) => [
            'id' => $c->intro_blog_comment_id,
            'name' => $c->name,
            'comment' => $c->comment,
            'date_created' => $c->date_created,
        ])->values();
        return $data;
    }

    protected function mapBlog(IntroBlog $b, bool $full): array
    {
        $content = $b->content;
        $decoded = json_decode($content, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $content = $decoded;
        }

        $data = [
            'id' => $b->intro_blog_id,
            'slug' => $b->slug,
            'title' => $b->title,
            'excerpt' => $b->excerpt,
            'featured_image' => $b->featured_image_url,
            'reading_time' => $b->reading_time,
            'published_at' => $b->published_at,
            'is_featured' => (bool) $b->is_featured,
            'category' => $b->category ? [
                'id' => $b->category->intro_blog_category_id,
                'name' => $b->category->name,
                'slug' => $b->category->slug,
            ] : null,
            'tags' => $b->tags->map(fn ($t) => [
                'id' => $t->intro_blog_tag_id,
                'name' => $t->name,
                'slug' => $t->slug,
            ])->values(),
            'author' => $b->author ? [
                'id' => $b->author->id,
                'name' => $b->author->name ?? ($b->author->username ?? null),
            ] : null,
            'seo' => [
                'seo_title' => $b->seo_title,
                'meta_description' => $b->meta_description,
                'meta_keywords' => $b->meta_keywords,
                'canonical_url' => $b->canonical_url,
                'og_title' => $b->og_title,
                'og_description' => $b->og_description,
                'og_image' => $b->og_image_url,
            ],
        ];
        if ($full) {
            $data['content'] = $content;
        }
        return $data;
    }

    public function blogCategories()
    {
        return IntroBlogCategory::where('is_deleted', 0)->where('status', Status::ACTIVE)->orderBy('display_order')->get();
    }

    public function blogTags()
    {
        return IntroBlogTag::where('is_deleted', 0)->where('status', Status::ACTIVE)->orderBy('name')->get();
    }

    public function submitComment(array $data)
    {
        $enabled = $this->settings->get('comments_enabled', '1');
        if ($enabled === '0' || $enabled === 'false') {
            throw new Exception('Blog comments are currently disabled.');
        }
        $blog = IntroBlog::where('is_deleted', 0)
            ->where('status', 'published')
            ->where(function ($q) use ($data) {
                if (!empty($data['intro_blog_id'])) {
                    $q->where('intro_blog_id', $data['intro_blog_id']);
                }
                if (!empty($data['blog_slug'])) {
                    $q->orWhere('slug', $data['blog_slug']);
                }
            })
            ->firstOrFail();

        return $this->comments->create([
            'intro_blog_id' => $blog->intro_blog_id,
            'name' => $data['name'],
            'email' => $data['email'],
            'comment' => $data['comment'],
            'ip_address' => $data['ip_address'] ?? null,
        ]);
    }

    public function testimonials()
    {
        return IntroTestimonial::where('is_deleted', 0)
            ->where('status', Status::ACTIVE)
            ->orderBy('display_order')
            ->get();
    }

    public function submitContact(array $data)
    {
        return $this->contact->create($data);
    }

    public function websiteSettings()
    {
        return $this->settings->publicMap();
    }

    public function navigation(?string $location = null)
    {
        $q = IntroNavigationItem::with('children')
            ->where('is_deleted', 0)
            ->where('status', Status::ACTIVE)
            ->whereNull('parent_id')
            ->orderBy('display_order');
        if ($location) {
            $q->where('location', $location);
        }
        return $q->get();
    }

    public function pages()
    {
        return IntroPage::where('is_deleted', 0)->where('status', 'published')->orderBy('title')->get();
    }

    public function pageBySlug(string $slug)
    {
        return IntroPage::where('slug', $slug)->where('is_deleted', 0)->where('status', 'published')->firstOrFail();
    }

    public function homepage()
    {
        return IntroHomepageSection::where('is_deleted', 0)
            ->where('is_enabled', 1)
            ->where('status', Status::ACTIVE)
            ->orderBy('display_order')
            ->get();
    }

    public function registerBusiness(array $data)
    {
        return $this->registrations->registerFromIntro($data);
    }
}
