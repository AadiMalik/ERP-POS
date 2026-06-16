<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\CategoryService;
use App\Services\Concrete\Admin\SubCategoryService;
use App\Services\Concrete\Admin\BusinessService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class SubCategoryController extends Controller
{
    use ResponseAPI;

    protected $business_service;
    protected $category_service;
    protected $sub_category_service;

    public function __construct(
        BusinessService $business_service,
        CategoryService $category_service,
        SubCategoryService $sub_category_service
    ) {
        $this->business_service = $business_service;
        $this->category_service = $category_service;
        $this->sub_category_service = $sub_category_service;
    }

    public function index()
    {
        $business =  $this->business_service->getAll();
        $categories =  $this->category_service->getAllActive();
        return view('admin.sub_category.index', compact('business','categories'));
    }

    public function getData(Request $request)
    {
        return $this->sub_category_service->getData($request->all());
    }

    public function store(Request $request)
    {
        $rules = [
            'name' => [
                'required',
                Rule::unique('sub_categories', 'name')
                    ->where(function ($query) use ($request) {
                        return $query->where('business_id', $request->business_id ?? Auth::user()->business_id)
                            ->where('is_deleted', 0);
                    })
                    ->ignore($request->category_id, 'category_id')
            ],
            'category_id' => 'required|exists:categories,category_id',
            'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048'
        ];

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }


        $obj = $request->only([
            'sub_category_id',
            'category_id',
            'name',
        ]);
        if ($request->hasFile('logo')) {

            $file = $request->file('logo');

            $fileName = time() . '_' . $file->getClientOriginalName();

            $file->move(public_path('uploads/sub_category'), $fileName);

            $obj['logo'] = $fileName;
        }
        $obj['business_id'] = $request->business_id ??  Auth::user()->business_id;
        $obj['status'] = $request->status ?? 'active';

        // create/update sub_category
        $sub_category = $this->sub_category_service->save($obj);
        return $this->success(
            empty($request->sub_category_id) ? Message::SAVE : Message::UPDATE,
            $sub_category
        );
    }
    public function edit($sub_category_id)
    {
        try {
            $sub_category = $this->sub_category_service->getById($sub_category_id);
            return $this->success(
                Message::FETCH,
                $sub_category
            );
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function status($sub_category_id)
    {
        try {
            $this->sub_category_service->status($sub_category_id);
            return $this->success(
                Message::STATUS,
                []
            );
        } catch (Exception $e) {
            return $this->error(
                Message::ERROR
            );
        }
    }

    public function destroy($sub_category_id)
    {
        try {

            $this->sub_category_service->delete($sub_category_id);

            return $this->success(
                Message::DELETE,
                []
            );
        } catch (Exception $e) {

            return $this->error(
                Message::ERROR
            );
        }
    }

    public function byCategory($category_id)
    {
        try {
            $sub_category = $this->sub_category_service->getByCategory($category_id);
            return $this->success(
                Message::SUCCESS,
                $sub_category
            );
        } catch (Exception $e) {
            return $this->error(
                Message::ERROR
            );
        }
    }

    public function byBusiness($business_id)
    {
        try {
            $brandes = $this->sub_category_service->getByBusiness($business_id);
            return $this->success(
                Message::SUCCESS,
                $brandes
            );
        } catch (Exception $e) {
            return $this->error(
                Message::ERROR
            );
        }
    }
}
