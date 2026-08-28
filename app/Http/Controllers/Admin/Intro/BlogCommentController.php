<?php

namespace App\Http\Controllers\Admin\Intro;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\Intro\BlogCommentService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BlogCommentController extends Controller
{
    use ResponseAPI;

    protected $service;

    public function __construct(BlogCommentService $service)
    {
        $this->middleware('superadmin');
        $this->middleware('permission:intro-blog-comment.view')->only(['index', 'getData', 'show']);
        $this->middleware('permission:intro-blog-comment.moderate')->only(['moderate']);
        $this->middleware('permission:intro-blog-comment.delete')->only(['destroy']);
        $this->service = $service;
    }

    public function index()
    {
        return view('admin.intro.blog_comments.index');
    }

    public function getData(Request $request)
    {
        return $this->service->getData($request->all());
    }

    public function show($id)
    {
        try {
            return $this->success(Message::FETCH, $this->service->getById($id));
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function moderate(Request $request, $id)
    {
        $validate = Validator::make($request->all(), [
            'status' => 'required|in:approved,rejected,spam,hidden,pending',
            'moderation_note' => 'nullable|string',
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }
        try {
            $this->service->moderate($id, $request->status, $request->moderation_note);
            return $this->success(Message::UPDATE, []);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $this->service->delete($id);
            return $this->success(Message::DELETE, []);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
