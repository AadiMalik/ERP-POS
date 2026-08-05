<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\SupplierService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Session;

class SupplierController extends Controller
{
    use ResponseAPI;

    protected $supplier_service;
    protected $business_service;

    public function __construct(
        SupplierService $supplier_service,
        BusinessService $business_service
    ) {
        $this->supplier_service = $supplier_service;
        $this->business_service = $business_service;
    }
    public function index()
    {
        $business = $this->business_service->getAllActive();
        return view('admin.supplier.index', compact('business'));
    }
    public function getData(Request $request)
    {
        return $this->supplier_service->getData($request->all());
    }
    public function create()
    {
        $business = $this->business_service->getAllActive();
        $prefix = session('supplier_setting.supplier_code_prefix') ?? 'SUP-';
        return view('admin.supplier.create', compact('business', 'prefix'));
    }

    public function store(Request $request)
    {
        $prefix = session('supplier_setting.supplier_code_prefix') ?? 'SUP-';

        if ($request->filled('code')) {
            $request->merge([
                'code' => $prefix . str_pad($request->code, 4, '0', STR_PAD_LEFT)
            ]);
        }
        $rules = [
            'code' => [
                'nullable',
                Rule::unique('suppliers', 'code')
                    ->where(function ($query) use ($request) {
                        return $query->where('business_id', $request->business_id ?? Auth::user()->business_id)
                            ->where('is_deleted', 0);
                    })
                    ->ignore($request->supplier_id, 'supplier_id')
            ],
            'name' => [
                'required',
                Rule::unique('suppliers', 'name')
                    ->where(function ($query) use ($request) {
                        return $query->where('business_id', $request->business_id ?? Auth::user()->business_id)
                            ->where('is_deleted', 0);
                    })
                    ->ignore($request->supplier_id, 'supplier_id')
            ],
            'company_name' => 'required',
        ];

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return redirect()->back()->withErrors($validate)->withInput();
        }

        // Build supplier data
        $obj = [
            'supplier_id' => $request->supplier_id,
            'business_id' => $request->business_id ?? Auth::user()->business_id,
            'branch_id' => $request->branch_id ?? Auth::user()->branch_id,
            'code' => $request->code ?? generateSupplierCode(),
            'name' => $request->name,
            'company_name' => $request->company_name,
            'contact_person' => $request->contact_person ?? null,
            'email' => $request->email ?? null,
            'phone' => $request->phone ?? null,
            'website' => $request->website ?? null,
            'address' => $request->address ?? null,
            'city' => $request->city ?? null,
            'state' => $request->state ?? null,
            'country' => $request->country ?? null,
            'zip_code' => $request->zip_code ?? null,
            'ntn' => $request->ntn ?? null,
            'strn' => $request->strn ?? null,
            'credit_limit' => $request->credit_limit ?? null,
            'credit_days' => $request->credit_days ?? null,
            'balance' => $request->balance ?? null,
            'description' => $request->description ?? null,
            'status' => $request->status ?? 'active',
        ];

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $path = public_path('uploads/supplier');
            if (!File::exists($path)) {
                File::makeDirectory($path, 0755, true);
            }
            $file->move($path, $fileName);
            $obj['image'] = $fileName;
        }
        // Create/update supplier
        $supplier = $this->supplier_service->save($obj);

        return redirect('admin/supplier')
            ->with('success', empty($request->supplier_id) ? Message::SAVE : Message::UPDATE);
    }
    public function edit($supplier_id)
    {
        $supplier = $this->supplier_service->getById($supplier_id);
        $business = $this->business_service->getAllActive();
        $prefix = session('supplier_setting.supplier_code_prefix') ?? 'SUP-';
        return view('admin.supplier.create', compact('supplier', 'business','prefix'));
    }

    public function status($supplier_id)
    {
        try {
            $this->supplier_service->status($supplier_id);
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

    public function destroy($supplier_id)
    {
        try {

            $this->supplier_service->delete($supplier_id);

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
            $suppliers = $this->supplier_service->getByBusiness($business_id);
            return $this->success(
                Message::SUCCESS,
                $suppliers
            );
        } catch (Exception $e) {
            return $this->error(
                Message::ERROR
            );
        }
    }
}
