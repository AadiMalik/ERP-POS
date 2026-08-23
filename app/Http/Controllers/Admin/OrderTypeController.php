<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\HasLookupTypeCrudActions;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\OrderTypeService;
use App\Traits\ResponseAPI;

class OrderTypeController extends Controller
{
    use ResponseAPI;
    use HasLookupTypeCrudActions;

    protected $order_type_service;
    protected $business_service;

    public function __construct(OrderTypeService $order_type_service, BusinessService $business_service)
    {
        $this->middleware('permission:order-type.view')->only(['index', 'getData']);
        $this->middleware('permission:order-type.create|order-type.edit')->only(['store']);
        $this->middleware('permission:order-type.edit')->only(['edit']);
        $this->middleware('permission:order-type.delete')->only(['destroy']);
        $this->middleware('permission:order-type.status')->only(['status']);

        $this->order_type_service = $order_type_service;
        $this->business_service = $business_service;
    }

    protected function lookupTypeService()
    {
        return $this->order_type_service;
    }

    protected function lookupTypeTable(): string
    {
        return 'order_types';
    }

    protected function lookupTypePkField(): string
    {
        return 'order_type_id';
    }

    public function index()
    {
        $business = $this->business_service->getAllActive();
        return view('admin.order-type.index', compact('business'));
    }
}
