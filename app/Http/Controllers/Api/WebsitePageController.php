<?php

namespace App\Http\Controllers\Api;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\WebsitePageService;
use App\Traits\ResponseAPI;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WebsitePageController extends Controller
{
    use ResponseAPI;

    protected $page_service;

    public function __construct(WebsitePageService $page_service)
    {
        $this->page_service = $page_service;
    }

    public function index(Request $request, $business_id)
    {
        $validate = Validator::make(
            ['business_id' => $business_id],
            ['business_id' => 'required|string|exists:businesses,business_id']
        );

        if ($validate->fails()) {
            return $this->error($validate->errors()->first(), 404);
        }

        return $this->success(Message::FETCH, $this->page_service->getAllPublic($business_id));
    }

    public function show(Request $request, $business_id, $slug)
    {
        $validate = Validator::make(
            ['business_id' => $business_id],
            ['business_id' => 'required|string|exists:businesses,business_id']
        );

        if ($validate->fails()) {
            return $this->error($validate->errors()->first(), 404);
        }

        $page = $this->page_service->getPublicBySlug($business_id, $slug);

        if (!$page) {
            return $this->error('Page not found', 404);
        }

        return $this->success(Message::FETCH, $page);
    }
}
