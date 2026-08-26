<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\WebsiteSectionService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class WebsiteSectionController extends Controller
{
    use ResponseAPI;

    protected $business_service;
    protected $section_service;

    public function __construct(BusinessService $business_service, WebsiteSectionService $section_service)
    {
        $this->middleware('permission:website-section.view')->only(['index', 'getData']);
        $this->middleware('permission:website-section.create|website-section.edit')->only(['store']);
        $this->middleware('permission:website-section.edit')->only(['edit']);
        $this->middleware('permission:website-section.delete')->only(['destroy']);
        $this->middleware('permission:website-section.status')->only(['status']);

        $this->business_service = $business_service;
        $this->section_service = $section_service;
    }

    public function index()
    {
        $business = $this->business_service->getAll();
        $types = [
            'hero' => 'Hero',
            'about_us' => 'About Us',
            'contact_us' => 'Contact Us (intro)',
            'why_shop_with_us' => 'Why Shop With Us',
            'promo_banner' => 'Promo Banner',
            'discount_banner' => 'Discount Banner',
            'featured_products' => 'Featured Products',
            'discounted_products' => 'Discounted Products',
            'trending_products' => 'Trending Products',
            'new_arrivals' => 'New Arrivals',
            'best_sellers' => 'Best Sellers',
            'shop' => 'Shop Page Header',
            'categories' => 'Categories Page Header',
            'cart' => 'Cart Page Header',
            'checkout' => 'Checkout Page Header',
            'wishlist' => 'Wishlist Page Header',
            'newsletter' => 'Newsletter Signup',
            'footer' => 'Footer Blurb',
            'about_cta' => 'About Page CTA Banner',
            'editorial_banner' => 'Editorial/Seasonal Banner',
            'login_promo' => 'Login Page Promo Panel',
            'signup_promo' => 'Signup Page Promo Panel',
            'testimonials' => 'Testimonials Section Header',
        ];
        return view('admin.website_section.index', compact('business', 'types'));
    }

    public function getData(Request $request)
    {
        return $this->section_service->getData($request->all());
    }

    public function store(Request $request)
    {
        $rules = [
            'type' => 'required|string',
            'tagline' => 'nullable|string|max:160',
            'tagline_icon' => 'nullable|string|max:100',
            'heading' => 'nullable|string|max:255',
            'heading_icon' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'image_mobile' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'button_text' => 'nullable|string|max:100',
            'button_link' => 'nullable|string|max:255',
            'link_type' => 'nullable|string|max:50',
            'link_target_id' => 'nullable|string|max:100',
            'secondary_button_text' => 'nullable|string|max:100',
            'secondary_button_link' => 'nullable|string|max:255',
            'secondary_link_type' => 'nullable|string|max:50',
            'secondary_link_target_id' => 'nullable|string|max:100',
            'countdown_end_at' => 'nullable|date',
            'sort_order' => 'nullable|integer',
        ];

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        $obj = $request->only([
            'section_id',
            'type',
            'tagline',
            'tagline_icon',
            'heading',
            'heading_icon',
            'description',
            'button_text',
            'button_link',
            'link_type',
            'link_target_id',
            'secondary_button_text',
            'secondary_button_link',
            'secondary_link_type',
            'secondary_link_target_id',
            'countdown_end_at',
            'sort_order',
        ]);

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/website/section'), $fileName);
            $obj['image'] = $fileName;
        }
        if ($request->hasFile('image_mobile')) {
            $file = $request->file('image_mobile');
            $fileName = time() . '_mobile_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/website/section'), $fileName);
            $obj['image_mobile'] = $fileName;
        }

        $obj['business_id'] = $request->business_id ?? Auth::user()->business_id;
        $obj['status'] = $request->status ?? 'active';

        $section = $this->section_service->save($obj);

        return $this->success(empty($request->section_id) ? Message::SAVE : Message::UPDATE, $section);
    }

    public function edit($section_id)
    {
        try {
            return $this->success(Message::FETCH, $this->section_service->getById($section_id));
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function status($section_id)
    {
        try {
            $this->section_service->status($section_id);
            return $this->success(Message::STATUS, []);
        } catch (Exception $e) {
            return $this->error(Message::ERROR);
        }
    }

    public function destroy($section_id)
    {
        try {
            $this->section_service->delete($section_id);
            return $this->success(Message::DELETE, []);
        } catch (Exception $e) {
            return $this->error(Message::ERROR);
        }
    }
}
