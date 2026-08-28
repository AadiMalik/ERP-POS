<?php
$base = dirname(__DIR__);

function w(string $path, string $c): void {
    $d = dirname($path);
    if (!is_dir($d)) mkdir($d, 0777, true);
    file_put_contents($path, $c);
    echo "Wrote $path\n";
}

// Generic admin controller factory
$admins = [
    ['ModuleController', 'ModuleService', 'intro-module', 'intro_module_id', [
        'name' => 'required|string|max:150',
        'slug' => 'nullable|string|max:160',
        'description' => 'nullable|string',
        'category' => 'nullable|string|max:100',
        'display_order' => 'nullable|integer',
        'is_featured' => 'nullable|boolean',
        'status' => 'nullable|string',
        'icon' => 'nullable|image|max:2048',
        'image' => 'nullable|image|max:4096',
    ], ['name','slug','description','category','display_order','is_featured','status'], 'intro/modules', ['icon','image'], true, true],
    ['BlogCategoryController', 'BlogCategoryService', 'intro-blog-category', 'intro_blog_category_id', [
        'name' => 'required|string|max:150',
        'slug' => 'nullable|string|max:160',
        'description' => 'nullable|string',
        'display_order' => 'nullable|integer',
        'status' => 'nullable|string',
        'seo_title' => 'nullable|string|max:200',
        'meta_description' => 'nullable|string',
    ], ['name','slug','description','display_order','status','seo_title','meta_description'], null, [], true, false],
    ['BlogTagController', 'BlogTagService', 'intro-blog-tag', 'intro_blog_tag_id', [
        'name' => 'required|string|max:100',
        'slug' => 'nullable|string|max:120',
        'status' => 'nullable|string',
    ], ['name','slug','status'], null, [], true, false],
    ['TestimonialController', 'TestimonialService', 'intro-testimonial', 'intro_testimonial_id', [
        'business_name' => 'nullable|string|max:150',
        'customer_name' => 'required|string|max:150',
        'designation' => 'nullable|string|max:150',
        'business_type' => 'nullable|string|max:100',
        'review_text' => 'required|string',
        'rating' => 'nullable|integer|min:1|max:5',
        'display_order' => 'nullable|integer',
        'status' => 'nullable|string',
        'image' => 'nullable|image|max:2048',
    ], ['business_name','customer_name','designation','business_type','review_text','rating','display_order','status'], 'intro/testimonials', ['image'], true, false],
    ['NavigationController', 'NavigationService', 'intro-navigation', 'intro_navigation_item_id', [
        'label' => 'required|string|max:150',
        'url' => 'nullable|string|max:255',
        'section_key' => 'nullable|string|max:100',
        'match_key' => 'nullable|string|max:100',
        'location' => 'nullable|string|max:50',
        'parent_id' => 'nullable|string',
        'display_order' => 'nullable|integer',
        'status' => 'nullable|string',
    ], ['label','url','section_key','match_key','location','parent_id','display_order','status'], null, [], true, false],
    ['HomepageSectionController', 'HomepageSectionService', 'intro-homepage-section', 'intro_homepage_section_id', [
        'section_key' => 'required|string|max:100',
        'title' => 'nullable|string|max:200',
        'subtitle' => 'nullable|string',
        'content' => 'nullable|string',
        'button_text' => 'nullable|string|max:100',
        'button_link' => 'nullable|string|max:255',
        'display_order' => 'nullable|integer',
        'is_enabled' => 'nullable|boolean',
        'status' => 'nullable|string',
        'image' => 'nullable|image|max:4096',
    ], ['section_key','title','subtitle','content','button_text','button_link','display_order','is_enabled','status'], 'intro/sections', ['image'], true, false],
    ['PageController', 'PageService', 'intro-page', 'intro_page_id', [
        'title' => 'required|string|max:200',
        'slug' => 'nullable|string|max:200',
        'content' => 'nullable|string',
        'status' => 'nullable|string',
        'seo_title' => 'nullable|string|max:200',
        'meta_description' => 'nullable|string',
        'meta_keywords' => 'nullable|string',
        'canonical_url' => 'nullable|string|max:255',
        'og_title' => 'nullable|string|max:200',
        'og_description' => 'nullable|string',
        'robots_index' => 'nullable|boolean',
        'robots_follow' => 'nullable|boolean',
        'og_image' => 'nullable|image|max:4096',
    ], ['title','slug','content','status','seo_title','meta_description','meta_keywords','canonical_url','og_title','og_description','robots_index','robots_follow'], 'intro/pages', ['og_image'], false, false],
];

foreach ($admins as $a) {
    [$class, $svc, $perm, $pk, $rules, $only, $uploadDir, $uploadFields, $hasStatus, $hasFeature] = $a;
    $rulesExport = var_export($rules, true);
    $onlyExport = var_export($only, true);
    $uploadFieldsExport = var_export($uploadFields, true);
    $uploadDirExport = var_export($uploadDir, true);
    $statusMw = $hasStatus ? "\n        \$this->middleware('permission:{$perm}.status')->only(['status']);" : '';
    $featureMw = $hasFeature ? "\n        \$this->middleware('permission:{$perm}.edit')->only(['toggleFeature']);" : '';
    $statusFn = $hasStatus ? <<<PHP

    public function status(\$id)
    {
        try {
            \$this->service->status(\$id);
            return \$this->success(Message::STATUS, []);
        } catch (Exception \$e) {
            return \$this->error(\$e->getMessage());
        }
    }

PHP : '';
    $featureFn = $hasFeature ? <<<PHP

    public function toggleFeature(\$id)
    {
        try {
            \$this->service->toggleFeature(\$id);
            return \$this->success(Message::UPDATE, []);
        } catch (Exception \$e) {
            return \$this->error(\$e->getMessage());
        }
    }

PHP : '';
    $enableFn = ($class === 'HomepageSectionController') ? <<<PHP

    public function toggleEnabled(\$id)
    {
        try {
            \$this->service->toggleEnabled(\$id);
            return \$this->success(Message::UPDATE, []);
        } catch (Exception \$e) {
            return \$this->error(\$e->getMessage());
        }
    }

PHP : '';

    $code = <<<PHP
<?php

namespace App\Http\Controllers\Admin\Intro;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\Intro\\{$svc};
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class {$class} extends Controller
{
    use ResponseAPI;

    protected \$service;

    public function __construct({$svc} \$service)
    {
        \$this->middleware('superadmin');
        \$this->middleware('permission:{$perm}.view')->only(['index', 'getData', 'show']);
        \$this->middleware('permission:{$perm}.create|{$perm}.edit')->only(['store']);
        \$this->middleware('permission:{$perm}.delete')->only(['destroy']);{$statusMw}{$featureMw}
        \$this->service = \$service;
    }

    public function index()
    {
        return view('admin.intro.placeholder', ['title' => '{$class}', 'resource' => '{$perm}']);
    }

    public function getData(Request \$request)
    {
        return \$this->service->getData(\$request->all());
    }

    public function show(\$id)
    {
        try {
            return \$this->success(Message::FETCH, \$this->service->getById(\$id));
        } catch (Exception \$e) {
            return \$this->error(\$e->getMessage());
        }
    }

    public function store(Request \$request)
    {
        \$rules = {$rulesExport};
        \$validate = Validator::make(\$request->all(), \$rules);
        if (\$validate->fails()) {
            return \$this->validationResponse(\$validate->errors()->first());
        }

        \$obj = \$request->only({$onlyExport});
        if (empty(\$obj['slug']) && !empty(\$obj['name'] ?? \$obj['title'] ?? null)) {
            \$obj['slug'] = Str::slug(\$obj['name'] ?? \$obj['title']);
        }
        if (\$request->filled('{$pk}')) {
            \$obj['{$pk}'] = \$request->input('{$pk}');
        }
        if (\$request->has('content_json')) {
            \$obj['content_json'] = is_string(\$request->content_json)
                ? json_decode(\$request->content_json, true)
                : \$request->content_json;
        }

        \$uploadDir = {$uploadDirExport};
        \$uploadFields = {$uploadFieldsExport};
        if (\$uploadDir) {
            foreach (\$uploadFields as \$field) {
                if (\$request->hasFile(\$field)) {
                    \$file = \$request->file(\$field);
                    \$fileName = time() . '_' . \$file->getClientOriginalName();
                    \$path = public_path('uploads/' . \$uploadDir);
                    if (!File::exists(\$path)) {
                        File::makeDirectory(\$path, 0755, true);
                    }
                    \$file->move(\$path, \$fileName);
                    \$obj[\$field] = \$fileName;
                }
            }
        }

        try {
            \$row = \$this->service->save(\$obj);
            return \$this->success(empty(\$request->{$pk}) ? Message::SAVE : Message::UPDATE, \$row);
        } catch (Exception \$e) {
            return \$this->error(\$e->getMessage());
        }
    }

    public function destroy(\$id)
    {
        try {
            \$this->service->delete(\$id);
            return \$this->success(Message::DELETE, []);
        } catch (Exception \$e) {
            return \$this->error(\$e->getMessage());
        }
    }
{$statusFn}{$featureFn}{$enableFn}}

PHP;
    w("{$base}/app/Http/Controllers/Admin/Intro/{$class}.php", $code);
}

// BlogController special
w("{$base}/app/Http/Controllers/Admin/Intro/BlogController.php", <<<'PHP'
<?php

namespace App\Http\Controllers\Admin\Intro;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\Intro\BlogService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class BlogController extends Controller
{
    use ResponseAPI;

    protected $service;

    public function __construct(BlogService $service)
    {
        $this->middleware('superadmin');
        $this->middleware('permission:intro-blog.view')->only(['index', 'getData', 'show']);
        $this->middleware('permission:intro-blog.create|intro-blog.edit')->only(['store']);
        $this->middleware('permission:intro-blog.delete')->only(['destroy']);
        $this->service = $service;
    }

    public function index()
    {
        return view('admin.intro.placeholder', ['title' => 'Blog Posts', 'resource' => 'intro-blog']);
    }

    public function getData(Request $request)
    {
        return $this->service->getData($request->all());
    }

    public function show($id)
    {
        try {
            return $this->success(Message::FETCH, $this->service->getById($id));
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function store(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'content' => 'nullable',
            'excerpt' => 'nullable|string',
            'intro_blog_category_id' => 'nullable|string',
            'author_id' => 'nullable|integer',
            'reading_time' => 'nullable|integer',
            'published_at' => 'nullable|date',
            'status' => 'nullable|in:draft,published,scheduled',
            'is_featured' => 'nullable|boolean',
            'seo_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'meta_keywords' => 'nullable|string',
            'canonical_url' => 'nullable|string|max:255',
            'og_title' => 'nullable|string|max:255',
            'og_description' => 'nullable|string',
            'featured_image' => 'nullable|image|max:4096',
            'og_image' => 'nullable|image|max:4096',
            'tag_ids' => 'nullable|array',
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        $obj = $request->only([
            'intro_blog_id', 'intro_blog_category_id', 'author_id', 'title', 'slug', 'content', 'excerpt',
            'reading_time', 'published_at', 'status', 'is_featured', 'seo_title', 'meta_description',
            'meta_keywords', 'canonical_url', 'og_title', 'og_description', 'tag_ids',
        ]);
        if (empty($obj['slug'])) {
            $obj['slug'] = Str::slug($obj['title']);
        }
        if (is_array($obj['content'] ?? null)) {
            $obj['content'] = json_encode($obj['content']);
        }

        foreach (['featured_image', 'og_image'] as $field) {
            if ($request->hasFile($field)) {
                $file = $request->file($field);
                $fileName = time() . '_' . $file->getClientOriginalName();
                $path = public_path('uploads/intro/blog');
                if (!File::exists($path)) {
                    File::makeDirectory($path, 0755, true);
                }
                $file->move($path, $fileName);
                $obj[$field] = $fileName;
            }
        }

        try {
            $row = $this->service->save($obj);
            return $this->success(empty($request->intro_blog_id) ? Message::SAVE : Message::UPDATE, $row);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $this->service->delete($id);
            return $this->success(Message::DELETE, []);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}

PHP);

w("{$base}/app/Http/Controllers/Admin/Intro/BlogCommentController.php", <<<'PHP'
<?php

namespace App\Http\Controllers\Admin\Intro;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\Intro\BlogCommentService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BlogCommentController extends Controller
{
    use ResponseAPI;

    protected $service;

    public function __construct(BlogCommentService $service)
    {
        $this->middleware('superadmin');
        $this->middleware('permission:intro-blog-comment.view')->only(['index', 'getData', 'show']);
        $this->middleware('permission:intro-blog-comment.moderate')->only(['moderate']);
        $this->middleware('permission:intro-blog-comment.delete')->only(['destroy']);
        $this->service = $service;
    }

    public function index()
    {
        return view('admin.intro.placeholder', ['title' => 'Blog Comments', 'resource' => 'intro-blog-comment']);
    }

    public function getData(Request $request)
    {
        return $this->service->getData($request->all());
    }

    public function show($id)
    {
        try {
            return $this->success(Message::FETCH, $this->service->getById($id));
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function moderate(Request $request, $id)
    {
        $validate = Validator::make($request->all(), [
            'status' => 'required|in:approved,rejected,spam,hidden,pending',
            'moderation_note' => 'nullable|string',
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }
        try {
            $this->service->moderate($id, $request->status, $request->moderation_note);
            return $this->success(Message::UPDATE, []);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $this->service->delete($id);
            return $this->success(Message::DELETE, []);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}

PHP);

w("{$base}/app/Http/Controllers/Admin/Intro/ContactInquiryController.php", <<<'PHP'
<?php

namespace App\Http\Controllers\Admin\Intro;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\Intro\ContactInquiryService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ContactInquiryController extends Controller
{
    use ResponseAPI;

    protected $service;

    public function __construct(ContactInquiryService $service)
    {
        $this->middleware('superadmin');
        $this->middleware('permission:intro-contact.view')->only(['index', 'getData', 'show']);
        $this->middleware('permission:intro-contact.reply')->only(['reply']);
        $this->middleware('permission:intro-contact.edit')->only(['updateStatus']);
        $this->middleware('permission:intro-contact.delete')->only(['destroy']);
        $this->service = $service;
    }

    public function index()
    {
        return view('admin.intro.placeholder', ['title' => 'Contact Inquiries', 'resource' => 'intro-contact']);
    }

    public function getData(Request $request)
    {
        return $this->service->getData($request->all());
    }

    public function show($id)
    {
        try {
            return $this->success(Message::FETCH, $this->service->getById($id));
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function reply(Request $request, $id)
    {
        $validate = Validator::make($request->all(), [
            'reply_message' => 'required|string',
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }
        try {
            $reply = $this->service->reply($id, $request->reply_message);
            return $this->success('Reply sent successfully.', $reply);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function updateStatus(Request $request, $id)
    {
        $validate = Validator::make($request->all(), [
            'status' => 'required|in:new,read,replied,closed',
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }
        try {
            $this->service->updateStatus($id, $request->status);
            return $this->success(Message::STATUS, []);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $this->service->delete($id);
            return $this->success(Message::DELETE, []);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}

PHP);

w("{$base}/app/Http/Controllers/Admin/Intro/WebsiteSettingController.php", <<<'PHP'
<?php

namespace App\Http\Controllers\Admin\Intro;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\Intro\WebsiteSettingService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class WebsiteSettingController extends Controller
{
    use ResponseAPI;

    protected $service;

    public function __construct(WebsiteSettingService $service)
    {
        $this->middleware('superadmin');
        $this->middleware('permission:intro-website-setting.view')->only(['index', 'show']);
        $this->middleware('permission:intro-website-setting.edit')->only(['update']);
        $this->service = $service;
    }

    public function index()
    {
        return view('admin.intro.placeholder', ['title' => 'Website Settings', 'resource' => 'intro-website-setting']);
    }

    public function show()
    {
        return $this->success(Message::FETCH, [
            'grouped' => $this->service->allGrouped(),
            'map' => $this->service->asMap(),
        ]);
    }

    public function update(Request $request)
    {
        try {
            $settings = $request->input('settings', $request->except(['_token']));
            if ($request->hasFile('logo')) {
                $settings['logo'] = $this->storeSettingImage($request->file('logo'), 'logo');
            }
            if ($request->hasFile('logo_light')) {
                $settings['logo_light'] = $this->storeSettingImage($request->file('logo_light'), 'logo_light');
            }
            if ($request->hasFile('favicon')) {
                $settings['favicon'] = $this->storeSettingImage($request->file('favicon'), 'favicon');
            }
            if ($request->hasFile('og_image')) {
                $settings['og_image'] = $this->storeSettingImage($request->file('og_image'), 'og_image');
            }
            $group = $request->input('group', 'general');
            $map = $this->service->upsertMany($settings, $group);
            return $this->success(Message::UPDATE, $map);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    protected function storeSettingImage($file, string $key): string
    {
        $path = public_path('uploads/intro/settings');
        if (!File::exists($path)) {
            File::makeDirectory($path, 0755, true);
        }
        $name = $key . '_' . time() . '_' . $file->getClientOriginalName();
        $file->move($path, $name);
        return $name;
    }
}

PHP);

w("{$base}/app/Http/Controllers/Admin/Intro/MediaController.php", <<<'PHP'
<?php

namespace App\Http\Controllers\Admin\Intro;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\Intro\MediaService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MediaController extends Controller
{
    use ResponseAPI;

    protected $service;

    public function __construct(MediaService $service)
    {
        $this->middleware('superadmin');
        $this->middleware('permission:intro-media.view')->only(['index', 'getData', 'show']);
        $this->middleware('permission:intro-media.create')->only(['store']);
        $this->middleware('permission:intro-media.delete')->only(['destroy']);
        $this->service = $service;
    }

    public function index()
    {
        return view('admin.intro.placeholder', ['title' => 'Media', 'resource' => 'intro-media']);
    }

    public function getData(Request $request)
    {
        return $this->service->getData($request->all());
    }

    public function show($id)
    {
        try {
            return $this->success(Message::FETCH, $this->service->getById($id));
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function store(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'file' => 'required|file|max:10240',
            'collection' => 'nullable|string|max:100',
            'alt_text' => 'nullable|string|max:255',
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }
        try {
            $row = $this->service->upload(
                $request->file('file'),
                $request->input('collection', 'general'),
                $request->input('alt_text')
            );
            return $this->success(Message::SAVE, $row);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $this->service->delete($id);
            return $this->success(Message::DELETE, []);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}

PHP);

w("{$base}/app/Http/Controllers/Admin/Intro/BusinessRegistrationController.php", <<<'PHP'
<?php

namespace App\Http\Controllers\Admin\Intro;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\Intro\BusinessRegistrationService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BusinessRegistrationController extends Controller
{
    use ResponseAPI;

    protected $service;

    public function __construct(BusinessRegistrationService $service)
    {
        $this->middleware('superadmin');
        $this->middleware('permission:intro-business.view')->only(['index', 'getData', 'show']);
        $this->middleware('permission:intro-business.edit')->only(['updateStatus']);
        $this->service = $service;
    }

    public function index()
    {
        return view('admin.intro.placeholder', ['title' => 'Intro Business Registrations', 'resource' => 'intro-business']);
    }

    public function getData(Request $request)
    {
        return $this->service->getData($request->all());
    }

    public function show($id)
    {
        try {
            return $this->success(Message::FETCH, $this->service->getById($id));
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function updateStatus(Request $request, $id)
    {
        $validate = Validator::make($request->all(), [
            'status' => 'required|string|max:50',
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }
        try {
            $this->service->updateStatus($id, $request->status);
            return $this->success(Message::STATUS, []);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}

PHP);

echo "Admin controllers done\n";
