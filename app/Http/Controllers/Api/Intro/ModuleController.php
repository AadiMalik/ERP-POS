<?php

namespace App\Http\Controllers\Api\Intro;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Api\Intro\IntroPublicService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;

class ModuleController extends Controller
{
    use ResponseAPI;

    protected $service;

    public function __construct(IntroPublicService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        try {
            $featured = $request->has('featured') ? filter_var($request->featured, FILTER_VALIDATE_BOOLEAN) : null;
            return $this->success(Message::FETCH, $this->service->modules($request->category, $featured));
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function show($slug)
    {
        try {
            return $this->success(Message::FETCH, $this->service->moduleBySlug($slug));
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}