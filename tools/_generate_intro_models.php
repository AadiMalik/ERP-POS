<?php

$base = dirname(__DIR__);
$models = [
    'IntroModule' => [
        'pk' => 'intro_module_id',
        'table' => 'intro_modules',
        'upload' => ['icon' => 'intro/modules', 'image' => 'intro/modules'],
        'fields' => ['name', 'slug', 'description', 'icon', 'image', 'category', 'display_order', 'is_featured', 'status'],
        'casts' => ['is_featured' => 'boolean'],
    ],
    'IntroBlogCategory' => [
        'pk' => 'intro_blog_category_id',
        'table' => 'intro_blog_categories',
        'fields' => ['name', 'slug', 'description', 'display_order', 'status', 'seo_title', 'meta_description'],
    ],
    'IntroBlogTag' => [
        'pk' => 'intro_blog_tag_id',
        'table' => 'intro_blog_tags',
        'fields' => ['name', 'slug', 'status'],
    ],
    'IntroBlog' => [
        'pk' => 'intro_blog_id',
        'table' => 'intro_blogs',
        'upload' => ['featured_image' => 'intro/blog', 'og_image' => 'intro/blog'],
        'fields' => [
            'intro_blog_category_id', 'author_id', 'title', 'slug', 'content', 'excerpt', 'featured_image',
            'reading_time', 'published_at', 'status', 'is_featured', 'seo_title', 'meta_description',
            'meta_keywords', 'canonical_url', 'og_title', 'og_description', 'og_image',
        ],
        'casts' => ['is_featured' => 'boolean', 'published_at' => 'datetime'],
        'relations' => true,
    ],
    'IntroBlogComment' => [
        'pk' => 'intro_blog_comment_id',
        'table' => 'intro_blog_comments',
        'fields' => [
            'intro_blog_id', 'name', 'email', 'comment', 'status', 'moderation_note',
            'moderatedby_id', 'moderated_at', 'ip_address',
        ],
        'no_update_audit' => true,
        'casts' => ['moderated_at' => 'datetime'],
    ],
    'IntroTestimonial' => [
        'pk' => 'intro_testimonial_id',
        'table' => 'intro_testimonials',
        'upload' => ['image' => 'intro/testimonials'],
        'fields' => [
            'business_name', 'customer_name', 'designation', 'business_type', 'review_text',
            'rating', 'image', 'display_order', 'status',
        ],
    ],
    'IntroContactInquiry' => [
        'pk' => 'intro_contact_inquiry_id',
        'table' => 'intro_contact_inquiries',
        'fields' => ['name', 'email', 'phone', 'subject', 'message', 'status'],
        'no_update_audit' => true,
    ],
    'IntroContactReply' => [
        'pk' => 'intro_contact_reply_id',
        'table' => 'intro_contact_replies',
        'fields' => ['intro_contact_inquiry_id', 'reply_message', 'send_status', 'error_message', 'repliedby_id'],
        'no_soft' => true,
    ],
    'IntroWebsiteSetting' => [
        'pk' => 'intro_website_setting_id',
        'table' => 'intro_website_settings',
        'fields' => ['group', 'key', 'value', 'type', 'label'],
        'no_soft' => true,
    ],
    'IntroNavigationItem' => [
        'pk' => 'intro_navigation_item_id',
        'table' => 'intro_navigation_items',
        'fields' => ['label', 'url', 'section_key', 'match_key', 'location', 'parent_id', 'display_order', 'status'],
    ],
    'IntroMedia' => [
        'pk' => 'intro_media_id',
        'table' => 'intro_media',
        'fields' => ['filename', 'original_name', 'disk_path', 'mime_type', 'collection', 'alt_text', 'size'],
        'no_update_audit' => true,
        'upload_url' => true,
    ],
    'IntroHomepageSection' => [
        'pk' => 'intro_homepage_section_id',
        'table' => 'intro_homepage_sections',
        'upload' => ['image' => 'intro/sections'],
        'fields' => [
            'section_key', 'title', 'subtitle', 'content', 'content_json', 'image',
            'button_text', 'button_link', 'display_order', 'is_enabled', 'status',
        ],
        'casts' => ['content_json' => 'array', 'is_enabled' => 'boolean'],
    ],
    'IntroPage' => [
        'pk' => 'intro_page_id',
        'table' => 'intro_pages',
        'upload' => ['og_image' => 'intro/pages'],
        'fields' => [
            'title', 'slug', 'content', 'status', 'seo_title', 'meta_description', 'meta_keywords',
            'canonical_url', 'og_title', 'og_description', 'og_image', 'robots_index', 'robots_follow',
        ],
        'casts' => ['robots_index' => 'boolean', 'robots_follow' => 'boolean'],
    ],
    'IntroBusinessRegistration' => [
        'pk' => 'intro_business_registration_id',
        'table' => 'intro_business_registrations',
        'fields' => [
            'business_id', 'package_id', 'billing_cycle', 'business_name', 'owner_name', 'owner_email',
            'owner_phone', 'business_email', 'business_phone', 'business_type', 'city', 'address',
            'notes', 'status', 'meta',
        ],
        'casts' => ['meta' => 'array'],
    ],
];

foreach ($models as $class => $cfg) {
    $pk = $cfg['pk'];
    $table = $cfg['table'];
    $fill = $cfg['fields'];
    if (empty($cfg['no_soft'])) {
        $fill = array_merge($fill, [
            'is_deleted', 'createdby_id', 'updatedby_id', 'deletedby_id',
            'date_created', 'date_updated', 'date_deleted',
        ]);
        if (!empty($cfg['no_update_audit'])) {
            $fill = array_values(array_diff($fill, ['updatedby_id', 'date_updated', 'createdby_id']));
            if (!in_array('createdby_id', $cfg['fields'], true) && $class !== 'IntroBlogComment' && $class !== 'IntroContactInquiry' && $class !== 'IntroMedia') {
                // keep as is
            }
            if ($class === 'IntroBlogComment') {
                $fill = array_merge($cfg['fields'], ['is_deleted', 'deletedby_id', 'date_created', 'date_deleted']);
            } elseif ($class === 'IntroContactInquiry') {
                $fill = array_merge($cfg['fields'], ['is_deleted', 'deletedby_id', 'date_created', 'date_deleted']);
            } elseif ($class === 'IntroMedia') {
                $fill = array_merge($cfg['fields'], ['is_deleted', 'createdby_id', 'deletedby_id', 'date_created', 'date_deleted']);
            }
        }
    } else {
        if ($class === 'IntroContactReply') {
            $fill = array_merge($cfg['fields'], ['date_created']);
        } elseif ($class === 'IntroWebsiteSetting') {
            $fill = array_merge($cfg['fields'], ['updatedby_id', 'date_updated']);
        }
    }
    $fill = array_values(array_unique($fill));

    $fillLines = implode(",\n        ", array_map(fn ($f) => "'{$f}'", $fill));

    $appendBlock = '';
    $accessorBlock = '';
    $apps = [];
    if (!empty($cfg['upload'])) {
        foreach ($cfg['upload'] as $field => $dir) {
            $apps[] = $field . '_url';
            $method = 'get' . str_replace(' ', '', ucwords(str_replace('_', ' ', $field))) . 'UrlAttribute';
            $accessorBlock .= <<<PHP

    public function {$method}()
    {
        return !empty(\$this->{$field}) ? asset('public/uploads/{$dir}/' . \$this->{$field}) : null;
    }

PHP;
        }
    }
    if (!empty($cfg['upload_url'])) {
        $apps[] = 'url';
        $accessorBlock .= <<<'PHP'

    public function getUrlAttribute()
    {
        return !empty($this->filename) ? asset('public/uploads/intro/media/' . $this->filename) : null;
    }

PHP;
    }
    if ($apps) {
        $list = "'" . implode("', '", $apps) . "'";
        $appendBlock = "\n    protected \$appends = [{$list}];\n";
    }

    $castsBlock = '';
    if (!empty($cfg['casts'])) {
        $parts = [];
        foreach ($cfg['casts'] as $k => $v) {
            $parts[] = "'{$k}' => '{$v}'";
        }
        $castsBlock = "\n    protected \$casts = [\n        " . implode(",\n        ", $parts) . "\n    ];\n";
    }

    $relations = '';
    if ($class === 'IntroBlog') {
        $relations = <<<'PHP'

    public function category()
    {
        return $this->belongsTo(IntroBlogCategory::class, 'intro_blog_category_id', 'intro_blog_category_id');
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function tags()
    {
        return $this->belongsToMany(
            IntroBlogTag::class,
            'intro_blog_tag',
            'intro_blog_id',
            'intro_blog_tag_id'
        );
    }

    public function comments()
    {
        return $this->hasMany(IntroBlogComment::class, 'intro_blog_id', 'intro_blog_id');
    }

PHP;
    } elseif ($class === 'IntroBlogComment') {
        $relations = <<<'PHP'

    public function blog()
    {
        return $this->belongsTo(IntroBlog::class, 'intro_blog_id', 'intro_blog_id');
    }

PHP;
    } elseif ($class === 'IntroContactInquiry') {
        $relations = <<<'PHP'

    public function replies()
    {
        return $this->hasMany(IntroContactReply::class, 'intro_contact_inquiry_id', 'intro_contact_inquiry_id');
    }

PHP;
    } elseif ($class === 'IntroContactReply') {
        $relations = <<<'PHP'

    public function inquiry()
    {
        return $this->belongsTo(IntroContactInquiry::class, 'intro_contact_inquiry_id', 'intro_contact_inquiry_id');
    }

    public function repliedby()
    {
        return $this->belongsTo(User::class, 'repliedby_id');
    }

PHP;
    } elseif ($class === 'IntroBusinessRegistration') {
        $relations = <<<'PHP'

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id', 'business_id');
    }

    public function package()
    {
        return $this->belongsTo(Package::class, 'package_id', 'package_id');
    }

PHP;
    } elseif ($class === 'IntroNavigationItem') {
        $relations = <<<'PHP'

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id', 'intro_navigation_item_id')
            ->where('is_deleted', 0)
            ->orderBy('display_order');
    }

PHP;
    }

    $code = <<<PHP
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class {$class} extends Model
{
    use HasFactory;

    public \$timestamps = false;
    protected \$table = '{$table}';
    protected \$primaryKey = '{$pk}';
    protected \$keyType = 'string';
    public \$incrementing = false;

    protected \$fillable = [
        {$fillLines}
    ];
{$appendBlock}{$castsBlock}{$accessorBlock}{$relations}}

PHP;

    file_put_contents("{$base}/app/Models/{$class}.php", $code);
    echo "Wrote {$class}\n";
}

echo "OK\n";
