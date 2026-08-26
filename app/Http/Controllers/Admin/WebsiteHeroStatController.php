<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\WebsiteHeroStatService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class WebsiteHeroStatController extends Controller
{
    use ResponseAPI;

    protected $business_service;
    protected $stat_service;

    public function __construct(BusinessService $business_service, WebsiteHeroStatService $stat_service)
    {
        $this->middleware('permission:website-hero-stat.view')->only(['index', 'getData']);
        $this->middleware('permission:website-hero-stat.create|website-hero-stat.edit')->only(['store']);
        $this->middleware('permission:website-hero-stat.edit')->only(['edit']);
        $this->middleware('permission:website-hero-stat.delete')->only(['destroy']);
        $this->middleware('permission:website-hero-stat.status')->only(['status']);

        $this->business_service = $business_service;
        $this->stat_service = $stat_service;
    }

    public function index()
    {
        $business = $this->business_service->getAll();
        return view('admin.website_hero_stat.index', compact('business'));
    }

    public function getData(Request $request)
    {
        return $this->stat_service->getData($request->all());
    }

    public function store(Request $request)
    {
        $rules = [
            'value' => 'required|string|max:50',
            'label' => 'required|string|max:100',
            'icon' => 'nullable|string|max:100',
            'icon_color' => 'nullable|string|max:20',
            'sort_order' => 'nullable|integer',
        ];

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        $obj = $request->only(['hero_stat_id', 'value', 'label', 'icon', 'icon_color', 'sort_order']);
        $obj['business_id'] = $request->business_id ?? Auth::user()->business_id;
        $obj['status'] = $request->status ?? 'active';

        $stat = $this->stat_service->save($obj);

        return $this->success(empty($request->hero_stat_id) ? Message::SAVE : Message::UPDATE, $stat);
    }

    public function edit($id)
    {
        try {
            return $this->success(Message::FETCH, $this->stat_service->getById($id));
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function status($id)
    {
        try {
            $this->stat_service->status($id);
            return $this->success(Message::STATUS, []);
        } catch (Exception $e) {
            return $this->error(Message::ERROR);
        }
    }

    public function destroy($id)
    {
        try {
            $this->stat_service->delete($id);
            return $this->success(Message::DELETE, []);
        } catch (Exception $e) {
            return $this->error(Message::ERROR);
        }
    }
}
