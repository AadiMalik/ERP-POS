<?php

namespace Database\Seeders;

use App\Enums\Status;
use App\Models\IntroBlog;
use App\Models\IntroBlogCategory;
use App\Models\IntroBlogComment;
use App\Models\IntroBlogTag;
use App\Models\IntroHomepageSection;
use App\Models\IntroModule;
use App\Models\IntroNavigationItem;
use App\Models\IntroPage;
use App\Models\IntroTestimonial;
use App\Models\IntroWebsiteSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeds Intro CMS tables from the current Dukanaz Intro website demo content.
 * Packages are NOT seeded — public Intro API reads existing ERP `packages`.
 */
class IntroCmsSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/intro_website_content.json');
        if (!file_exists($path)) {
            $this->command?->error('Missing intro_website_content.json — run: node tools/export_intro_seed_data.mjs');
            return;
        }

        $data = json_decode(file_get_contents($path), true);
        if (!$data) {
            $this->command?->error('Invalid intro_website_content.json');
            return;
        }

        $this->seedSettings($data);
        $this->seedNavigation($data);
        $this->seedModules($data);
        $this->seedTestimonials($data);
        $this->seedHomepageSections($data);
        $categoryMap = $this->seedBlogCategories($data);
        $tagMap = $this->seedBlogTags($data);
        $blogMap = $this->seedBlogs($data, $categoryMap, $tagMap);
        $this->seedComments($data, $blogMap);
        $this->seedPages($data);

        $this->command?->info('Intro CMS content seeded (packages skipped — using ERP packages).');
    }

    protected function seedSettings(array $data): void
    {
        $site = $data['site'] ?? [];
        $bank = $data['bankDetails'] ?? [];

        $pairs = [
            'brand_name' => $site['name'] ?? 'Dukanaz',
            'brand_description' => $site['tagline'] ?? '',
            'email' => $site['email'] ?? 'hello@dukanaz.com',
            'phone' => $site['phone'] ?? '',
            'phone_hours' => $site['phoneHours'] ?? '',
            'copyright_note' => $site['copyrightNote'] ?? '',
            'currency' => 'PKR',
            'comments_enabled' => '1',
            'comments_require_moderation' => '1',
            'default_seo_title' => 'Dukanaz — Business Operating System',
            'default_meta_description' => $site['tagline'] ?? '',
            'bank_name' => $bank['bankName'] ?? '',
            'bank_account_title' => $bank['accountTitle'] ?? '',
            'bank_account_number' => $bank['accountNumber'] ?? '',
            'bank_iban' => $bank['iban'] ?? '',
            'bank_branch' => $bank['branch'] ?? '',
            'bank_branch_code' => $bank['branchCode'] ?? '',
            'bank_swift' => $bank['swift'] ?? '',
            'bank_instructions' => json_encode($bank['instructions'] ?? []),
            'social_facebook' => '',
            'social_instagram' => '',
            'social_linkedin' => '',
            'social_twitter' => '',
            'social_youtube' => '',
            'social_github' => '',
            'website_url' => '',
            'address' => '',
            'logo' => '',
            'logo_light' => '',
            'favicon' => '',
            'og_image' => '',
        ];

        foreach ($pairs as $key => $value) {
            IntroWebsiteSetting::updateOrCreate(
                ['key' => $key],
                [
                    'intro_website_setting_id' => IntroWebsiteSetting::where('key', $key)->value('intro_website_setting_id') ?: generateUuid(),
                    'group' => $this->settingGroup($key),
                    'value' => is_array($value) ? json_encode($value) : $value,
                    'type' => is_array($value) || str_starts_with((string) $value, '[') || str_starts_with((string) $value, '{') ? 'json' : (in_array($key, ['comments_enabled', 'comments_require_moderation'], true) ? 'boolean' : 'text'),
                    'label' => ucwords(str_replace('_', ' ', $key)),
                    'date_updated' => now(),
                ]
            );
        }
    }

    protected function settingGroup(string $key): string
    {
        if (str_starts_with($key, 'bank_')) {
            return 'bank';
        }
        if (str_starts_with($key, 'social_')) {
            return 'social';
        }
        if (str_contains($key, 'seo') || str_contains($key, 'meta') || $key === 'og_image') {
            return 'seo';
        }
        if (str_starts_with($key, 'comments_')) {
            return 'blog';
        }
        if (in_array($key, ['logo', 'logo_light', 'favicon'], true)) {
            return 'branding';
        }
        return 'general';
    }

    protected function seedNavigation(array $data): void
    {
        $upsertNav = function (string $label, string $location, array $attrs) {
            $existing = IntroNavigationItem::where('label', $label)
                ->where('location', $location)
                ->where('is_deleted', 0)
                ->first();
            $attrs['intro_navigation_item_id'] = $existing?->intro_navigation_item_id ?: generateUuid();
            $attrs['label'] = $label;
            $attrs['location'] = $location;
            $attrs['status'] = Status::ACTIVE;
            $attrs['is_deleted'] = 0;
            if (!$existing) {
                $attrs['date_created'] = now();
            } else {
                $attrs['date_updated'] = now();
            }
            IntroNavigationItem::updateOrCreate(
                ['intro_navigation_item_id' => $attrs['intro_navigation_item_id']],
                $attrs
            );
        };

        $order = 0;
        foreach ($data['navLinks'] ?? [] as $link) {
            $order++;
            $upsertNav($link['label'], 'header', [
                'url' => $link['to'] ?? null,
                'match_key' => $link['match'] ?? null,
                'section_key' => null,
                'display_order' => $order,
            ]);
        }

        $upsertNav('Request Access', 'header', [
            'url' => '/business-registration',
            'match_key' => 'cta',
            'display_order' => $order + 1,
        ]);

        $deckOrder = 0;
        foreach ($data['deckItems'] ?? [] as $item) {
            $deckOrder++;
            $upsertNav($item['title'], 'deck', [
                'url' => $item['to'] ?? null,
                'section_key' => $item['tag'] ?? null,
                'display_order' => $deckOrder,
            ]);
        }

        $footer = [
            ['Packages & Pricing', '/pricing'],
            ['Blog', '/blog'],
            ['Contact', '/#contact'],
            ['Customer Reviews', '/#testimonials'],
            ['Register Your Business', '/business-registration'],
        ];
        foreach ($footer as $i => [$label, $url]) {
            $upsertNav($label, 'footer', [
                'url' => $url,
                'display_order' => $i + 1,
            ]);
        }
    }

    protected function seedModules(array $data): void
    {
        // Rail modules (40) — primary website module list
        foreach ($data['railModules'] ?? [] as $i => $m) {
            $slug = Str::slug($m['name']);
            IntroModule::updateOrCreate(
                ['slug' => $slug, 'is_deleted' => 0],
                [
                    'intro_module_id' => IntroModule::where('slug', $slug)->value('intro_module_id') ?: generateUuid(),
                    'name' => $m['name'],
                    'description' => $m['desc'] ?? null,
                    'category' => 'rail',
                    'icon' => null,
                    'display_order' => (int) ($m['i'] ?? ($i + 1)),
                    'is_featured' => false,
                    'status' => Status::ACTIVE,
                    'date_created' => now(),
                ]
            );
        }

        // Explorer cards — featured / explorer catalog
        foreach ($data['explorerCards'] ?? [] as $i => $m) {
            $slug = 'explorer-' . Str::slug($m['name']);
            IntroModule::updateOrCreate(
                ['slug' => $slug, 'is_deleted' => 0],
                [
                    'intro_module_id' => IntroModule::where('slug', $slug)->value('intro_module_id') ?: generateUuid(),
                    'name' => $m['name'],
                    'description' => $m['desc'] ?? null,
                    'category' => $m['group'] ?? 'explorer',
                    'icon' => $m['icon'] ?? null,
                    'display_order' => $i + 1,
                    'is_featured' => true,
                    'status' => Status::ACTIVE,
                    'date_created' => now(),
                ]
            );
        }
    }

    protected function seedTestimonials(array $data): void
    {
        foreach ($data['testimonials'] ?? [] as $i => $t) {
            $existing = IntroTestimonial::where('customer_name', $t['name'])
                ->where('business_name', $t['business'])
                ->where('is_deleted', 0)
                ->first();
            IntroTestimonial::updateOrCreate(
                ['intro_testimonial_id' => $existing?->intro_testimonial_id ?: generateUuid()],
                [
                    'business_name' => $t['business'],
                    'customer_name' => $t['name'],
                    'designation' => $t['designation'] ?? null,
                    'business_type' => $t['type'] ?? null,
                    'review_text' => $t['text'],
                    'rating' => $t['rating'] ?? 5,
                    'display_order' => $i + 1,
                    'status' => Status::ACTIVE,
                    'is_deleted' => 0,
                    'date_created' => $existing?->date_created ?: now(),
                ]
            );
        }
    }

    protected function seedHomepageSections(array $data): void
    {
        $sections = [
            [
                'section_key' => 'hero',
                'title' => 'Dukanaz',
                'subtitle' => $data['site']['tagline'] ?? '',
                'content' => null,
                'content_json' => ['cta_primary' => 'Request Access', 'cta_primary_link' => '/business-registration'],
                'display_order' => 1,
            ],
            [
                'section_key' => 'ticker',
                'title' => 'Live Revenue Stream',
                'subtitle' => null,
                'content' => null,
                'content_json' => ['items' => $data['tickerItems'] ?? []],
                'display_order' => 2,
            ],
            [
                'section_key' => 'rail',
                'title' => 'Every Module',
                'subtitle' => 'Forty modules. One shared core.',
                'content' => null,
                'content_json' => null,
                'display_order' => 3,
            ],
            [
                'section_key' => 'orbit',
                'title' => 'Living Core',
                'subtitle' => null,
                'content' => null,
                'content_json' => ['nodes' => $data['orbitNodes'] ?? []],
                'display_order' => 4,
            ],
            [
                'section_key' => 'explorer',
                'title' => 'Module Explorer',
                'subtitle' => null,
                'content' => null,
                'content_json' => ['groups' => $data['explorerGroups'] ?? []],
                'display_order' => 5,
            ],
            [
                'section_key' => 'business',
                'title' => 'Built for your business type',
                'subtitle' => 'Mart, Retail, Wholesale, Distribution & B2B',
                'content' => null,
                'content_json' => ['types' => $data['businessTypes'] ?? []],
                'display_order' => 6,
            ],
            [
                'section_key' => 'testimonials',
                'title' => 'Customer Reviews',
                'subtitle' => 'Operators across marts, retail, wholesale and B2B',
                'content' => null,
                'content_json' => null,
                'display_order' => 7,
            ],
            [
                'section_key' => 'pricing',
                'title' => 'Transparent PKR Pricing',
                'subtitle' => 'Packages come from the ERP package catalog — not duplicated in Intro CMS.',
                'content' => null,
                'content_json' => ['faqs' => $data['pricingFaqs'] ?? []],
                'display_order' => 8,
            ],
            [
                'section_key' => 'faq',
                'title' => 'Frequently Asked Questions',
                'subtitle' => null,
                'content' => null,
                'content_json' => ['faqs' => $data['homeFaqs'] ?? []],
                'display_order' => 9,
            ],
            [
                'section_key' => 'contact',
                'title' => 'Request Access',
                'subtitle' => 'Talk to us about provisioning your business on Dukanaz.',
                'content' => null,
                'content_json' => null,
                'display_order' => 10,
            ],
            [
                'section_key' => 'journey',
                'title' => 'Subscription Journey',
                'subtitle' => null,
                'content' => null,
                'content_json' => ['steps' => $data['journeySteps'] ?? []],
                'display_order' => 11,
            ],
        ];

        foreach ($sections as $s) {
            IntroHomepageSection::updateOrCreate(
                ['section_key' => $s['section_key'], 'is_deleted' => 0],
                [
                    'intro_homepage_section_id' => IntroHomepageSection::where('section_key', $s['section_key'])->value('intro_homepage_section_id') ?: generateUuid(),
                    'title' => $s['title'],
                    'subtitle' => $s['subtitle'],
                    'content' => $s['content'],
                    'content_json' => $s['content_json'],
                    'display_order' => $s['display_order'],
                    'is_enabled' => true,
                    'status' => Status::ACTIVE,
                    'date_created' => now(),
                ]
            );
        }
    }

    protected function seedBlogCategories(array $data): array
    {
        $map = [];
        foreach ($data['blogCategories'] ?? [] as $i => $name) {
            $slug = Str::slug($name);
            $row = IntroBlogCategory::updateOrCreate(
                ['slug' => $slug, 'is_deleted' => 0],
                [
                    'intro_blog_category_id' => IntroBlogCategory::where('slug', $slug)->value('intro_blog_category_id') ?: generateUuid(),
                    'name' => $name,
                    'display_order' => $i + 1,
                    'status' => Status::ACTIVE,
                    'date_created' => now(),
                ]
            );
            $map[$name] = $row->intro_blog_category_id;
        }
        return $map;
    }

    protected function seedBlogTags(array $data): array
    {
        $names = [];
        foreach ($data['blogPosts'] ?? [] as $post) {
            foreach ($post['tags'] ?? [] as $tag) {
                $names[$tag] = true;
            }
        }
        $map = [];
        foreach (array_keys($names) as $name) {
            $slug = Str::slug($name);
            $row = IntroBlogTag::updateOrCreate(
                ['slug' => $slug, 'is_deleted' => 0],
                [
                    'intro_blog_tag_id' => IntroBlogTag::where('slug', $slug)->value('intro_blog_tag_id') ?: generateUuid(),
                    'name' => $name,
                    'status' => Status::ACTIVE,
                    'date_created' => now(),
                ]
            );
            $map[$name] = $row->intro_blog_tag_id;
        }
        return $map;
    }

    protected function seedBlogs(array $data, array $categoryMap, array $tagMap): array
    {
        $blogMap = [];
        foreach ($data['blogPosts'] ?? [] as $post) {
            $slug = $post['slug'];
            $keywords = is_array($post['keywords'] ?? null) ? implode(', ', $post['keywords']) : ($post['keywords'] ?? null);
            $row = IntroBlog::updateOrCreate(
                ['slug' => $slug, 'is_deleted' => 0],
                [
                    'intro_blog_id' => IntroBlog::where('slug', $slug)->value('intro_blog_id') ?: generateUuid(),
                    'intro_blog_category_id' => $categoryMap[$post['category'] ?? ''] ?? null,
                    'title' => $post['title'],
                    'excerpt' => $post['excerpt'] ?? null,
                    'content' => json_encode($post['body'] ?? []),
                    'reading_time' => $post['readMinutes'] ?? null,
                    'published_at' => !empty($post['publishedAt']) ? $post['publishedAt'] . ' 10:00:00' : now(),
                    'status' => 'published',
                    'is_featured' => !empty($post['featured']),
                    'seo_title' => $post['title'],
                    'meta_description' => $post['excerpt'] ?? null,
                    'meta_keywords' => $keywords,
                    'og_title' => $post['title'],
                    'og_description' => $post['excerpt'] ?? null,
                    'date_created' => now(),
                ]
            );

            $tagIds = [];
            foreach ($post['tags'] ?? [] as $tagName) {
                if (!empty($tagMap[$tagName])) {
                    $tagIds[] = $tagMap[$tagName];
                }
            }
            $row->tags()->sync($tagIds);
            $blogMap[$slug] = $row->intro_blog_id;
        }
        return $blogMap;
    }

    protected function seedComments(array $data, array $blogMap): void
    {
        foreach ($data['seedComments'] ?? [] as $slug => $comments) {
            if (empty($blogMap[$slug])) {
                continue;
            }
            foreach ($comments as $c) {
                IntroBlogComment::updateOrCreate(
                    [
                        'intro_blog_id' => $blogMap[$slug],
                        'name' => $c['name'],
                        'comment' => $c['body'],
                        'is_deleted' => 0,
                    ],
                    [
                        'intro_blog_comment_id' => generateUuid(),
                        'email' => Str::slug($c['name']) . '@example.com',
                        'status' => 'approved',
                        'date_created' => !empty($c['createdAt'])
                            ? \Carbon\Carbon::parse($c['createdAt'])->format('Y-m-d H:i:s')
                            : now(),
                    ]
                );
            }
        }
    }

    protected function seedPages(array $data): void
    {
        $pages = [
            [
                'title' => 'Home',
                'slug' => 'home',
                'seo_title' => 'Dukanaz — Business Operating System',
                'meta_description' => $data['site']['tagline'] ?? '',
            ],
            [
                'title' => 'Pricing',
                'slug' => 'pricing',
                'seo_title' => 'Dukanaz Pricing — PKR Packages',
                'meta_description' => 'Transparent PKR pricing with clear user, branch and product limits.',
            ],
            [
                'title' => 'Blog',
                'slug' => 'blog',
                'seo_title' => 'Dukanaz Insights',
                'meta_description' => 'Retail, wholesale, inventory and ERP insights for growing businesses.',
            ],
            [
                'title' => 'Business Registration',
                'slug' => 'business-registration',
                'seo_title' => 'Register Your Business — Dukanaz',
                'meta_description' => 'Register your mart, retail, wholesale or B2B business on Dukanaz.',
            ],
        ];

        foreach ($pages as $i => $p) {
            IntroPage::updateOrCreate(
                ['slug' => $p['slug'], 'is_deleted' => 0],
                [
                    'intro_page_id' => IntroPage::where('slug', $p['slug'])->value('intro_page_id') ?: generateUuid(),
                    'title' => $p['title'],
                    'content' => null,
                    'status' => 'published',
                    'seo_title' => $p['seo_title'],
                    'meta_description' => $p['meta_description'],
                    'og_title' => $p['seo_title'],
                    'og_description' => $p['meta_description'],
                    'robots_index' => true,
                    'robots_follow' => true,
                    'date_created' => now(),
                ]
            );
        }
    }
}
