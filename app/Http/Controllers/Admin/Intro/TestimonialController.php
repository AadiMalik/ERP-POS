<?php

namespace App\Http\Controllers\Admin\Intro;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\Intro\TestimonialService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class TestimonialController extends Controller
{
    use ResponseAPI;

    protected $service;

    public function __construct(TestimonialService $service)
    {
        $this->middleware('superadmin');
        $this->middleware('permission:intro-testimonial.view')->only(['index', 'getData', 'show']);
        $this->middleware('permission:intro-testimonial.create|intro-testimonial.edit')->only(['store']);
        $this->middleware('permission:intro-testimonial.delete')->only(['destroy']);
        $this->middleware('permission:intro-testimonial.status')->only(['status']);
        $this->service = $service;
    }

    public function index()
    {
        return view('admin.intro.testimonials.index');
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
  'business_name' => 'nullable|string|max:150',
  'customer_name' => 'required|string|max:150',
  'designation' => 'nullable|string|max:150',
  'business_type' => 'nullable|string|max:100',
  'review_text' => 'required|string',
  'rating' => 'nullable|integer|min:1|max:5',
  'display_order' => 'nullable|integer',
  'status' => 'nullable|string',
  'image' => 'nullable|image|max:2048',
);
        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        $obj = $request->only(array (
  0 => 'business_name',
  1 => 'customer_name',
  2 => 'designation',
  3 => 'business_type',
  4 => 'review_text',
  5 => 'rating',
  6 => 'display_order',
  7 => 'status',
));
        if (empty($obj['slug']) && !empty($obj['name'] ?? $obj['title'] ?? null)) {
            $obj['slug'] = Str::slug($obj['name'] ?? $obj['title']);
        }
        if ($request->filled('intro_testimonial_id')) {
            $obj['intro_testimonial_id'] = $request->input('intro_testimonial_id');
        }
        if ($request->has('content_json')) {
            $obj['content_json'] = is_string($request->content_json)
                ? json_decode($request->content_json, true)
                : $request->content_json;
        }

        $uploadDir = 'intro/testimonials';
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
            return $this->success(empty($request->intro_testimonial_id) ? Message::SAVE : Message::UPDATE, $row);
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
