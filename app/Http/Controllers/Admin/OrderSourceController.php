<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\HasLookupTypeCrudActions;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\OrderSourceService;
use App\Traits\ResponseAPI;

class OrderSourceController extends Controller
{
    use ResponseAPI;
    use HasLookupTypeCrudActions;

    protected $order_source_service;
    protected $business_service;

    public function __construct(OrderSourceService $order_source_service, BusinessService $business_service)
    {
        $this->middleware('permission:order-source.view')->only(['index', 'getData']);
        $this->middleware('permission:order-source.create|order-source.edit')->only(['store']);
        $this->middleware('permission:order-source.edit')->only(['edit']);
        $this->middleware('permission:order-source.delete')->only(['destroy']);
        $this->middleware('permission:order-source.status')->only(['status']);

        $this->order_source_service = $order_source_service;
        $this->business_service = $business_service;
    }

    protected function lookupTypeService()
    {
        return $this->order_source_service;
    }

    protected function lookupTypeTable(): string
    {
        return 'order_sources';
    }

    protected function lookupTypePkField(): string
    {
        return 'order_source_id';
    }

    public function index()
    {
        $business = $this->business_service->getAllActive();
        return view('admin.order-source.index', compact('business'));
    }
}
