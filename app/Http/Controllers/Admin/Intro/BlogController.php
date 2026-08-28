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
        $categories = app(\App\Services\Concrete\Admin\Intro\BlogCategoryService::class)->getAllActive();
        $tags = app(\App\Services\Concrete\Admin\Intro\BlogTagService::class)->getAllActive();
        return view('admin.intro.blogs.index', compact('categories', 'tags'));
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
