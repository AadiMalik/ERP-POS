<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\WebsiteTestimonialService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class WebsiteTestimonialController extends Controller
{
    use ResponseAPI;

    protected $business_service;
    protected $testimonial_service;

    public function __construct(BusinessService $business_service, WebsiteTestimonialService $testimonial_service)
    {
        $this->middleware('permission:website-testimonial.view')->only(['index', 'getData']);
        $this->middleware('permission:website-testimonial.create|website-testimonial.edit')->only(['store']);
        $this->middleware('permission:website-testimonial.edit')->only(['edit']);
        $this->middleware('permission:website-testimonial.delete')->only(['destroy']);
        $this->middleware('permission:website-testimonial.status')->only(['status']);

        $this->business_service = $business_service;
        $this->testimonial_service = $testimonial_service;
    }

    public function index()
    {
        $business = $this->business_service->getAll();
        return view('admin.website_testimonial.index', compact('business'));
    }

    public function getData(Request $request)
    {
        return $this->testimonial_service->getData($request->all());
    }

    public function store(Request $request)
    {
        $rules = [
            'author_name' => 'required|string|max:100',
            'author_title' => 'nullable|string|max:100',
            'avatar' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'quote' => 'required|string|max:1000',
            'rating' => 'nullable|integer|min:1|max:5',
            'sort_order' => 'nullable|integer',
        ];

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        $obj = $request->only(['testimonial_id', 'author_name', 'author_title', 'quote', 'rating', 'sort_order']);

        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/website/testimonial'), $fileName);
            $obj['avatar'] = $fileName;
        }

        $obj['business_id'] = $request->business_id ?? Auth::user()->business_id;
        $obj['status'] = $request->status ?? 'active';

        $testimonial = $this->testimonial_service->save($obj);

        return $this->success(empty($request->testimonial_id) ? Message::SAVE : Message::UPDATE, $testimonial);
    }

    public function edit($id)
    {
        try {
            return $this->success(Message::FETCH, $this->testimonial_service->getById($id));
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function status($id)
    {
        try {
            $this->testimonial_service->status($id);
            return $this->success(Message::STATUS, []);
        } catch (Exception $e) {
            return $this->error(Message::ERROR);
        }
    }

    public function destroy($id)
    {
        try {
            $this->testimonial_service->delete($id);
            return $this->success(Message::DELETE, []);
        } catch (Exception $e) {
            return $this->error(Message::ERROR);
        }
    }
}
