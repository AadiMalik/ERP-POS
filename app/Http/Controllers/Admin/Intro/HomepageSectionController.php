<?php

namespace App\Http\Controllers\Admin\Intro;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\Intro\HomepageSectionService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class HomepageSectionController extends Controller
{
    use ResponseAPI;

    protected $service;

    public function __construct(HomepageSectionService $service)
    {
        $this->middleware('superadmin');
        $this->middleware('permission:intro-homepage-section.view')->only(['index', 'getData', 'show']);
        $this->middleware('permission:intro-homepage-section.create|intro-homepage-section.edit')->only(['store']);
        $this->middleware('permission:intro-homepage-section.delete')->only(['destroy']);
        $this->middleware('permission:intro-homepage-section.status')->only(['status']);
        $this->service = $service;
    }

    public function index()
    {
        return view('admin.intro.homepage_sections.index');
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
);
        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        $obj = $request->only(array (
  0 => 'section_key',
  1 => 'title',
  2 => 'subtitle',
  3 => 'content',
  4 => 'button_text',
  5 => 'button_link',
  6 => 'display_order',
  7 => 'is_enabled',
  8 => 'status',
));
        if (empty($obj['slug']) && !empty($obj['name'] ?? $obj['title'] ?? null)) {
            $obj['slug'] = Str::slug($obj['name'] ?? $obj['title']);
        }
        if ($request->filled('intro_homepage_section_id')) {
            $obj['intro_homepage_section_id'] = $request->input('intro_homepage_section_id');
        }
        if ($request->has('content_json')) {
            $obj['content_json'] = is_string($request->content_json)
                ? json_decode($request->content_json, true)
                : $request->content_json;
        }

        $uploadDir = 'intro/sections';
        $uploadFields = array (
  0 => 'image',
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
            return $this->success(empty($request->intro_homepage_section_id) ? Message::SAVE : Message::UPDATE, $row);
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

    public function toggleEnabled($id)
    {
        try {
            $this->service->toggleEnabled($id);
            return $this->success(Message::UPDATE, []);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
