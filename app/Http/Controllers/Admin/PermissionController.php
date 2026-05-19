<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\PermissionService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PermissionController extends Controller
{
    use ResponseAPI;
    protected $permission_service;
    public function __construct(PermissionService  $permission_service)
    {
        $this->permission_service = $permission_service;
    }
    public function index()
    {
        return view('admin.permissions.index');
    }

    public function getData(Request $request)
    {
        return $this->permission_service->getData($request->all());
    }

    public function store(Request $request)
    {

        $rules = [
            'name' => 'required|string|max:50|unique:permissions,name,'.$request->id,
        ];
        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }
        try {
            $obj = [
                'id' => $request->id,
                'name' => $request->name,
                'is_system_only' => ($request->is_system_only=='on')?1:0,
            ];

            $permission = $this->permission_service->save($obj);
            return $this->success(
                Message::SAVE,
                $permission
            );
        } catch (Exception $e) {
            return $this->error(Message::ERROR);
        }
    }

    public function edit($id){
        try {
            $permission = $this->permission_service->edit($id);
            return $this->success(
                Message::FETCH,
                $permission
            );
        } catch (Exception $e) {
            return $this->error(Message::ERROR);
        }
    }
    public function destroy($id)
    {
        try {
            $permission = $this->permission_service->delete($id);
            return $this->success(
                Message::DELETE,
                $permission
            );
        } catch (Exception $e) {
            return $this->error(Message::ERROR);
        }
    }
}
