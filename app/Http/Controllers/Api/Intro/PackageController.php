<?php

namespace App\Http\Controllers\Api\Intro;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Api\Intro\IntroPublicService;
use App\Traits\ResponseAPI;
use Exception;

class PackageController extends Controller
{
    use ResponseAPI;

    protected $service;

    public function __construct(IntroPublicService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        try {
            return $this->success(Message::FETCH, $this->service->packages());
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function show($package_id)
    {
        try {
            return $this->success(Message::FETCH, $this->service->package($package_id));
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}