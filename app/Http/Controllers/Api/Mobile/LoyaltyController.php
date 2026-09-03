<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Api\Mobile\MobileLoyaltyService;
use App\Traits\ResponseAPI;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class LoyaltyController extends Controller
{
    use ResponseAPI;

    protected $loyalty_service;

    public function __construct(MobileLoyaltyService $loyalty_service)
    {
        $this->loyalty_service = $loyalty_service;
    }

    public function show(Request $request, $business_id)
    {
        $validate = Validator::make(
            ['business_id' => $business_id],
            ['business_id' => 'required|string|exists:businesses,business_id']
        );
        if ($validate->fails()) {
            return $this->error($validate->errors()->first(), 404);
        }

        $summary = $this->loyalty_service->summary($business_id, Auth::id());

        return $this->success(Message::FETCH, $summary);
    }

    public function history(Request $request, $business_id)
    {
        $validate = Validator::make(
            ['business_id' => $business_id],
            ['business_id' => 'required|string|exists:businesses,business_id']
        );
        if ($validate->fails()) {
            return $this->error($validate->errors()->first(), 404);
        }

        $result = $this->loyalty_service->history($business_id, Auth::id(), $request->only([
            'page',
            'per_page',
        ]));

        return $this->success(Message::FETCH, $result);
    }
}
