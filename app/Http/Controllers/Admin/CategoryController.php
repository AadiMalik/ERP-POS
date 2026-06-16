<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\CategoryService;
use App\Services\Concrete\Admin\BusinessService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    use ResponseAPI;

    protected $business_service;
    protected $category_service;

    public function __construct(BusinessService $business_service, CategoryService $category_service)
    {
        $this->business_service = $business_service;
        $this->category_service = $category_service;
    }

    public function index()
    {
        $business =  $this->business_service->getAll();
        return view('admin.category.index', compact('business'));
    }

    public function getData(Request $request)
    {
        return $this->category_service->getData($request->all());
    }

    public function store(Request $request)
    {
        $rules = [
            'name' => [
                'required',
                Rule::unique('categories', 'name')
                    ->where(function ($query) use ($request) {
                        return $query->where('business_id', $request->business_id ?? Auth::user()->business_id)
                            ->where('is_deleted', 0);
                    })
                    ->ignore($request->category_id, 'category_id')
            ],
            'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048'
        ];

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }


        $obj = $request->only([
            'category_id',
            'name',
        ]);
        if ($request->hasFile('logo')) {

            $file = $request->file('logo');

            $fileName = time() . '_' . $file->getClientOriginalName();

            $file->move(public_path('uploads/category'), $fileName);

            $obj['logo'] = $fileName;
        }
        $obj['business_id'] = $request->business_id ??  Auth::user()->business_id;
        $obj['status'] = $request->status ?? 'active';

        // create/update category
        $category = $this->category_service->save($obj);
        return $this->success(
            empty($request->category_id) ? Message::SAVE : Message::UPDATE,
            $category
        );
    }
    public function edit($category_id)
    {
        try {
            $category = $this->category_service->getById($category_id);
            return $this->success(
                Message::FETCH,
                $category
            );
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function status($category_id)
    {
        try {
            $this->category_service->status($category_id);
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

    public function destroy($category_id)
    {
        try {

            $this->category_service->delete($category_id);

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

    public function byBusiness($business_id)
    {
        try {
            $category = $this->category_service->getByBusiness($business_id);
            return $this->success(
                Message::SUCCESS,
                $category
            );
        } catch (Exception $e) {
            return $this->error(
                Message::ERROR
            );
        }
    }
}
