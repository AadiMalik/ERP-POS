<?php

namespace App\Http\Controllers\Admin\Intro;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\Intro\ContactInquiryService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ContactInquiryController extends Controller
{
    use ResponseAPI;

    protected $service;

    public function __construct(ContactInquiryService $service)
    {
        $this->middleware('superadmin');
        $this->middleware('permission:intro-contact.view')->only(['index', 'getData', 'show']);
        $this->middleware('permission:intro-contact.reply')->only(['reply']);
        $this->middleware('permission:intro-contact.edit')->only(['updateStatus']);
        $this->middleware('permission:intro-contact.delete')->only(['destroy']);
        $this->service = $service;
    }

    public function index()
    {
        return view('admin.intro.contact_inquiries.index');
    }

    public function getData(Request $request)
    {
        return $this->service->getData($request->all());
    }

    public function show($id)
    {
        try {
            return $this->success(Message::FETCH, $this->service->getById($id));
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
            $reply = $this->service->reply($id, $request->reply_message);
            return $this->success('Reply sent successfully.', $reply);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function updateStatus(Request $request, $id)
    {
        $validate = Validator::make($request->all(), [
            'status' => 'required|in:new,read,replied,closed',
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }
        try {
            $this->service->updateStatus($id, $request->status);
            return $this->success(Message::STATUS, []);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $this->service->delete($id);
            return $this->success(Message::DELETE, []);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
