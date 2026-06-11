<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\PackageService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class BusinessController extends Controller
{
    use ResponseAPI;

    protected $business_service;
    protected $package_service;

    public function __construct(BusinessService $business_service, PackageService $package_service)
    {
        $this->business_service = $business_service;
        $this->package_service = $package_service;
    }

    public function index()
    {
        return view('admin.business.index');
    }

    public function getData(Request $request)
    {
        return $this->business_service->getData($request->all());
    }
    public function create()
    {
        $packages = $this->package_service->getAll();
        return view('admin.business.create', compact('packages'));
    }


    public function store(Request $request)
    {
        $rules = [
            'name' => [
                'required',
                Rule::unique('businesses', 'name')
                    ->where(function ($query) use ($request) {
                        return $query->where('is_deleted', 0);
                    })
                    ->ignore($request->business_id, 'business_id')
            ],
            'owner_name' => 'required',
            'owner_email' => 'required|email',
        ];

        if (empty($request->business_id)) {
            $rules['package_id'] = 'required|exists:packages,package_id';
        }

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return redirect()->back()->withErrors($validate)->withInput();
        }


        $obj = $request->only([
            'business_id',
            'package_id',
            'owner_name',
            'owner_email',
            'owner_phone',
            'code',
            'name',
            'email',
            'phone',
            'address',
            'city',
            'state',
            'country',
            'description',
        ]);
        if ($request->hasFile('logo')) {

            $file = $request->file('logo');

            $fileName = time() . '_' . $file->getClientOriginalName();

            $path = public_path('uploads/business');

            if (!File::exists($path)) {
                File::makeDirectory($path, 0755, true);
            }

            $file->move($path, $fileName);

            $obj['logo'] = $fileName;
        }
        $obj['status'] = $request->status ?? 'active';

        // create/update business
        $business = $this->business_service->save($obj);
        return redirect('admin/business')
            ->with('success', empty($request->business_id) ? Message::SAVE : Message::UPDATE);
    }
    public function edit($business_id)
    {
        $business = $this->business_service->getById($business_id);
        $packages = $this->package_service->getAll();
        return view('admin.business.create', compact('business', 'packages'));
    }

    public function destroy($id)
    {
        try {

            $this->business_service->delete($id);

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
}
