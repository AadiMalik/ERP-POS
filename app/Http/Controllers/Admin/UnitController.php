<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\UnitService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UnitController extends Controller
{
    use ResponseAPI;

    protected $unit_service;

    public function __construct(UnitService $unit_service)
    {
        $this->middleware('permission:unit.view')->only(['index', 'getData']);
        $this->middleware('permission:unit.create|unit.edit')->only(['store']);
        $this->middleware('permission:unit.edit')->only(['edit']);
        $this->middleware('permission:unit.delete')->only(['destroy']);
        $this->middleware('permission:unit.status')->only(['status']);

        $this->unit_service = $unit_service;
    }

    public function index()
    {
        return view('admin.unit.index');
    }

    public function getData(Request $request)
    {
        return $this->unit_service->getData($request->all());
    }

    public function store(Request $request)
    {
        $rules = [
            'name' => ['required', 'string', 'max:255',
                Rule::unique('units', 'name')
                    ->where(function ($query) use ($request) {
                        return $query->where('is_deleted', 0);
                    })
                    ->ignore($request->unit_id, 'unit_id')
            ],
        ];

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }


        $obj = $request->only([
            'unit_id',
            'name',
        ]);
        $obj['status'] = $request->status ?? 'active';

        // create/update unit
        $unit = $this->unit_service->save($obj);
        return $this->success(
            empty($request->unit_id) ? Message::SAVE : Message::UPDATE,
            $unit
        );
    }
    public function edit($unit_id)
    {
        try {
            $unit = $this->unit_service->getById($unit_id);
            return $this->success(
                Message::FETCH,
                $unit
            );
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function status($unit_id)
    {
        try {
            $this->unit_service->status($unit_id);
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

    public function destroy($unit_id)
    {
        try {

            $this->unit_service->delete($unit_id);

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
