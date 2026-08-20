<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Message;
use App\Enums\RoleNames;
use App\Http\Controllers\Concerns\HandlesImportExport;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\BrandService;
use App\Services\Concrete\Admin\BusinessService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class BrandController extends Controller
{
    use ResponseAPI;
    use HandlesImportExport;

    protected $business_service;
    protected $brand_service;

    public function __construct(BusinessService $business_service, BrandService $brand_service)
    {
        $this->middleware('permission:brand.view')->only(['index', 'getData', 'byBusiness']);
        $this->middleware('permission:brand.create|brand.edit')->only(['store']);
        $this->middleware('permission:brand.edit')->only(['edit']);
        $this->middleware('permission:brand.delete')->only(['destroy']);
        $this->middleware('permission:brand.status')->only(['status']);
        $this->middleware('permission:brand.import')->only(['importSample', 'importPreview', 'importConfirm']);
        $this->middleware('permission:brand.export')->only(['export']);

        $this->business_service = $business_service;
        $this->brand_service = $brand_service;
    }

    protected function importExportModuleKey(): string
    {
        return 'brand';
    }

    public function index()
    {
        $business =  $this->business_service->getAll();
        return view('admin.brand.index', compact('business'));
    }

    public function getData(Request $request)
    {
        return $this->brand_service->getData($request->all());
    }

    public function store(Request $request)
    {
        $rules = [
            'name' => [
                'required',
                Rule::unique('brands', 'name')
                    ->where(function ($query) use ($request) {
                        return $query->where('business_id', $request->business_id ?? Auth::user()->business_id)
                            ->where('is_deleted', 0);
                    })
                    ->ignore($request->brand_id, 'brand_id')
            ],
            'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048'
        ];

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }


        $obj = $request->only([
            'brand_id',
            'name',
        ]);
        if ($request->hasFile('logo')) {

            $file = $request->file('logo');

            $fileName = time() . '_' . $file->getClientOriginalName();

            $file->move(public_path('uploads/brand'), $fileName);

            $obj['logo'] = $fileName;
        }
        $obj['business_id'] = $request->business_id ??  Auth::user()->business_id;
        $obj['status'] = $request->status ?? 'active';

        // create/update brand
        $brand = $this->brand_service->save($obj);
        return $this->success(
            empty($request->brand_id) ? Message::SAVE : Message::UPDATE,
            $brand
        );
    }
    public function edit($brand_id)
    {
        try {
            $brand = $this->brand_service->getById($brand_id);
            return $this->success(
                Message::FETCH,
                $brand
            );
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function status($brand_id)
    {
        try {
            $this->brand_service->status($brand_id);
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

    public function destroy($brand_id)
    {
        try {

            $this->brand_service->delete($brand_id);

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
            $brandes = $this->brand_service->getByBusiness($business_id);
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
