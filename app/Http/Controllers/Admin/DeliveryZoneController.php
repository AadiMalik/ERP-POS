<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Enums\Message;
use App\Enums\RoleNames;
use App\Services\Concrete\Admin\BranchService;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\DeliveryZoneService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class DeliveryZoneController extends Controller
{
    use ResponseAPI;

    protected $delivery_zone_service;
    protected $business_service;
    protected $branch_service;

    public function __construct(
        DeliveryZoneService $delivery_zone_service,
        BusinessService $business_service,
        BranchService $branch_service
    ) {
        $this->middleware('permission:delivery-zone.view')->only(['index', 'getData', 'byBranch']);
        $this->middleware('permission:delivery-zone.create')->only(['create']);
        $this->middleware('permission:delivery-zone.create|delivery-zone.edit')->only(['store']);
        $this->middleware('permission:delivery-zone.edit')->only(['edit']);
        $this->middleware('permission:delivery-zone.delete')->only(['destroy']);
        $this->middleware('permission:delivery-zone.status')->only(['status']);

        $this->delivery_zone_service = $delivery_zone_service;
        $this->business_service = $business_service;
        $this->branch_service = $branch_service;
    }

    public function index()
    {
        $business = $this->business_service->getAll();
        $branches = $this->branch_service->getAll();
        return view('admin.delivery-zone.index', compact('business', 'branches'));
    }

    public function getData(Request $request)
    {
        return $this->delivery_zone_service->getData($request->all());
    }

    public function create()
    {
        $business = $this->business_service->getAll();
        $branches = $this->branch_service->getAllActive();
        return view('admin.delivery-zone.create', compact('business', 'branches'));
    }

    public function store(Request $request)
    {
        $rules = [
            'min_km' => 'required|numeric|min:0',
            'max_km' => 'required|numeric|gt:min_km',
            'fee' => 'required|numeric|min:0',
        ];
        if (getRoleName() == RoleNames::SUPERADMIN) {
            $rules['business_id'] = 'required|exists:businesses,business_id';
        }
        $rules['branch_id'] = 'required|exists:branches,branch_id';

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return redirect()->back()->withErrors($validate)->withInput();
        }

        $obj = $request->only([
            'delivery_zone_id',
            'name',
            'min_km',
            'max_km',
            'fee',
        ]);
        $obj['business_id'] = $request->business_id ?? Auth::user()->business_id;
        $obj['branch_id'] = $request->branch_id ?? Auth::user()->branch_id;
        $obj['status'] = $request->status ?? 'active';

        try {
            $this->delivery_zone_service->save($obj);
        } catch (Exception $e) {
            return redirect()->back()->withErrors(['max_km' => $e->getMessage()])->withInput();
        }

        return redirect('admin/delivery-zone')
            ->with('success', empty($request->delivery_zone_id) ? Message::SAVE : Message::UPDATE);
    }

    public function edit($delivery_zone_id)
    {
        $delivery_zone = $this->delivery_zone_service->getById($delivery_zone_id);
        $business = $this->business_service->getAll();
        $branches = $this->branch_service->getAllActive();
        return view('admin.delivery-zone.create', compact('delivery_zone', 'business', 'branches'));
    }

    public function status($delivery_zone_id)
    {
        try {
            $this->delivery_zone_service->status($delivery_zone_id);
            return $this->success(Message::STATUS, []);
        } catch (Exception $e) {
            return $this->error(Message::ERROR);
        }
    }

    public function destroy($delivery_zone_id)
    {
        try {
            $this->delivery_zone_service->delete($delivery_zone_id);
            return $this->success(Message::DELETE, []);
        } catch (Exception $e) {
            return $this->error(Message::ERROR);
        }
    }

    public function byBranch($branch_id)
    {
        try {
            $zones = $this->delivery_zone_service->getByBranch($branch_id);
            return $this->success(Message::SUCCESS, $zones);
        } catch (Exception $e) {
            return $this->error(Message::ERROR);
        }
    }
}
