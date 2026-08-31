<?php

namespace App\Http\Controllers\Api\Intro;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Api\Intro\IntroPublicService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BlogCommentController extends Controller
{
    use ResponseAPI;

    protected $service;

    public function __construct(IntroPublicService $service)
    {
        $this->service = $service;
    }

    public function store(Request $request)
    {
        if (filled($request->input('website'))) {
            return $this->success(Message::SAVE, null);
        }

        $validate = Validator::make($request->all(), [
            'blog_slug' => 'required_without:intro_blog_id|string',
            'intro_blog_id' => 'required_without:blog_slug|string',
            'name' => 'required|string|max:150',
            'email' => 'required|email|max:150',
            'comment' => 'required|string|max:2000',
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }
        try {
            $data = $request->only(['blog_slug', 'intro_blog_id', 'name', 'email', 'comment']);
            $data['ip_address'] = $request->ip();
            $row = $this->service->submitComment($data);
            return $this->success(Message::SAVE, $row);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}