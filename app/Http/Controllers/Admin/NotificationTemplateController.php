<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Message;
use App\Enums\RoleNames;
use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\NotificationTemplateService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class NotificationTemplateController extends Controller
{
    use ResponseAPI;

    protected $notification_template_service;
    protected $business_service;

    public function __construct(
        NotificationTemplateService $notification_template_service,
        BusinessService $business_service
    ) {
        $this->middleware('permission:notification-template.view')->only(['index', 'getData']);
        $this->middleware('permission:notification-template.create')->only(['create']);
        $this->middleware('permission:notification-template.create|notification-template.edit')->only(['store']);
        $this->middleware('permission:notification-template.edit')->only(['edit']);
        $this->middleware('permission:notification-template.delete')->only(['destroy']);
        $this->middleware('permission:notification-template.status')->only(['status']);

        $this->notification_template_service = $notification_template_service;
        $this->business_service = $business_service;
    }

    public function index()
    {
        $business = $this->business_service->getAll();

        return view('admin.notification-template.index', compact('business'));
    }

    public function getData(Request $request)
    {
        return $this->notification_template_service->getData($request->all());
    }

    public function create()
    {
        $business = $this->business_service->getAll();

        return view('admin.notification-template.create', compact('business'));
    }

    public function store(Request $request)
    {
        $rules = [
            'name' => 'required|string|max:191',
            'title' => 'required|string|max:191',
            'body' => 'required|string',
            'image' => 'nullable|string|max:500',
            'data' => 'nullable|string',
            'status' => 'nullable|in:active,inactive',
        ];

        if (getRoleName() === RoleNames::SUPERADMIN) {
            $rules['business_id'] = 'required|exists:businesses,business_id';
        }

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return redirect()->back()->withErrors($validate)->withInput();
        }

        if (!empty($request->data)) {
            json_decode($request->data);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return redirect()->back()
                    ->withErrors(['data' => 'Custom data must be valid JSON.'])
                    ->withInput();
            }
        }

        $obj = $request->only([
            'notification_template_id',
            'name',
            'title',
            'body',
            'image',
            'data',
            'status',
        ]);
        $obj['business_id'] = $request->business_id ?? Auth::user()->business_id;
        $obj['status'] = $request->status ?? Status::ACTIVE;

        try {
            $this->notification_template_service->save($obj);
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }

        return redirect('admin/notification-template')
            ->with('success', empty($request->notification_template_id) ? Message::SAVE : Message::UPDATE);
    }

    public function edit($id)
    {
        $template = $this->notification_template_service->getById($id);
        if (getRoleName() !== RoleNames::SUPERADMIN
            && $template->business_id !== Auth::user()->business_id) {
            abort(403);
        }

        $business = $this->business_service->getAll();

        return view('admin.notification-template.create', compact('template', 'business'));
    }

    public function status($id)
    {
        try {
            $template = $this->notification_template_service->getById($id);
            if (getRoleName() !== RoleNames::SUPERADMIN
                && $template->business_id !== Auth::user()->business_id) {
                return $this->error('Unauthorized', 403);
            }

            $this->notification_template_service->status($id);

            return $this->success(Message::UPDATE, []);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $template = $this->notification_template_service->getById($id);
            if (getRoleName() !== RoleNames::SUPERADMIN
                && $template->business_id !== Auth::user()->business_id) {
                return $this->error('Unauthorized', 403);
            }

            $this->notification_template_service->delete($id);

            return $this->success(Message::DELETE, []);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
