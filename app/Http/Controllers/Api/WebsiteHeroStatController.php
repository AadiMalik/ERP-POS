<?php

namespace App\Http\Controllers\Api;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\WebsiteHeroStatService;
use App\Traits\ResponseAPI;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WebsiteHeroStatController extends Controller
{
    use ResponseAPI;

    protected $stat_service;

    public function __construct(WebsiteHeroStatService $stat_service)
    {
        $this->stat_service = $stat_service;
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

        return $this->success(Message::FETCH, $this->stat_service->getActivePublicByBusiness($business_id));
    }
}
