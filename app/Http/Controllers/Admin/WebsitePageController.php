<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\WebsitePageService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WebsitePageController extends Controller
{
    use ResponseAPI;

    protected $business_service;
    protected $page_service;

    public function __construct(BusinessService $business_service, WebsitePageService $page_service)
    {
        $this->middleware('permission:website-page.view')->only(['index', 'getData']);
        $this->middleware('permission:website-page.edit')->only(['store', 'edit']);
        $this->middleware('permission:website-page.status')->only(['status']);

        $this->business_service = $business_service;
        $this->page_service = $page_service;
    }

    public function index()
    {
        $business = $this->business_service->getAll();
        return view('admin.website_page.index', compact('business'));
    }

    public function getData(Request $request)
    {
        return $this->page_service->getData($request->all());
    }

    public function store(Request $request)
    {
        $rules = [
            'page_id' => 'required|string|exists:website_pages,page_id',
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string|max:1000',
            'seo_keywords' => 'nullable|string|max:1000',
            'og_image' => 'nullable|image|max:2048',
        ];

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        $obj = $request->only(['page_id', 'title', 'content', 'seo_title', 'seo_description', 'seo_keywords']);

        if ($request->hasFile('og_image')) {
            $file = $request->file('og_image');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/website/page'), $fileName);
            $obj['og_image'] = $fileName;
        }

        $page = $this->page_service->save($obj);

        return $this->success(Message::UPDATE, $page);
    }

    public function edit($page_id)
    {
        try {
            return $this->success(Message::FETCH, $this->page_service->getById($page_id));
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function status($page_id)
    {
        try {
            $this->page_service->status($page_id);
            return $this->success(Message::STATUS, []);
        } catch (Exception $e) {
            return $this->error(Message::ERROR);
        }
    }
}
