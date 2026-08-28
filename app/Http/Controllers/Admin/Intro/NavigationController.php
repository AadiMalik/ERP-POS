<?php

namespace App\Http\Controllers\Admin\Intro;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\Intro\NavigationService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class NavigationController extends Controller
{
    use ResponseAPI;

    protected $service;

    public function __construct(NavigationService $service)
    {
        $this->middleware('superadmin');
        $this->middleware('permission:intro-navigation.view')->only(['index', 'getData', 'show']);
        $this->middleware('permission:intro-navigation.create|intro-navigation.edit')->only(['store']);
        $this->middleware('permission:intro-navigation.delete')->only(['destroy']);
        $this->middleware('permission:intro-navigation.status')->only(['status']);
        $this->service = $service;
    }

    public function index()
    {
        return view('admin.intro.navigation.index');
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
  'label' => 'required|string|max:150',
  'url' => 'nullable|string|max:255',
  'section_key' => 'nullable|string|max:100',
  'match_key' => 'nullable|string|max:100',
  'location' => 'nullable|string|max:50',
  'parent_id' => 'nullable|string',
  'display_order' => 'nullable|integer',
  'status' => 'nullable|string',
);
        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        $obj = $request->only(array (
  0 => 'label',
  1 => 'url',
  2 => 'section_key',
  3 => 'match_key',
  4 => 'location',
  5 => 'parent_id',
  6 => 'display_order',
  7 => 'status',
));
        if (empty($obj['slug']) && !empty($obj['name'] ?? $obj['title'] ?? null)) {
            $obj['slug'] = Str::slug($obj['name'] ?? $obj['title']);
        }
        if ($request->filled('intro_navigation_item_id')) {
            $obj['intro_navigation_item_id'] = $request->input('intro_navigation_item_id');
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
            return $this->success(empty($request->intro_navigation_item_id) ? Message::SAVE : Message::UPDATE, $row);
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
