<?php

namespace App\Http\Controllers\Admin\Intro;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\Intro\ModuleService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ModuleController extends Controller
{
    use ResponseAPI;

    protected $service;

    public function __construct(ModuleService $service)
    {
        $this->middleware('superadmin');
        $this->middleware('permission:intro-module.view')->only(['index', 'getData', 'show']);
        $this->middleware('permission:intro-module.create|intro-module.edit')->only(['store']);
        $this->middleware('permission:intro-module.delete')->only(['destroy']);
        $this->middleware('permission:intro-module.status')->only(['status']);
        $this->middleware('permission:intro-module.edit')->only(['toggleFeature']);
        $this->service = $service;
    }

    public function index()
    {
        return view('admin.intro.modules.index');
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
  'category' => 'nullable|string|max:100',
  'display_order' => 'nullable|integer',
  'is_featured' => 'nullable|boolean',
  'status' => 'nullable|string',
  'icon' => 'nullable|image|max:2048',
  'image' => 'nullable|image|max:4096',
);
        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        $obj = $request->only(array (
  0 => 'name',
  1 => 'slug',
  2 => 'description',
  3 => 'category',
  4 => 'display_order',
  5 => 'is_featured',
  6 => 'status',
));
        if (empty($obj['slug']) && !empty($obj['name'] ?? $obj['title'] ?? null)) {
            $obj['slug'] = Str::slug($obj['name'] ?? $obj['title']);
        }
        if ($request->filled('intro_module_id')) {
            $obj['intro_module_id'] = $request->input('intro_module_id');
        }
        if ($request->has('content_json')) {
            $obj['content_json'] = is_string($request->content_json)
                ? json_decode($request->content_json, true)
                : $request->content_json;
        }

        $uploadDir = 'intro/modules';
        $uploadFields = array (
  0 => 'icon',
  1 => 'image',
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
            return $this->success(empty($request->intro_module_id) ? Message::SAVE : Message::UPDATE, $row);
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

    public function toggleFeature($id)
    {
        try {
            $this->service->toggleFeature($id);
            return $this->success(Message::UPDATE, []);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
