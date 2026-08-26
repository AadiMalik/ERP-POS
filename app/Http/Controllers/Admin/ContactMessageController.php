<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\ContactMessageService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ContactMessageController extends Controller
{
    use ResponseAPI;

    protected $message_service;

    public function __construct(ContactMessageService $message_service)
    {
        $this->middleware('permission:contact-message.view')->only(['index', 'getData', 'show']);
        $this->middleware('permission:contact-message.reply')->only(['reply']);
        $this->middleware('permission:contact-message.delete')->only(['destroy']);

        $this->message_service = $message_service;
    }

    public function index()
    {
        return view('admin.contact_message.index');
    }

    public function getData(Request $request)
    {
        return $this->message_service->getData($request->all());
    }

    public function show($id)
    {
        try {
            return $this->success(Message::FETCH, $this->message_service->getById($id));
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function reply(Request $request, $id)
    {
        $validate = Validator::make($request->all(), [
            'reply_message' => 'required|string',
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        try {
            $message = $this->message_service->reply($id, Auth::user()->business_id, $request->reply_message);
            return $this->success(Message::UPDATE, $message);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $this->message_service->delete($id);
            return $this->success(Message::DELETE, []);
        } catch (Exception $e) {
            return $this->error(Message::ERROR);
        }
    }
}
