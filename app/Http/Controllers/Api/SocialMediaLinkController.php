<?php

namespace App\Http\Controllers\Api;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\SocialMediaLinkService;
use App\Traits\ResponseAPI;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SocialMediaLinkController extends Controller
{
    use ResponseAPI;

    protected $link_service;

    public function __construct(SocialMediaLinkService $link_service)
    {
        $this->link_service = $link_service;
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

        return $this->success(Message::FETCH, $this->link_service->getActivePublicByBusiness($business_id));
    }
}
