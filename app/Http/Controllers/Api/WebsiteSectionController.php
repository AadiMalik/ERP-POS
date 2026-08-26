<?php

namespace App\Http\Controllers\Api;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\WebsiteSectionService;
use App\Traits\ResponseAPI;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WebsiteSectionController extends Controller
{
    use ResponseAPI;

    protected $section_service;

    public function __construct(WebsiteSectionService $section_service)
    {
        $this->section_service = $section_service;
    }

    /**
     * Lightweight single-type lookup - used by page headers and other
     * per-page CMS content spots that don't need the full homepage
     * aggregator. Returns the first active section of the given type, or
     * null so callers can keep their own static fallback content.
     */
    public function show(Request $request, $business_id, $type)
    {
        $validate = Validator::make(
            ['business_id' => $business_id],
            ['business_id' => 'required|string|exists:businesses,business_id']
        );

        if ($validate->fails()) {
            return $this->error($validate->errors()->first(), 404);
        }

        $sections = $this->section_service->getActivePublicByBusiness($business_id, $type);

        return $this->success(Message::FETCH, $sections->first());
    }
}
