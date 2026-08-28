<?php

namespace App\Http\Controllers\Admin\Intro;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\Intro\MediaService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MediaController extends Controller
{
    use ResponseAPI;

    protected $service;

    public function __construct(MediaService $service)
    {
        $this->middleware('superadmin');
        $this->middleware('permission:intro-media.view')->only(['index', 'getData', 'show']);
        $this->middleware('permission:intro-media.create')->only(['store']);
        $this->middleware('permission:intro-media.delete')->only(['destroy']);
        $this->service = $service;
    }

    public function index()
    {
        return view('admin.intro.media.index');
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

    public function store(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'file' => 'required|file|max:10240',
            'collection' => 'nullable|string|max:100',
            'alt_text' => 'nullable|string|max:255',
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }
        try {
            $row = $this->service->upload(
                $request->file('file'),
                $request->input('collection', 'general'),
                $request->input('alt_text')
            );
            return $this->success(Message::SAVE, $row);
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
