<?php

namespace App\Http\Controllers\Api\Intro;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Api\Intro\IntroPublicService;
use App\Traits\ResponseAPI;
use Exception;

class HomepageController extends Controller
{
    use ResponseAPI;

    protected $service;

    public function __construct(IntroPublicService $service)
    {
        $this->service = $service;
    }

    public function show()
    {
        try {
            return $this->success(Message::FETCH, $this->service->homepage());
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}