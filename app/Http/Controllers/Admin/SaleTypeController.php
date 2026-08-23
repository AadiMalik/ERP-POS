<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Message;
use App\Http\Controllers\Admin\Concerns\HasLookupTypeCrudActions;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\SaleTypeService;
use App\Traits\ResponseAPI;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SaleTypeController extends Controller
{
    use ResponseAPI;
    use HasLookupTypeCrudActions;

    protected $sale_type_service;

    public function __construct(SaleTypeService $sale_type_service)
    {
        $this->middleware('permission:sale-type.view')->only(['getData', 'list']);
        $this->middleware('permission:sale-type.create|sale-type.edit')->only(['store']);
        $this->middleware('permission:sale-type.edit')->only(['edit']);
        $this->middleware('permission:sale-type.delete')->only(['destroy']);
        $this->middleware('permission:sale-type.status')->only(['status']);

        $this->sale_type_service = $sale_type_service;
    }

    protected function lookupTypeService()
    {
        return $this->sale_type_service;
    }

    protected function lookupTypeTable(): string
    {
        return 'sale_types';
    }

    protected function lookupTypePkField(): string
    {
        return 'sale_type_id';
    }

    /**
     * Plain list (active + inactive) for the inline Sale Types manager in
     * the POS Settings tab. Unique to Sale Type - no index() page exists
     * for it (see AbstractLookupTypeService's/HasLookupTypeCrudActions'
     * doc comments).
     */
    public function list(Request $request)
    {
        $business_id = $request->business_id ?? Auth::user()->business_id;

        return $this->success(Message::FETCH, $this->sale_type_service->getAll($business_id));
    }
}
