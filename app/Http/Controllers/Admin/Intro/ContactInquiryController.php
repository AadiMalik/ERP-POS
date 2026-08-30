<?php

namespace App\Http\Controllers\Admin\Intro;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Models\Package;
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
        $this->middleware('permission:intro-contact.edit')->only(['updateStatus', 'registerBusiness', 'updatePayment', 'activate']);
        $this->middleware('permission:intro-contact.delete')->only(['destroy']);
        $this->service = $service;
    }

    public function index()
    {
        $packages = Package::where('is_deleted', 0)
            ->where('status', 1)
            ->where(function ($q) {
                $q->where('is_custom', 0)->orWhereNull('is_custom');
            })
            ->orderBy('order')
            ->orderBy('duration_type')
            ->get(['package_id', 'name', 'duration_type', 'price', 'discount']);

        return view('admin.intro.contact_inquiries.index', compact('packages'));
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

    public function registerBusiness(Request $request, $id)
    {
        $validate = Validator::make($request->all(), [
            'package_id' => 'required|exists:packages,package_id',
            'billing_cycle' => 'nullable|in:monthly,yearly',
            'business_name' => 'nullable|string|max:255',
            'owner_name' => 'nullable|string|max:255',
            'owner_email' => 'nullable|email|max:255',
            'owner_phone' => 'nullable|string|max:50',
            'payment_method' => 'nullable|in:cash,bank_transfer,cheque,online',
            'payment_reference' => 'nullable|string|max:255',
            'activate' => 'nullable|boolean',
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        try {
            $inquiry = $this->service->registerBusiness($id, $request->all());
            return $this->success('Business registered successfully.', $inquiry);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function updatePayment(Request $request, $id)
    {
        $validate = Validator::make($request->all(), [
            'payment_method' => 'nullable|in:cash,bank_transfer,cheque,online',
            'payment_reference' => 'nullable|string|max:255',
            'amount' => 'nullable|numeric|min:0.01',
            'notes' => 'nullable|string',
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        try {
            $payment = $this->service->updatePayment($id, $request->all());
            return $this->success('Payment updated.', $payment);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function activate($id)
    {
        try {
            $inquiry = $this->service->activateBusiness($id);
            return $this->success('Business activated and payment confirmed.', $inquiry);
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
