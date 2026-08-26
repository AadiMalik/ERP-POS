<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\WebsiteFaqService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class WebsiteFaqController extends Controller
{
    use ResponseAPI;

    protected $business_service;
    protected $faq_service;

    public function __construct(BusinessService $business_service, WebsiteFaqService $faq_service)
    {
        $this->middleware('permission:website-faq.view')->only(['index', 'getData']);
        $this->middleware('permission:website-faq.create|website-faq.edit')->only(['store']);
        $this->middleware('permission:website-faq.edit')->only(['edit']);
        $this->middleware('permission:website-faq.delete')->only(['destroy']);
        $this->middleware('permission:website-faq.status')->only(['status']);

        $this->business_service = $business_service;
        $this->faq_service = $faq_service;
    }

    public function index()
    {
        $business = $this->business_service->getAll();
        return view('admin.website_faq.index', compact('business'));
    }

    public function getData(Request $request)
    {
        return $this->faq_service->getData($request->all());
    }

    public function store(Request $request)
    {
        $rules = [
            'question' => 'required|string|max:255',
            'answer' => 'nullable|string',
            'sort_order' => 'nullable|integer',
        ];

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        $obj = $request->only(['faq_id', 'question', 'answer', 'sort_order']);
        $obj['business_id'] = $request->business_id ?? Auth::user()->business_id;
        $obj['status'] = $request->status ?? 'active';

        $faq = $this->faq_service->save($obj);

        return $this->success(empty($request->faq_id) ? Message::SAVE : Message::UPDATE, $faq);
    }

    public function edit($faq_id)
    {
        try {
            return $this->success(Message::FETCH, $this->faq_service->getById($faq_id));
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function status($faq_id)
    {
        try {
            $this->faq_service->status($faq_id);
            return $this->success(Message::STATUS, []);
        } catch (Exception $e) {
            return $this->error(Message::ERROR);
        }
    }

    public function destroy($faq_id)
    {
        try {
            $this->faq_service->delete($faq_id);
            return $this->success(Message::DELETE, []);
        } catch (Exception $e) {
            return $this->error(Message::ERROR);
        }
    }
}
