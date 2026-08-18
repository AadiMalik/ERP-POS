<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\Hrm\AssetService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AssetController extends Controller
{
    use ResponseAPI;

    protected $asset_service;

    public function __construct(AssetService $asset_service)
    {
        $this->middleware('permission:asset.view')->only(['index', 'getData']);
        $this->middleware('permission:asset.create')->only(['create']);
        $this->middleware('permission:asset.create|asset.edit')->only(['store']);
        $this->middleware('permission:asset.edit')->only(['edit']);
        $this->middleware('permission:asset.delete')->only(['destroy']);
        $this->middleware('permission:asset.status')->only(['status']);

        $this->asset_service = $asset_service;
    }

    public function index()
    {
        return view('admin.hrm.asset.index');
    }

    public function getData(Request $request)
    {
        return $this->asset_service->getData($request->all());
    }

    public function create()
    {
        return view('admin.hrm.asset.create');
    }

    public function store(Request $request)
    {
        $rules = [
            'name' => 'required|string|max:255',
            'purchase_date' => 'nullable|date',
            'purchase_value' => 'nullable|numeric|min:0',
        ];

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return redirect()->back()->withErrors($validate)->withInput();
        }

        $obj = $request->only(['asset_id', 'asset_tag', 'name', 'category', 'purchase_date', 'purchase_value', 'condition']);
        $obj['business_id'] = Auth::user()->business_id;
        $obj['branch_id'] = Auth::user()->branch_id;

        $this->asset_service->save($obj);

        return redirect('admin/asset')
            ->with('success', empty($request->asset_id) ? Message::SAVE : Message::UPDATE);
    }

    public function edit($asset_id)
    {
        $asset = $this->asset_service->getById($asset_id);
        return view('admin.hrm.asset.create', compact('asset'));
    }

    public function status(Request $request, $asset_id)
    {
        try {
            $this->asset_service->setStatus($asset_id, $request->status);
            return $this->success(Message::STATUS, []);
        } catch (Exception $e) {
            return $this->error(Message::ERROR);
        }
    }

    public function destroy($asset_id)
    {
        try {
            $this->asset_service->delete($asset_id);
            return $this->success(Message::DELETE, []);
        } catch (Exception $e) {
            return $this->error(Message::ERROR);
        }
    }
}
