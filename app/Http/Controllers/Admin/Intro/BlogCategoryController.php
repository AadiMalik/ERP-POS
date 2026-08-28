<?php

namespace App\Http\Controllers\Admin\Intro;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\Intro\BlogCategoryService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class BlogCategoryController extends Controller
{
    use ResponseAPI;

    protected $service;

    public function __construct(BlogCategoryService $service)
    {
        $this->middleware('superadmin');
        $this->middleware('permission:intro-blog-category.view')->only(['index', 'getData', 'show']);
        $this->middleware('permission:intro-blog-category.create|intro-blog-category.edit')->only(['store']);
        $this->middleware('permission:intro-blog-category.delete')->only(['destroy']);
        $this->middleware('permission:intro-blog-category.status')->only(['status']);
        $this->service = $service;
    }

    public function index()
    {
        return view('admin.intro.blog_categories.index');
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
        $rules = array (
  'name' => 'required|string|max:150',
  'slug' => 'nullable|string|max:160',
  'description' => 'nullable|string',
  'display_order' => 'nullable|integer',
  'status' => 'nullable|string',
  'seo_title' => 'nullable|string|max:200',
  'meta_description' => 'nullable|string',
);
        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        $obj = $request->only(array (
  0 => 'name',
  1 => 'slug',
  2 => 'description',
  3 => 'display_order',
  4 => 'status',
  5 => 'seo_title',
  6 => 'meta_description',
));
        if (empty($obj['slug']) && !empty($obj['name'] ?? $obj['title'] ?? null)) {
            $obj['slug'] = Str::slug($obj['name'] ?? $obj['title']);
        }
        if ($request->filled('intro_blog_category_id')) {
            $obj['intro_blog_category_id'] = $request->input('intro_blog_category_id');
        }
        if ($request->has('content_json')) {
            $obj['content_json'] = is_string($request->content_json)
                ? json_decode($request->content_json, true)
                : $request->content_json;
        }

        $uploadDir = NULL;
        $uploadFields = array (
);
        if ($uploadDir) {
            foreach ($uploadFields as $field) {
                if ($request->hasFile($field)) {
                    $file = $request->file($field);
                    $fileName = time() . '_' . $file->getClientOriginalName();
                    $path = public_path('uploads/' . $uploadDir);
                    if (!File::exists($path)) {
                        File::makeDirectory($path, 0755, true);
                    }
                    $file->move($path, $fileName);
                    $obj[$field] = $fileName;
                }
            }
        }

        try {
            $row = $this->service->save($obj);
            return $this->success(empty($request->intro_blog_category_id) ? Message::SAVE : Message::UPDATE, $row);
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

    public function status($id)
    {
        try {
            $this->service->status($id);
            return $this->success(Message::STATUS, []);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
