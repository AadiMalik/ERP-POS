<?php
/**
 * Generates Intro CMS Admin/API services, controllers, routes wiring,
 * permissions snippet file, and default content seeder.
 * Run: php tools/_generate_intro_cms.php
 */
$base = dirname(__DIR__);

function write_file(string $path, string $contents): void
{
    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    file_put_contents($path, $contents);
    echo "Wrote {$path}\n";
}

// ---------------------------------------------------------------------------
// Admin soft-delete concern
// ---------------------------------------------------------------------------
write_file("{$base}/app/Services/Concrete/Admin/Intro/Concerns/IntroAuditable.php", <<<'PHP'
<?php

namespace App\Services\Concrete\Admin\Intro\Concerns;

use Illuminate\Support\Facades\Auth;

trait IntroAuditable
{
    protected function createAudit(array $obj): array
    {
        $obj['createdby_id'] = Auth::id();
        $obj['date_created'] = now();
        return $obj;
    }

    protected function updateAudit(array $obj): array
    {
        $obj['updatedby_id'] = Auth::id();
        $obj['date_updated'] = now();
        return $obj;
    }

    protected function deleteAudit(): array
    {
        return [
            'is_deleted' => 1,
            'deletedby_id' => Auth::id(),
            'date_deleted' => now(),
        ];
    }
}

PHP);

// ---------------------------------------------------------------------------
// Admin services (compact but complete)
// ---------------------------------------------------------------------------
$adminServices = [
    'ModuleService' => ['model' => 'IntroModule', 'pk' => 'intro_module_id', 'order' => 'display_order', 'label' => 'name', 'hasStatus' => true, 'hasFeature' => true],
    'BlogCategoryService' => ['model' => 'IntroBlogCategory', 'pk' => 'intro_blog_category_id', 'order' => 'display_order', 'label' => 'name', 'hasStatus' => true],
    'BlogTagService' => ['model' => 'IntroBlogTag', 'pk' => 'intro_blog_tag_id', 'order' => 'name', 'label' => 'name', 'hasStatus' => true],
    'TestimonialService' => ['model' => 'IntroTestimonial', 'pk' => 'intro_testimonial_id', 'order' => 'display_order', 'label' => 'customer_name', 'hasStatus' => true],
    'NavigationService' => ['model' => 'IntroNavigationItem', 'pk' => 'intro_navigation_item_id', 'order' => 'display_order', 'label' => 'label', 'hasStatus' => true],
    'HomepageSectionService' => ['model' => 'IntroHomepageSection', 'pk' => 'intro_homepage_section_id', 'order' => 'display_order', 'label' => 'title', 'hasStatus' => true, 'hasEnable' => true],
    'PageService' => ['model' => 'IntroPage', 'pk' => 'intro_page_id', 'order' => 'date_created', 'label' => 'title', 'hasStatus' => false],
];

foreach ($adminServices as $svc => $cfg) {
    $model = $cfg['model'];
    $pk = $cfg['pk'];
    $order = $cfg['order'];
    $label = $cfg['label'];
    $statusCol = '';
    $statusMethod = '';
    $featureMethod = '';
    $enableMethod = '';
    if (!empty($cfg['hasStatus'])) {
        $statusCol = <<<PHP

            ->addColumn('status', function (\$item) {
                \$checked = \$item->status == Status::ACTIVE ? 'checked' : '';
                return '<div class="form-check form-switch mb-0"><input class="form-check-input statusToggle" type="checkbox" data-id="' . \$item->{$pk} . '" ' . \$checked . '></div>';
            })
PHP;
        $statusMethod = <<<PHP

    public function status(\$id)
    {
        \$row = \$this->repo->find(\$id);
        return \$this->repo->update([
            'status' => (\$row->status == Status::ACTIVE ? Status::INACTIVE : Status::ACTIVE),
            'updatedby_id' => Auth::id(),
            'date_updated' => now(),
        ], \$id);
    }

PHP;
    }
    if (!empty($cfg['hasFeature'])) {
        $featureMethod = <<<PHP

    public function toggleFeature(\$id)
    {
        \$row = \$this->repo->find(\$id);
        return \$this->repo->update([
            'is_featured' => !\$row->is_featured,
            'updatedby_id' => Auth::id(),
            'date_updated' => now(),
        ], \$id);
    }

PHP;
    }
    if (!empty($cfg['hasEnable'])) {
        $enableMethod = <<<PHP

    public function toggleEnabled(\$id)
    {
        \$row = \$this->repo->find(\$id);
        return \$this->repo->update([
            'is_enabled' => !\$row->is_enabled,
            'updatedby_id' => Auth::id(),
            'date_updated' => now(),
        ], \$id);
    }

PHP;
    }
    $rawCols = !empty($cfg['hasStatus']) ? "['status','action']" : "['action']";
    $code = <<<PHP
<?php

namespace App\Services\Concrete\Admin\Intro;

use App\Enums\Status;
use App\Models\\{$model};
use App\Repository\Repository;
use App\Services\Concrete\Admin\Intro\Concerns\IntroAuditable;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;

class {$svc}
{
    use IntroAuditable;

    protected \$repo;

    public function __construct()
    {
        \$this->repo = new Repository(new {$model}());
    }

    public function getData(\$obj = [])
    {
        \$q = \$this->repo->getModel()::where('is_deleted', 0)->orderBy('{$order}');
        return DataTables::of(\$q)
{$statusCol}
            ->addColumn('action', function (\$item) {
                return "
                    <a class='btn btn-icon btn-outline-primary mr-2' id='editIntroItem' href='javascript:void(0)' data-id='{\$item->{$pk}}'><i class='fa fa-pencil'></i></a>
                    <a class='btn btn-icon btn-outline-danger' id='deleteIntroItem' data-id='{\$item->{$pk}}'><i class='fa fa-trash'></i></a>
                ";
            })
            ->rawColumns({$rawCols})
            ->make(true);
    }

    public function save(array \$obj)
    {
        if (!empty(\$obj['{$pk}'])) {
            \$obj = \$this->updateAudit(\$obj);
            \$this->repo->update(\$obj, \$obj['{$pk}']);
            return \$this->repo->find(\$obj['{$pk}']);
        }
        \$obj['{$pk}'] = generateUuid();
        \$obj = \$this->createAudit(\$obj);
        return \$this->repo->create(\$obj);
    }

    public function getById(\$id)
    {
        return \$this->repo->find(\$id);
    }

    public function delete(\$id)
    {
        return \$this->repo->update(\$this->deleteAudit(), \$id);
    }

    public function getAllActive()
    {
        return \$this->repo->getModel()::where('is_deleted', 0)
            ->where('status', Status::ACTIVE)
            ->orderBy('{$order}')
            ->get();
    }
{$statusMethod}{$featureMethod}{$enableMethod}}

PHP;
    write_file("{$base}/app/Services/Concrete/Admin/Intro/{$svc}.php", $code);
}

// BlogService (special)
write_file("{$base}/app/Services/Concrete/Admin/Intro/BlogService.php", <<<'PHP'
<?php

namespace App\Services\Concrete\Admin\Intro;

use App\Models\IntroBlog;
use App\Repository\Repository;
use App\Services\Concrete\Admin\Intro\Concerns\IntroAuditable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

class BlogService
{
    use IntroAuditable;

    protected $repo;

    public function __construct()
    {
        $this->repo = new Repository(new IntroBlog());
    }

    public function getData($obj = [])
    {
        $q = $this->repo->getModel()::with(['category', 'tags'])
            ->where('is_deleted', 0)
            ->orderByDesc('date_created');

        if (!empty($obj['status_filter'])) {
            $q->where('status', $obj['status_filter']);
        }
        if (!empty($obj['category_id'])) {
            $q->where('intro_blog_category_id', $obj['category_id']);
        }

        return DataTables::of($q)
            ->addColumn('category', fn ($item) => $item->category?->name ?? '-')
            ->addColumn('status_badge', function ($item) {
                $map = [
                    'draft' => 'secondary',
                    'published' => 'success',
                    'scheduled' => 'warning',
                ];
                $cls = $map[$item->status] ?? 'secondary';
                return '<span class="badge bg-label-' . $cls . '">' . e(ucfirst($item->status)) . '</span>';
            })
            ->addColumn('action', function ($item) {
                return "
                    <a class='btn btn-icon btn-outline-primary mr-2' id='editIntroItem' href='javascript:void(0)' data-id='{$item->intro_blog_id}'><i class='fa fa-pencil'></i></a>
                    <a class='btn btn-icon btn-outline-danger' id='deleteIntroItem' data-id='{$item->intro_blog_id}'><i class='fa fa-trash'></i></a>
                ";
            })
            ->rawColumns(['status_badge', 'action'])
            ->make(true);
    }

    public function save(array $obj)
    {
        $tagIds = $obj['tag_ids'] ?? [];
        unset($obj['tag_ids']);

        if (empty($obj['reading_time']) && !empty($obj['content'])) {
            $text = is_string($obj['content']) ? $obj['content'] : json_encode($obj['content']);
            $words = str_word_count(strip_tags($text));
            $obj['reading_time'] = max(1, (int) ceil($words / 200));
        }

        return DB::transaction(function () use ($obj, $tagIds) {
            if (!empty($obj['intro_blog_id'])) {
                $obj = $this->updateAudit($obj);
                $this->repo->update($obj, $obj['intro_blog_id']);
                $blog = $this->repo->find($obj['intro_blog_id']);
            } else {
                $obj['intro_blog_id'] = generateUuid();
                if (empty($obj['author_id'])) {
                    $obj['author_id'] = Auth::id();
                }
                $obj = $this->createAudit($obj);
                $blog = $this->repo->create($obj);
            }
            $blog->tags()->sync($tagIds ?: []);
            return $blog->load(['category', 'tags', 'author']);
        });
    }

    public function getById($id)
    {
        return $this->repo->find($id)->load(['category', 'tags', 'author']);
    }

    public function delete($id)
    {
        return $this->repo->update($this->deleteAudit(), $id);
    }
}

PHP);

write_file("{$base}/app/Services/Concrete/Admin/Intro/BlogCommentService.php", <<<'PHP'
<?php

namespace App\Services\Concrete\Admin\Intro;

use App\Models\IntroBlogComment;
use App\Models\IntroWebsiteSetting;
use App\Repository\Repository;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;

class BlogCommentService
{
    protected $repo;

    public function __construct()
    {
        $this->repo = new Repository(new IntroBlogComment());
    }

    public function getData($obj = [])
    {
        $q = $this->repo->getModel()::with('blog')
            ->where('is_deleted', 0)
            ->orderByDesc('date_created');

        if (!empty($obj['status_filter'])) {
            $q->where('status', $obj['status_filter']);
        }
        if (!empty($obj['blog_id'])) {
            $q->where('intro_blog_id', $obj['blog_id']);
        }

        return DataTables::of($q)
            ->addColumn('blog_title', fn ($item) => $item->blog?->title ?? '-')
            ->addColumn('status_badge', function ($item) {
                $map = [
                    'pending' => 'warning',
                    'approved' => 'success',
                    'rejected' => 'danger',
                    'spam' => 'dark',
                    'hidden' => 'secondary',
                ];
                $cls = $map[$item->status] ?? 'secondary';
                return '<span class="badge bg-label-' . $cls . '">' . e(ucfirst($item->status)) . '</span>';
            })
            ->addColumn('action', function ($item) {
                return "
                    <a class='btn btn-icon btn-outline-success mr-1' title='Approve' id='approveComment' data-id='{$item->intro_blog_comment_id}'><i class='fa fa-check'></i></a>
                    <a class='btn btn-icon btn-outline-warning mr-1' title='Reject' id='rejectComment' data-id='{$item->intro_blog_comment_id}'><i class='fa fa-ban'></i></a>
                    <a class='btn btn-icon btn-outline-secondary mr-1' title='Spam' id='spamComment' data-id='{$item->intro_blog_comment_id}'><i class='fa fa-flag'></i></a>
                    <a class='btn btn-icon btn-outline-danger' id='deleteIntroItem' data-id='{$item->intro_blog_comment_id}'><i class='fa fa-trash'></i></a>
                ";
            })
            ->rawColumns(['status_badge', 'action'])
            ->make(true);
    }

    public function create(array $obj)
    {
        $requireModeration = IntroWebsiteSetting::where('key', 'comments_require_moderation')->value('value');
        $status = ($requireModeration === '0' || $requireModeration === 'false') ? 'approved' : 'pending';

        $obj['intro_blog_comment_id'] = generateUuid();
        $obj['status'] = $obj['status'] ?? $status;
        $obj['date_created'] = now();
        return $this->repo->create($obj);
    }

    public function moderate($id, string $status, ?string $note = null)
    {
        return $this->repo->update([
            'status' => $status,
            'moderation_note' => $note,
            'moderatedby_id' => Auth::id(),
            'moderated_at' => now(),
        ], $id);
    }

    public function getById($id)
    {
        return $this->repo->find($id)->load('blog');
    }

    public function delete($id)
    {
        return $this->repo->update([
            'is_deleted' => 1,
            'deletedby_id' => Auth::id(),
            'date_deleted' => now(),
        ], $id);
    }

    public function approvedForBlog($blogId)
    {
        return $this->repo->getModel()::where('intro_blog_id', $blogId)
            ->where('status', 'approved')
            ->where('is_deleted', 0)
            ->orderBy('date_created')
            ->get();
    }
}

PHP);

write_file("{$base}/app/Services/Concrete/Admin/Intro/ContactInquiryService.php", <<<'PHP'
<?php

namespace App\Services\Concrete\Admin\Intro;

use App\Models\IntroContactInquiry;
use App\Models\IntroContactReply;
use App\Repository\Repository;
use App\Services\Concrete\Email\DTO\EmailData;
use App\Services\Concrete\Email\EmailService;
use Exception;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;

class ContactInquiryService
{
    protected $repo;
    protected $email_service;

    public function __construct(EmailService $email_service)
    {
        $this->repo = new Repository(new IntroContactInquiry());
        $this->email_service = $email_service;
    }

    public function getData($obj = [])
    {
        $q = $this->repo->getModel()::where('is_deleted', 0)->orderByDesc('date_created');
        if (!empty($obj['status_filter'])) {
            $q->where('status', $obj['status_filter']);
        }

        return DataTables::of($q)
            ->addColumn('status_badge', function ($item) {
                $map = [
                    'new' => 'danger',
                    'read' => 'warning',
                    'replied' => 'success',
                    'closed' => 'secondary',
                ];
                $cls = $map[$item->status] ?? 'secondary';
                return '<span class="badge bg-label-' . $cls . '">' . e(ucfirst($item->status)) . '</span>';
            })
            ->addColumn('date_created', fn ($item) => localDateTime($item->date_created))
            ->addColumn('action', function ($item) {
                return "
                    <a class='btn btn-icon btn-outline-primary mr-2' id='viewIntroInquiry' data-id='{$item->intro_contact_inquiry_id}'><i class='fa fa-eye'></i></a>
                    <a class='btn btn-icon btn-outline-danger' id='deleteIntroItem' data-id='{$item->intro_contact_inquiry_id}'><i class='fa fa-trash'></i></a>
                ";
            })
            ->rawColumns(['status_badge', 'action'])
            ->make(true);
    }

    public function create(array $obj)
    {
        $obj['intro_contact_inquiry_id'] = generateUuid();
        $obj['status'] = 'new';
        $obj['date_created'] = now();
        return $this->repo->create($obj);
    }

    public function getById($id)
    {
        $inquiry = $this->repo->find($id)->load('replies');
        if ($inquiry->status === 'new') {
            $inquiry->update(['status' => 'read']);
        }
        return $inquiry->fresh('replies');
    }

    public function updateStatus($id, string $status)
    {
        return $this->repo->update(['status' => $status], $id);
    }

    public function reply($id, string $message)
    {
        $inquiry = $this->repo->find($id);

        $result = $this->email_service->sendPlatform(new EmailData([
            'to' => $inquiry->email,
            'subject' => 'Re: ' . ($inquiry->subject ?: 'Your message to Dukanaz'),
            'body' => nl2br(e($message)),
        ]));

        $reply = IntroContactReply::create([
            'intro_contact_reply_id' => generateUuid(),
            'intro_contact_inquiry_id' => $inquiry->intro_contact_inquiry_id,
            'reply_message' => $message,
            'send_status' => $result['status'] ? 'sent' : 'failed',
            'error_message' => $result['status'] ? null : ($result['message'] ?? 'Send failed'),
            'repliedby_id' => Auth::id(),
            'date_created' => now(),
        ]);

        if (!$result['status']) {
            throw new Exception($result['message'] ?? 'Failed to send email.');
        }

        $inquiry->update(['status' => 'replied']);
        return $reply;
    }

    public function delete($id)
    {
        return $this->repo->update([
            'is_deleted' => 1,
            'deletedby_id' => Auth::id(),
            'date_deleted' => now(),
        ], $id);
    }
}

PHP);

write_file("{$base}/app/Services/Concrete/Admin/Intro/WebsiteSettingService.php", <<<'PHP'
<?php

namespace App\Services\Concrete\Admin\Intro;

use App\Models\IntroWebsiteSetting;
use Illuminate\Support\Facades\Auth;

class WebsiteSettingService
{
    public function allGrouped()
    {
        return IntroWebsiteSetting::orderBy('group')->orderBy('key')->get()->groupBy('group');
    }

    public function asMap(): array
    {
        return IntroWebsiteSetting::pluck('value', 'key')->toArray();
    }

    public function get(string $key, $default = null)
    {
        $row = IntroWebsiteSetting::where('key', $key)->first();
        return $row ? $row->value : $default;
    }

    public function upsertMany(array $pairs, string $group = 'general')
    {
        foreach ($pairs as $key => $value) {
            $type = 'text';
            if (is_bool($value) || $value === '0' || $value === '1' || $value === 'true' || $value === 'false') {
                $type = 'boolean';
            } elseif (is_array($value)) {
                $type = 'json';
                $value = json_encode($value);
            }

            IntroWebsiteSetting::updateOrCreate(
                ['key' => $key],
                [
                    'intro_website_setting_id' => IntroWebsiteSetting::where('key', $key)->value('intro_website_setting_id') ?: generateUuid(),
                    'group' => $group,
                    'value' => is_bool($value) ? ($value ? '1' : '0') : $value,
                    'type' => $type,
                    'label' => ucwords(str_replace('_', ' ', $key)),
                    'updatedby_id' => Auth::id(),
                    'date_updated' => now(),
                ]
            );
        }
        return $this->asMap();
    }

    public function publicMap(): array
    {
        $hidden = ['smtp_password', 'api_secret'];
        return IntroWebsiteSetting::whereNotIn('key', $hidden)->pluck('value', 'key')->toArray();
    }
}

PHP);

write_file("{$base}/app/Services/Concrete/Admin/Intro/MediaService.php", <<<'PHP'
<?php

namespace App\Services\Concrete\Admin\Intro;

use App\Models\IntroMedia;
use App\Repository\Repository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Yajra\DataTables\DataTables;

class MediaService
{
    protected $repo;

    public function __construct()
    {
        $this->repo = new Repository(new IntroMedia());
    }

    public function getData($obj = [])
    {
        $q = $this->repo->getModel()::where('is_deleted', 0)->orderByDesc('date_created');
        if (!empty($obj['collection'])) {
            $q->where('collection', $obj['collection']);
        }

        return DataTables::of($q)
            ->addColumn('preview', function ($item) {
                return $item->url
                    ? '<img src="' . e($item->url) . '" style="width:40px;height:40px;object-fit:cover;border-radius:4px;">'
                    : '-';
            })
            ->addColumn('action', function ($item) {
                return "<a class='btn btn-icon btn-outline-danger' id='deleteIntroItem' data-id='{$item->intro_media_id}'><i class='fa fa-trash'></i></a>";
            })
            ->rawColumns(['preview', 'action'])
            ->make(true);
    }

    public function upload(UploadedFile $file, string $collection = 'general', ?string $alt = null)
    {
        $path = public_path('uploads/intro/media');
        if (!File::exists($path)) {
            File::makeDirectory($path, 0755, true);
        }
        $filename = time() . '_' . preg_replace('/\s+/', '_', $file->getClientOriginalName());
        $file->move($path, $filename);

        return $this->repo->create([
            'intro_media_id' => generateUuid(),
            'filename' => $filename,
            'original_name' => $file->getClientOriginalName(),
            'disk_path' => 'uploads/intro/media/' . $filename,
            'mime_type' => $file->getClientMimeType() ?: $file->getMimeType(),
            'collection' => $collection,
            'alt_text' => $alt,
            'size' => @filesize($path . DIRECTORY_SEPARATOR . $filename) ?: null,
            'createdby_id' => Auth::id(),
            'date_created' => now(),
        ]);
    }

    public function getById($id)
    {
        return $this->repo->find($id);
    }

    public function delete($id)
    {
        return $this->repo->update([
            'is_deleted' => 1,
            'deletedby_id' => Auth::id(),
            'date_deleted' => now(),
        ], $id);
    }
}

PHP);

write_file("{$base}/app/Services/Concrete/Admin/Intro/BusinessRegistrationService.php", <<<'PHP'
<?php

namespace App\Services\Concrete\Admin\Intro;

use App\Models\Business;
use App\Models\IntroBusinessRegistration;
use App\Models\Package;
use App\Repository\Repository;
use App\Services\Concrete\Admin\AccountingSettingCloneService;
use App\Services\Concrete\Admin\ChartOfAccountsCloneService;
use App\Services\Concrete\Admin\SubscriptionService;
use App\Services\Concrete\Admin\WebsiteCmsDefaultsService;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

class BusinessRegistrationService
{
    protected $repo;
    protected $subscription_service;
    protected $chart_of_accounts_clone_service;
    protected $accounting_setting_clone_service;
    protected $website_cms_defaults_service;

    public function __construct(
        SubscriptionService $subscription_service,
        ChartOfAccountsCloneService $chart_of_accounts_clone_service,
        AccountingSettingCloneService $accounting_setting_clone_service,
        WebsiteCmsDefaultsService $website_cms_defaults_service
    ) {
        $this->repo = new Repository(new IntroBusinessRegistration());
        $this->subscription_service = $subscription_service;
        $this->chart_of_accounts_clone_service = $chart_of_accounts_clone_service;
        $this->accounting_setting_clone_service = $accounting_setting_clone_service;
        $this->website_cms_defaults_service = $website_cms_defaults_service;
    }

    public function getData($obj = [])
    {
        $q = $this->repo->getModel()::with(['business.currentSubscription', 'package'])
            ->where('is_deleted', 0)
            ->orderByDesc('date_created');

        if (!empty($obj['status_filter'])) {
            $q->where('status', $obj['status_filter']);
        }
        if (!empty($obj['search'])) {
            $s = $obj['search'];
            $q->where(function ($w) use ($s) {
                $w->where('business_name', 'like', "%{$s}%")
                    ->orWhere('owner_email', 'like', "%{$s}%")
                    ->orWhere('owner_name', 'like', "%{$s}%");
            });
        }

        return DataTables::of($q)
            ->addColumn('package_name', fn ($item) => $item->package?->name ?? '-')
            ->addColumn('subscription_status', fn ($item) => $item->business?->currentSubscription?->status ?? '-')
            ->addColumn('status_badge', function ($item) {
                return '<span class="badge bg-label-info">' . e(ucfirst($item->status)) . '</span>';
            })
            ->addColumn('action', function ($item) {
                return "<a class='btn btn-icon btn-outline-primary' id='viewIntroRegistration' data-id='{$item->intro_business_registration_id}'><i class='fa fa-eye'></i></a>";
            })
            ->rawColumns(['status_badge', 'action'])
            ->make(true);
    }

    public function getById($id)
    {
        return $this->repo->find($id)->load(['business.currentSubscription', 'business.package', 'package']);
    }

    public function updateStatus($id, string $status)
    {
        return $this->repo->update([
            'status' => $status,
            'updatedby_id' => Auth::id(),
            'date_updated' => now(),
        ], $id);
    }

    /**
     * Public Intro registration — reuses existing Package + SubscriptionService.
     * Does not modify package schema. Creates business with payment_pending.
     */
    public function registerFromIntro(array $data): IntroBusinessRegistration
    {
        $package = Package::where('package_id', $data['package_id'])
            ->where('is_deleted', 0)
            ->where('status', 1)
            ->first();

        if (!$package) {
            throw new Exception('Selected package is not available.');
        }

        return DB::transaction(function () use ($data, $package) {
            $business = Business::create([
                'business_id' => generateUuid(),
                'owner_name' => $data['owner_name'],
                'owner_email' => $data['owner_email'],
                'owner_phone' => $data['owner_phone'] ?? null,
                'name' => $data['business_name'],
                'email' => $data['business_email'] ?? $data['owner_email'],
                'phone' => $data['business_phone'] ?? ($data['owner_phone'] ?? null),
                'address' => $data['address'] ?? null,
                'city' => $data['city'] ?? null,
                'description' => $data['business_type'] ?? null,
                'status' => 'active',
                'is_deleted' => 0,
                'createdby_id' => Auth::id(),
                'date_created' => now(),
            ]);

            $billingCycle = $data['billing_cycle'] ?? $package->duration_type;

            $this->subscription_service->createInitial($business, $package, [
                'billing_cycle' => $billingCycle,
                'mark_paid' => false,
                'payment_method' => 'bank_transfer',
                'payment_reference' => 'intro registration',
            ]);

            $accountMap = $this->chart_of_accounts_clone_service->cloneTemplateToBusiness($business->business_id);
            $this->accounting_setting_clone_service->cloneTemplateToBusiness($business->business_id, $accountMap);
            $this->website_cms_defaults_service->seed($business->business_id);

            return $this->repo->create([
                'intro_business_registration_id' => generateUuid(),
                'business_id' => $business->business_id,
                'package_id' => $package->package_id,
                'billing_cycle' => $billingCycle,
                'business_name' => $data['business_name'],
                'owner_name' => $data['owner_name'],
                'owner_email' => $data['owner_email'],
                'owner_phone' => $data['owner_phone'] ?? null,
                'business_email' => $data['business_email'] ?? null,
                'business_phone' => $data['business_phone'] ?? null,
                'business_type' => $data['business_type'] ?? null,
                'city' => $data['city'] ?? null,
                'address' => $data['address'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => 'pending',
                'meta' => $data['meta'] ?? null,
                'date_created' => now(),
            ]);
        });
    }
}

PHP);

echo "Admin services done\n";
