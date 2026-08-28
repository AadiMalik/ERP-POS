<?php
$base = dirname(__DIR__);
$dir = "{$base}/app/Http/Controllers/Api/Intro";
if (!is_dir($dir)) mkdir($dir, 0777, true);

$controllers = [
'PackageController' => <<<'PHP'
<?php

namespace App\Http\Controllers\Api\Intro;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Api\Intro\IntroPublicService;
use App\Traits\ResponseAPI;
use Exception;

class PackageController extends Controller
{
    use ResponseAPI;

    protected $service;

    public function __construct(IntroPublicService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        try {
            return $this->success(Message::FETCH, $this->service->packages());
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function show($package_id)
    {
        try {
            return $this->success(Message::FETCH, $this->service->package($package_id));
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
PHP,
'BusinessController' => <<<'PHP'
<?php

namespace App\Http\Controllers\Api\Intro;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Api\Intro\IntroPublicService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BusinessController extends Controller
{
    use ResponseAPI;

    protected $service;

    public function __construct(IntroPublicService $service)
    {
        $this->service = $service;
    }

    public function register(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'package_id' => 'required|exists:packages,package_id',
            'billing_cycle' => 'nullable|in:monthly,yearly',
            'business_name' => 'required|string|max:150',
            'owner_name' => 'required|string|max:150',
            'owner_email' => 'required|email|max:150',
            'owner_phone' => 'nullable|string|max:50',
            'business_email' => 'nullable|email|max:150',
            'business_phone' => 'nullable|string|max:50',
            'business_type' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }
        try {
            $row = $this->service->registerBusiness($request->all());
            return $this->success(Message::SAVE, $row->load(['business', 'package']));
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
PHP,
'ModuleController' => <<<'PHP'
<?php

namespace App\Http\Controllers\Api\Intro;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Api\Intro\IntroPublicService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;

class ModuleController extends Controller
{
    use ResponseAPI;

    protected $service;

    public function __construct(IntroPublicService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        try {
            $featured = $request->has('featured') ? filter_var($request->featured, FILTER_VALIDATE_BOOLEAN) : null;
            return $this->success(Message::FETCH, $this->service->modules($request->category, $featured));
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function show($slug)
    {
        try {
            return $this->success(Message::FETCH, $this->service->moduleBySlug($slug));
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
PHP,
'BlogController' => <<<'PHP'
<?php

namespace App\Http\Controllers\Api\Intro;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Api\Intro\IntroPublicService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    use ResponseAPI;

    protected $service;

    public function __construct(IntroPublicService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        try {
            return $this->success(Message::FETCH, $this->service->blogs($request->only(['category', 'tag', 'featured', 'q'])));
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function show($slug)
    {
        try {
            return $this->success(Message::FETCH, $this->service->blogBySlug($slug));
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
PHP,
'BlogCategoryController' => <<<'PHP'
<?php

namespace App\Http\Controllers\Api\Intro;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Api\Intro\IntroPublicService;
use App\Traits\ResponseAPI;
use Exception;

class BlogCategoryController extends Controller
{
    use ResponseAPI;

    protected $service;

    public function __construct(IntroPublicService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        try {
            return $this->success(Message::FETCH, $this->service->blogCategories());
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
PHP,
'BlogTagController' => <<<'PHP'
<?php

namespace App\Http\Controllers\Api\Intro;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Api\Intro\IntroPublicService;
use App\Traits\ResponseAPI;
use Exception;

class BlogTagController extends Controller
{
    use ResponseAPI;

    protected $service;

    public function __construct(IntroPublicService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        try {
            return $this->success(Message::FETCH, $this->service->blogTags());
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
PHP,
'BlogCommentController' => <<<'PHP'
<?php

namespace App\Http\Controllers\Api\Intro;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Api\Intro\IntroPublicService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BlogCommentController extends Controller
{
    use ResponseAPI;

    protected $service;

    public function __construct(IntroPublicService $service)
    {
        $this->service = $service;
    }

    public function store(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'blog_slug' => 'required_without:intro_blog_id|string',
            'intro_blog_id' => 'required_without:blog_slug|string',
            'name' => 'required|string|max:150',
            'email' => 'required|email|max:150',
            'comment' => 'required|string|max:2000',
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }
        try {
            $data = $request->only(['blog_slug', 'intro_blog_id', 'name', 'email', 'comment']);
            $data['ip_address'] = $request->ip();
            $row = $this->service->submitComment($data);
            return $this->success(Message::SAVE, $row);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
PHP,
'TestimonialController' => <<<'PHP'
<?php

namespace App\Http\Controllers\Api\Intro;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Api\Intro\IntroPublicService;
use App\Traits\ResponseAPI;
use Exception;

class TestimonialController extends Controller
{
    use ResponseAPI;

    protected $service;

    public function __construct(IntroPublicService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        try {
            return $this->success(Message::FETCH, $this->service->testimonials());
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
PHP,
'ContactController' => <<<'PHP'
<?php

namespace App\Http\Controllers\Api\Intro;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Api\Intro\IntroPublicService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ContactController extends Controller
{
    use ResponseAPI;

    protected $service;

    public function __construct(IntroPublicService $service)
    {
        $this->service = $service;
    }

    public function store(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'name' => 'required|string|max:150',
            'email' => 'required|email|max:150',
            'phone' => 'nullable|string|max:50',
            'subject' => 'nullable|string|max:200',
            'message' => 'required|string',
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }
        try {
            $row = $this->service->submitContact($request->only(['name', 'email', 'phone', 'subject', 'message']));
            return $this->success(Message::SAVE, $row);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
PHP,
'WebsiteSettingController' => <<<'PHP'
<?php

namespace App\Http\Controllers\Api\Intro;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Api\Intro\IntroPublicService;
use App\Traits\ResponseAPI;
use Exception;

class WebsiteSettingController extends Controller
{
    use ResponseAPI;

    protected $service;

    public function __construct(IntroPublicService $service)
    {
        $this->service = $service;
    }

    public function show()
    {
        try {
            return $this->success(Message::FETCH, $this->service->websiteSettings());
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
PHP,
'NavigationController' => <<<'PHP'
<?php

namespace App\Http\Controllers\Api\Intro;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Api\Intro\IntroPublicService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;

class NavigationController extends Controller
{
    use ResponseAPI;

    protected $service;

    public function __construct(IntroPublicService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        try {
            return $this->success(Message::FETCH, $this->service->navigation($request->location));
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
PHP,
'PageController' => <<<'PHP'
<?php

namespace App\Http\Controllers\Api\Intro;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Api\Intro\IntroPublicService;
use App\Traits\ResponseAPI;
use Exception;

class PageController extends Controller
{
    use ResponseAPI;

    protected $service;

    public function __construct(IntroPublicService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        try {
            return $this->success(Message::FETCH, $this->service->pages());
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function show($slug)
    {
        try {
            return $this->success(Message::FETCH, $this->service->pageBySlug($slug));
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
PHP,
'HomepageController' => <<<'PHP'
<?php

namespace App\Http\Controllers\Api\Intro;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Api\Intro\IntroPublicService;
use App\Traits\ResponseAPI;
use Exception;

class HomepageController extends Controller
{
    use ResponseAPI;

    protected $service;

    public function __construct(IntroPublicService $service)
    {
        $this->service = $service;
    }

    public function show()
    {
        try {
            return $this->success(Message::FETCH, $this->service->homepage());
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
PHP,
];

foreach ($controllers as $name => $code) {
    file_put_contents("{$dir}/{$name}.php", $code);
    echo "Wrote {$name}\n";
}
echo "OK\n";
