<?php

namespace App\Http\Controllers\Admin\Intro;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\Intro\PageService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PageController extends Controller
{
    use ResponseAPI;

    protected $service;

    public function __construct(PageService $service)
    {
        $this->middleware('superadmin');
        $this->middleware('permission:intro-page.view')->only(['index', 'getData', 'show']);
        $this->middleware('permission:intro-page.create|intro-page.edit')->only(['store']);
        $this->middleware('permission:intro-page.delete')->only(['destroy']);
        $this->service = $service;
    }

    public function index()
    {
        return view('admin.intro.pages.index');
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
);
        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        $obj = $request->only(array (
  0 => 'title',
  1 => 'slug',
  2 => 'content',
  3 => 'status',
  4 => 'seo_title',
  5 => 'meta_description',
  6 => 'meta_keywords',
  7 => 'canonical_url',
  8 => 'og_title',
  9 => 'og_description',
  10 => 'robots_index',
  11 => 'robots_follow',
));
        if (empty($obj['slug']) && !empty($obj['name'] ?? $obj['title'] ?? null)) {
            $obj['slug'] = Str::slug($obj['name'] ?? $obj['title']);
        }
        if ($request->filled('intro_page_id')) {
            $obj['intro_page_id'] = $request->input('intro_page_id');
        }
        if ($request->has('content_json')) {
            $obj['content_json'] = is_string($request->content_json)
                ? json_decode($request->content_json, true)
                : $request->content_json;
        }

        $uploadDir = 'intro/pages';
        $uploadFields = array (
  0 => 'og_image',
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
            return $this->success(empty($request->intro_page_id) ? Message::SAVE : Message::UPDATE, $row);
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
