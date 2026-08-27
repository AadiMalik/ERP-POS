<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Message;
use App\Enums\RoleNames;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\BroadcastNotificationService;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\FirebaseSettingService;
use App\Services\Concrete\Admin\NotificationTemplateService;
use App\Services\Concrete\Admin\UserFcmTokenService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class BroadcastNotificationController extends Controller
{
    use ResponseAPI;

    protected $broadcast_notification_service;
    protected $business_service;
    protected $notification_template_service;
    protected $user_fcm_token_service;
    protected $firebase_setting_service;

    public function __construct(
        BroadcastNotificationService $broadcast_notification_service,
        BusinessService $business_service,
        NotificationTemplateService $notification_template_service,
        UserFcmTokenService $user_fcm_token_service,
        FirebaseSettingService $firebase_setting_service
    ) {
        $this->middleware('permission:broadcast-notification.view')->only([
            'index', 'getData', 'show', 'getRecipientData', 'usersWithTokens', 'templatesByBusiness',
        ]);
        $this->middleware('permission:broadcast-notification.create')->only(['create', 'store']);
        $this->middleware('permission:broadcast-notification.start')->only(['start']);
        $this->middleware('permission:broadcast-notification.cancel')->only(['cancel']);
        $this->middleware('permission:broadcast-notification.resend')->only(['resendFailed']);
        $this->middleware('permission:broadcast-notification.delete')->only(['destroy']);

        $this->broadcast_notification_service = $broadcast_notification_service;
        $this->business_service = $business_service;
        $this->notification_template_service = $notification_template_service;
        $this->user_fcm_token_service = $user_fcm_token_service;
        $this->firebase_setting_service = $firebase_setting_service;
    }

    public function index()
    {
        $business = $this->business_service->getAll();

        return view('admin.broadcast-notification.index', compact('business'));
    }

    public function getData(Request $request)
    {
        return $this->broadcast_notification_service->getData($request->all());
    }

    public function create(Request $request)
    {
        $business = $this->business_service->getAll();
        $businessId = $request->get('business_id')
            ?? (getRoleName() === RoleNames::SUPERADMIN ? null : Auth::user()->business_id);

        if (!$businessId && getRoleName() === RoleNames::SUPERADMIN && $business->isNotEmpty()) {
            $businessId = $business->first()->business_id;
        }

        $templates = $businessId
            ? $this->notification_template_service->getActiveByBusiness($businessId)
            : collect();
        $usersWithTokens = $businessId
            ? $this->user_fcm_token_service->getUsersWithActiveTokens($businessId)
            : collect();
        $hasFirebase = $businessId
            ? $this->firebase_setting_service->hasValidConfiguration($businessId)
            : false;

        return view('admin.broadcast-notification.create', compact(
            'business',
            'businessId',
            'templates',
            'usersWithTokens',
            'hasFirebase'
        ));
    }

    public function store(Request $request)
    {
        $rules = [
            'title' => 'required|string|max:191',
            'body' => 'required|string',
            'image' => 'nullable|string|max:500',
            'data' => 'nullable|string',
            'template_id' => 'nullable|string',
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'integer',
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

        try {
            $campaign = $this->broadcast_notification_service->createCampaign([
                'business_id' => $request->business_id ?? Auth::user()->business_id,
                'template_id' => $request->template_id,
                'title' => $request->title,
                'body' => $request->body,
                'image' => $request->image,
                'data' => $request->data,
                'user_ids' => $request->user_ids,
            ]);
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }

        return redirect()
            ->route('broadcast-notification.show', $campaign->broadcast_notification_id)
            ->with('success', Message::SAVE);
    }

    public function show($id)
    {
        $campaign = $this->broadcast_notification_service->getById($id);
        if (getRoleName() !== RoleNames::SUPERADMIN
            && $campaign->business_id !== Auth::user()->business_id) {
            abort(403);
        }

        $hasFirebase = $this->firebase_setting_service->hasValidConfiguration($campaign->business_id);

        return view('admin.broadcast-notification.show', compact('campaign', 'hasFirebase'));
    }

    public function getRecipientData(Request $request, $id)
    {
        $campaign = $this->broadcast_notification_service->getById($id);
        if (getRoleName() !== RoleNames::SUPERADMIN
            && $campaign->business_id !== Auth::user()->business_id) {
            return $this->error('Unauthorized', 403);
        }

        return $this->broadcast_notification_service->getRecipientData($id, $request->all());
    }

    public function start($id)
    {
        try {
            $this->broadcast_notification_service->start($id);

            return $this->success('Campaign queued for sending.', []);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function cancel($id)
    {
        try {
            $this->broadcast_notification_service->cancel($id);

            return $this->success('Campaign cancelled.', []);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function resendFailed($id)
    {
        try {
            $this->broadcast_notification_service->resendFailed($id);

            return $this->success('Failed recipients queued for resend.', []);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $this->broadcast_notification_service->delete($id);

            return $this->success(Message::DELETE, []);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function usersWithTokens(Request $request)
    {
        $businessId = $request->get('business_id') ?? Auth::user()->business_id;
        if (getRoleName() !== RoleNames::SUPERADMIN
            && $businessId !== Auth::user()->business_id) {
            return $this->error('Unauthorized', 403);
        }

        return $this->success('OK', $this->user_fcm_token_service->getUsersWithActiveTokens($businessId));
    }

    public function templatesByBusiness(Request $request)
    {
        $businessId = $request->get('business_id') ?? Auth::user()->business_id;
        if (getRoleName() !== RoleNames::SUPERADMIN
            && $businessId !== Auth::user()->business_id) {
            return $this->error('Unauthorized', 403);
        }

        return $this->success(
            'OK',
            $this->notification_template_service->getActiveByBusiness($businessId)
        );
    }
}
