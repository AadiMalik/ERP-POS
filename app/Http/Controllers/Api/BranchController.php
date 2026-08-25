<?php

namespace App\Http\Controllers\Api;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\BranchService;
use App\Traits\ResponseAPI;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BranchController extends Controller
{
    use ResponseAPI;

    protected $branch_service;

    public function __construct(BranchService $branch_service)
    {
        $this->branch_service = $branch_service;
    }

    /**
     * Public storefront endpoint - active branches for a business, used by
     * the Vue frontend's branch selector instead of hard-coded branch data.
     */
    public function index(Request $request, $business_id)
    {
        $validate = Validator::make(
            ['business_id' => $business_id],
            ['business_id' => 'required|string|exists:businesses,business_id']
        );

        if ($validate->fails()) {
            return $this->error($validate->errors()->first(), 404);
        }

        $branches = $this->branch_service->getActivePublicByBusiness($business_id);

        return $this->success(Message::FETCH, $branches);
    }
}
