<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\WebsiteBenefitService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class WebsiteBenefitController extends Controller
{
    use ResponseAPI;

    protected $business_service;
    protected $benefit_service;

    public function __construct(BusinessService $business_service, WebsiteBenefitService $benefit_service)
    {
        $this->middleware('permission:website-benefit.view')->only(['index', 'getData']);
        $this->middleware('permission:website-benefit.create|website-benefit.edit')->only(['store']);
        $this->middleware('permission:website-benefit.edit')->only(['edit']);
        $this->middleware('permission:website-benefit.delete')->only(['destroy']);
        $this->middleware('permission:website-benefit.status')->only(['status']);

        $this->business_service = $business_service;
        $this->benefit_service = $benefit_service;
    }

    public function index()
    {
        $business = $this->business_service->getAll();
        $groups = [
            'why_shop_with_us' => 'Why Shop With Us',
            'product_trust' => 'Product Page Trust Badges',
            'cart_trust' => 'Cart Page Trust Badges',
            'login_promo' => 'Login Page Promo Bullets',
            'signup_promo' => 'Signup Page Promo Bullets',
            'about_values' => 'About Page Values',
            'delivery_options' => 'Delivery Options',
            'payment_methods' => 'Payment Method Info',
            'payment_icons' => 'Footer Payment Icons',
            'announcement_bar' => 'Announcement Bar Messages',
        ];
        return view('admin.website_benefit.index', compact('business', 'groups'));
    }

    public function getData(Request $request)
    {
        return $this->benefit_service->getData($request->all());
    }

    public function store(Request $request)
    {
        $rules = [
            'group' => 'required|string|max:60',
            'title' => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
            'value' => 'nullable|string|max:60',
            'code' => 'nullable|string|max:60',
            'icon' => 'nullable|string|max:100',
            'icon_color' => 'nullable|string|max:20',
            'sort_order' => 'nullable|integer',
        ];

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        $obj = $request->only(['benefit_id', 'group', 'title', 'description', 'value', 'code', 'icon', 'icon_color', 'sort_order']);
        $obj['business_id'] = $request->business_id ?? Auth::user()->business_id;
        $obj['status'] = $request->status ?? 'active';

        $benefit = $this->benefit_service->save($obj);

        return $this->success(empty($request->benefit_id) ? Message::SAVE : Message::UPDATE, $benefit);
    }

    public function edit($id)
    {
        try {
            return $this->success(Message::FETCH, $this->benefit_service->getById($id));
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function status($id)
    {
        try {
            $this->benefit_service->status($id);
            return $this->success(Message::STATUS, []);
        } catch (Exception $e) {
            return $this->error(Message::ERROR);
        }
    }

    public function destroy($id)
    {
        try {
            $this->benefit_service->delete($id);
            return $this->success(Message::DELETE, []);
        } catch (Exception $e) {
            return $this->error(Message::ERROR);
        }
    }
}
