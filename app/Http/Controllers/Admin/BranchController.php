<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Message;
use App\Enums\RoleNames;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\BranchService;
use App\Services\Concrete\Admin\BusinessService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class BranchController extends Controller
{
    use ResponseAPI;

    protected $business_service;
    protected $branch_service;

    public function __construct(BusinessService $business_service, BranchService $branch_service)
    {
        $this->business_service = $business_service;
        $this->branch_service = $branch_service;
    }

    public function index()
    {
        return view('admin.branch.index');
    }

    public function getData(Request $request)
    {
        return $this->branch_service->getData($request->all());
    }
    public function create()
    {
        $business = (getRoleName() == RoleNames::SUPERADMIN) ? $this->business_service->getAll() : [];
        return view('admin.branch.create', compact('business'));
    }


    public function store(Request $request)
    {
        $rules = [
            'name' => [
                'required',
                Rule::unique('branches', 'name')
                    ->where(function ($query) use ($request) {
                        return $query->where('business_id', $request->business_id ?? Auth::user()->business_id)
                        ->where('is_deleted', 0);
                    })
                    ->ignore($request->branch_id, 'branch_id')
            ],
            'email' => 'required|email',
            'phone' => 'required',
        ];

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return redirect()->back()->withErrors($validate)->withInput();
        }


        $obj = $request->only([
            'branch_id',
            'name',
            'code',
            'name',
            'email',
            'phone',
            'address',
            'city',
            'state',
            'country',
        ]);
        if ($request->hasFile('logo')) {

            $file = $request->file('logo');

            $fileName = time() . '_' . $file->getClientOriginalName();

            $file->move(public_path('uploads/branch'), $fileName);

            $obj['logo'] = $fileName;
        }
        $obj['business_id'] = $request->business_id ??  Auth::user()->business_id;
        $obj['status'] = $request->status ?? 'active';

        // create/update branch
        $branch = $this->branch_service->save($obj);
        return redirect('admin/branch')
            ->with('success', empty($request->branch_id) ? Message::SAVE : Message::UPDATE);
    }
    public function edit($branch_id)
    {
        $branch = $this->branch_service->getById($branch_id);
        $business = $this->business_service->getAll();
        return view('admin.branch.create', compact('branch', 'business'));
    }

    public function destroy($branch_id)
    {
        try {

            $this->branch_service->delete($branch_id);

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
