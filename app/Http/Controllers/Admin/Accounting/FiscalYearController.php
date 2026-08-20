<?php

namespace App\Http\Controllers\Admin\Accounting;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\FiscalYearService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * Advanced Accounting Mode screen. Gated by businessAccountingAdvancedModeEnabled()
 * as a UX guarantee (Simple-mode businesses never see this), on top of the
 * real authorization layer (fiscal-year.* permissions).
 */
class FiscalYearController extends Controller
{
    use ResponseAPI;

    protected $fiscal_year_service;

    public function __construct(FiscalYearService $fiscal_year_service)
    {
        $this->middleware('permission:fiscal-year.view')->only(['index', 'getData', 'edit']);
        $this->middleware('permission:fiscal-year.create|fiscal-year.edit')->only(['store']);
        $this->middleware('permission:fiscal-year.delete')->only(['destroy']);
        $this->middleware(function ($request, $next) {
            if (!businessAccountingAdvancedModeEnabled()) {
                abort(403, 'Advanced Accounting Mode is not enabled for this business.');
            }

            return $next($request);
        });

        $this->fiscal_year_service = $fiscal_year_service;
    }

    public function index()
    {
        return view('admin.fiscal_year.index');
    }

    public function getData(Request $request)
    {
        $obj = $request->all();
        $obj['business_id'] = $obj['business_id'] ?? Auth::user()->business_id;

        return $this->success(Message::FETCH, $this->fiscal_year_service->getData($obj));
    }

    public function store(Request $request)
    {
        $rules = [
            'fiscal_year_id' => 'nullable|exists:fiscal_years,fiscal_year_id',
            'name'           => 'required|string|max:100',
            'start_date'     => 'required|date',
            'end_date'       => 'required|date|after:start_date',
        ];

        $validate = Validator::make($request->all(), $rules);

        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        try {
            $obj = $request->only(['fiscal_year_id', 'name', 'start_date', 'end_date']);
            $obj['business_id'] = $request->business_id ?? Auth::user()->business_id;

            $fiscal_year = $this->fiscal_year_service->save($obj);

            return $this->success(
                empty($request->fiscal_year_id) ? Message::SAVE : Message::UPDATE,
                $fiscal_year
            );
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function edit($fiscal_year_id)
    {
        try {
            return $this->success(Message::FETCH, $this->fiscal_year_service->getById($fiscal_year_id));
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function destroy($fiscal_year_id)
    {
        try {
            $this->fiscal_year_service->delete($fiscal_year_id);

            return $this->success(Message::DELETE, []);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
