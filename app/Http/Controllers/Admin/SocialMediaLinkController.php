<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\SocialMediaLinkService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class SocialMediaLinkController extends Controller
{
    use ResponseAPI;

    protected $business_service;
    protected $link_service;

    public function __construct(BusinessService $business_service, SocialMediaLinkService $link_service)
    {
        $this->middleware('permission:social-media.view')->only(['index', 'getData']);
        $this->middleware('permission:social-media.create|social-media.edit')->only(['store']);
        $this->middleware('permission:social-media.edit')->only(['edit']);
        $this->middleware('permission:social-media.delete')->only(['destroy']);
        $this->middleware('permission:social-media.status')->only(['status']);

        $this->business_service = $business_service;
        $this->link_service = $link_service;
    }

    public function index()
    {
        $business = $this->business_service->getAll();
        return view('admin.social_media.index', compact('business'));
    }

    public function getData(Request $request)
    {
        return $this->link_service->getData($request->all());
    }

    public function store(Request $request)
    {
        $rules = [
            'platform' => 'required|string|max:100',
            'url' => 'required|url|max:255',
            'icon' => 'nullable|string|max:100',
            'icon_color' => 'nullable|string|max:20',
            'display_color' => 'nullable|string|max:20',
            'sort_order' => 'nullable|integer',
        ];

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        $obj = $request->only(['social_media_link_id', 'platform', 'url', 'icon', 'icon_color', 'display_color', 'sort_order']);
        $obj['business_id'] = $request->business_id ?? Auth::user()->business_id;
        $obj['status'] = $request->status ?? 'active';

        $link = $this->link_service->save($obj);

        return $this->success(empty($request->social_media_link_id) ? Message::SAVE : Message::UPDATE, $link);
    }

    public function edit($id)
    {
        try {
            return $this->success(Message::FETCH, $this->link_service->getById($id));
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function status($id)
    {
        try {
            $this->link_service->status($id);
            return $this->success(Message::STATUS, []);
        } catch (Exception $e) {
            return $this->error(Message::ERROR);
        }
    }

    public function destroy($id)
    {
        try {
            $this->link_service->delete($id);
            return $this->success(Message::DELETE, []);
        } catch (Exception $e) {
            return $this->error(Message::ERROR);
        }
    }
}
