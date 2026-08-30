<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Api\Mobile\MobileCmsService;
use App\Traits\ResponseAPI;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WebsitePageController extends Controller
{
    use ResponseAPI;

    protected $cms_service;

    public function __construct(MobileCmsService $cms_service)
    {
        $this->cms_service = $cms_service;
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

        return $this->success(Message::FETCH, $this->cms_service->pages($business_id));
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

        $page = $this->cms_service->page($business_id, $slug);

        if (!$page) {
            return $this->error('Page not found', 404);
        }

        return $this->success(Message::FETCH, $page);
    }
}
