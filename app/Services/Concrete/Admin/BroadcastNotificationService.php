<?php

namespace App\Services\Concrete\Admin;

use App\Enums\BroadcastNotificationStatus;
use App\Enums\BroadcastRecipientStatus;
use App\Enums\Filter;
use App\Enums\RoleNames;
use App\Jobs\ProcessBroadcastNotificationJob;
use App\Models\BroadcastNotification;
use App\Models\BroadcastNotificationRecipient;
use App\Models\NotificationTemplate;
use App\Repository\Repository;
use App\Services\Concrete\Firebase\FirebaseNotificationService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

class BroadcastNotificationService
{
    protected $model;
    protected $firebaseSettingService;
    protected $userFcmTokenService;
    protected $firebaseNotificationService;

    public function __construct(
        FirebaseSettingService $firebaseSettingService,
        UserFcmTokenService $userFcmTokenService,
        FirebaseNotificationService $firebaseNotificationService
    ) {
        $this->model = new Repository(new BroadcastNotification());
        $this->firebaseSettingService = $firebaseSettingService;
        $this->userFcmTokenService = $userFcmTokenService;
        $this->firebaseNotificationService = $firebaseNotificationService;
    }

    public function getData($obj)
    {
        $wh = [['is_deleted', 0]];
        $orderBy = Filter::ORDERBY;

        if (!empty($obj['orderBy'])) {
            $orderBy = $obj['orderBy'];
        }
        if (!empty($obj['business_id'])) {
            $wh[] = ['business_id', $obj['business_id']];
        }
        if (!empty($obj['status'])) {
            $wh[] = ['status', $obj['status']];
        }
        if (!empty($obj['start_date'])) {
            $wh[] = ['date_created', '>=', Carbon::parse($obj['start_date'])->startOfDay()];
        }
        if (!empty($obj['end_date'])) {
            $wh[] = ['date_created', '<=', Carbon::parse($obj['end_date'])->endOfDay()];
        }

        $datatable = $this->model->getModel()::where($wh)
            ->with(['business', 'template', 'createdBy'])
            ->orderBy('date_created', $orderBy);

        $datatable = applyRoleScope($datatable, [RoleNames::SUPERADMIN, RoleNames::BUSINESSADMIN]);

        return DataTables::of($datatable)
            ->addColumn('business', fn ($item) => $item->business?->name ?? '-')
            ->addColumn('template_name', fn ($item) => $item->template?->name ?? '-')
            ->addColumn('created_by_name', fn ($item) => $item->createdBy?->name ?? '-')
            ->addColumn('status_badge', function ($item) {
                $labels = BroadcastNotificationStatus::labels();
                $label = $labels[$item->status] ?? $item->status;
                $class = match ($item->status) {
                    BroadcastNotificationStatus::COMPLETED => 'success',
                    BroadcastNotificationStatus::PROCESSING, BroadcastNotificationStatus::QUEUED => 'primary',
                    BroadcastNotificationStatus::CANCELLED => 'secondary',
                    BroadcastNotificationStatus::FAILED => 'danger',
                    default => 'warning',
                };

                return '<span class="badge bg-' . $class . '">' . e($label) . '</span>';
            })
            ->addColumn('action', function ($item) {
                $id = $item->broadcast_notification_id;
                $html = "<a class='btn btn-icon btn-outline-info mr-1' href='" . route('broadcast-notification.show', $id) . "' title='View'><i class='fa fa-eye'></i></a>";

                if (in_array($item->status, BroadcastNotificationStatus::startable(), true)) {
                    $html .= "<a class='btn btn-icon btn-outline-success mr-1 startBroadcast' data-id='{$id}' title='Start'><i class='fa fa-play'></i></a>";
                }
                if (in_array($item->status, BroadcastNotificationStatus::cancellable(), true)
                    && $item->status !== BroadcastNotificationStatus::DRAFT) {
                    $html .= "<a class='btn btn-icon btn-outline-warning mr-1 cancelBroadcast' data-id='{$id}' title='Cancel'><i class='fa fa-stop'></i></a>";
                }
                if ($item->failed_count > 0 && in_array($item->status, [
                    BroadcastNotificationStatus::COMPLETED,
                    BroadcastNotificationStatus::FAILED,
                    BroadcastNotificationStatus::CANCELLED,
                ], true)) {
                    $html .= "<a class='btn btn-icon btn-outline-primary mr-1 resendBroadcast' data-id='{$id}' title='Resend Failed'><i class='fa fa-redo'></i></a>";
                }
                if ($item->status === BroadcastNotificationStatus::DRAFT) {
                    $html .= "<a class='btn btn-icon btn-outline-danger deleteBroadcast' data-id='{$id}' title='Delete'><i class='fa fa-trash'></i></a>";
                }

                return $html;
            })
            ->rawColumns(['status_badge', 'action'])
            ->make(true);
    }

    public function createCampaign(array $obj): BroadcastNotification
    {
        $businessId = $obj['business_id'];
        $userIds = array_values(array_unique(array_map('intval', $obj['user_ids'] ?? [])));

        if (empty($userIds)) {
            throw new Exception('Please select at least one customer/user with an active FCM token.');
        }

        $tokens = $this->userFcmTokenService->getActiveTokensForUsers($businessId, $userIds);
        if ($tokens->isEmpty()) {
            throw new Exception('None of the selected users have an active FCM token.');
        }

        $title = $obj['title'];
        $body = $obj['body'];
        $image = $obj['image'] ?? null;
        $data = $obj['data'] ?? null;
        $templateId = $obj['template_id'] ?? null;

        if ($templateId) {
            $template = NotificationTemplate::notDeleted()
                ->where('notification_template_id', $templateId)
                ->where('business_id', $businessId)
                ->first();

            if (!$template) {
                throw new Exception('Selected notification template was not found for this business.');
            }

            // Snapshot from template when fields were left empty.
            $title = $title ?: $template->title;
            $body = $body ?: $template->body;
            $image = $image ?: $template->image;
            $data = $data !== null && $data !== '' ? $data : $template->data;
        }

        if (is_string($data)) {
            $decoded = json_decode($data, true);
            $data = json_last_error() === JSON_ERROR_NONE ? $decoded : null;
        }

        return DB::transaction(function () use ($businessId, $templateId, $title, $body, $image, $data, $tokens) {
            $campaign = BroadcastNotification::create([
                'broadcast_notification_id' => generateUuid(),
                'business_id' => $businessId,
                'template_id' => $templateId,
                'title' => $title,
                'body' => $body,
                'image' => $image,
                'data' => $data,
                'status' => BroadcastNotificationStatus::DRAFT,
                'total_count' => $tokens->count(),
                'pending_count' => $tokens->count(),
                'success_count' => 0,
                'failed_count' => 0,
                'cancelled_count' => 0,
                'created_by' => Auth::id(),
                'date_created' => now(),
            ]);

            $rows = [];
            $now = now();
            foreach ($tokens as $token) {
                $rows[] = [
                    'broadcast_notification_recipient_id' => generateUuid(),
                    'broadcast_notification_id' => $campaign->broadcast_notification_id,
                    'user_id' => $token->user_id,
                    'user_fcm_token_id' => $token->user_fcm_token_id,
                    'fcm_token' => $token->fcm_token,
                    'status' => BroadcastRecipientStatus::PENDING,
                    'attempts' => 0,
                    'date_created' => $now,
                ];
            }

            foreach (array_chunk($rows, 500) as $chunk) {
                BroadcastNotificationRecipient::insert($chunk);
            }

            return $campaign;
        });
    }

    public function getById($id): BroadcastNotification
    {
        return $this->model->getModel()::with(['business', 'template', 'createdBy'])
            ->findOrFail($id);
    }

    public function getRecipientData($campaignId, $obj)
    {
        $query = BroadcastNotificationRecipient::where('broadcast_notification_id', $campaignId)
            ->with(['user:id,name,email,phone', 'fcmToken:user_fcm_token_id,device_type,device_id,is_active']);

        if (!empty($obj['status'])) {
            $query->where('status', $obj['status']);
        }

        return DataTables::of($query->orderBy('date_created', 'desc'))
            ->addColumn('user_name', fn ($item) => $item->user?->name ?? '-')
            ->addColumn('user_email', fn ($item) => $item->user?->email ?? '-')
            ->addColumn('device_info', function ($item) {
                $type = $item->fcmToken?->device_type ?: '-';
                $deviceId = $item->fcmToken?->device_id ?: '';
                $active = $item->fcmToken ? ($item->fcmToken->is_active ? 'active' : 'inactive') : 'n/a';

                return e($type) . ($deviceId ? ' / ' . e($deviceId) : '') . ' (' . $active . ')';
            })
            ->addColumn('token_preview', function ($item) {
                $token = (string) $item->fcm_token;
                if (strlen($token) <= 24) {
                    return e($token);
                }

                return e(substr($token, 0, 12) . '…' . substr($token, -8));
            })
            ->addColumn('status_badge', function ($item) {
                $labels = BroadcastRecipientStatus::labels();
                $label = $labels[$item->status] ?? $item->status;
                $class = match ($item->status) {
                    BroadcastRecipientStatus::SENT => 'success',
                    BroadcastRecipientStatus::FAILED => 'danger',
                    BroadcastRecipientStatus::CANCELLED => 'secondary',
                    BroadcastRecipientStatus::SENDING => 'primary',
                    default => 'warning',
                };

                return '<span class="badge bg-' . $class . '">' . e($label) . '</span>';
            })
            ->addColumn('sent_at_fmt', fn ($item) => $item->sent_at?->format('Y-m-d H:i:s') ?? '-')
            ->rawColumns(['status_badge', 'device_info'])
            ->make(true);
    }

    /**
     * Transition draft/failed → queued and dispatch the processing job.
     */
    public function start(string $campaignId): BroadcastNotification
    {
        $campaign = DB::transaction(function () use ($campaignId) {
            $campaign = BroadcastNotification::where('broadcast_notification_id', $campaignId)
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertBusinessAccess($campaign);

            if (!in_array($campaign->status, BroadcastNotificationStatus::startable(), true)) {
                throw new Exception('This campaign cannot be started in its current status.');
            }

            if (!$this->firebaseSettingService->hasValidConfiguration($campaign->business_id)) {
                throw new Exception(
                    'Firebase configuration is not configured for this business. Please configure Firebase before starting the notification.'
                );
            }

            $pending = BroadcastNotificationRecipient::where('broadcast_notification_id', $campaignId)
                ->where('status', BroadcastRecipientStatus::PENDING)
                ->count();

            if ($pending < 1) {
                throw new Exception('There are no pending recipients to send.');
            }

            $campaign->status = BroadcastNotificationStatus::QUEUED;
            $campaign->started_at = $campaign->started_at ?: now();
            $campaign->completed_at = null;
            $campaign->date_updated = now();
            $campaign->updatedby_id = Auth::id();
            $campaign->save();

            return $campaign;
        });

        ProcessBroadcastNotificationJob::dispatch($campaign->broadcast_notification_id);

        return $campaign;
    }

    public function cancel(string $campaignId): BroadcastNotification
    {
        return DB::transaction(function () use ($campaignId) {
            $campaign = BroadcastNotification::where('broadcast_notification_id', $campaignId)
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertBusinessAccess($campaign);

            if (!in_array($campaign->status, BroadcastNotificationStatus::cancellable(), true)) {
                throw new Exception('Only draft, queued, or processing campaigns can be cancelled.');
            }

            $pendingUpdated = BroadcastNotificationRecipient::where('broadcast_notification_id', $campaignId)
                ->whereIn('status', [BroadcastRecipientStatus::PENDING, BroadcastRecipientStatus::SENDING])
                ->update([
                    'status' => BroadcastRecipientStatus::CANCELLED,
                    'date_updated' => now(),
                ]);

            $campaign->cancelled_count = (int) $campaign->cancelled_count + $pendingUpdated;
            $campaign->pending_count = BroadcastNotificationRecipient::where('broadcast_notification_id', $campaignId)
                ->where('status', BroadcastRecipientStatus::PENDING)
                ->count();
            $campaign->status = BroadcastNotificationStatus::CANCELLED;
            $campaign->cancelled_at = now();
            $campaign->completed_at = now();
            $campaign->date_updated = now();
            $campaign->updatedby_id = Auth::id();
            $campaign->save();

            return $campaign;
        });
    }

    /**
     * Reset eligible failed recipients to pending (token still active) and re-queue.
     */
    public function resendFailed(string $campaignId): BroadcastNotification
    {
        $campaign = DB::transaction(function () use ($campaignId) {
            $campaign = BroadcastNotification::where('broadcast_notification_id', $campaignId)
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertBusinessAccess($campaign);

            if (in_array($campaign->status, [
                BroadcastNotificationStatus::QUEUED,
                BroadcastNotificationStatus::PROCESSING,
            ], true)) {
                throw new Exception('Cannot resend while the campaign is still processing.');
            }

            if (!$this->firebaseSettingService->hasValidConfiguration($campaign->business_id)) {
                throw new Exception(
                    'Firebase configuration is not configured for this business. Please configure Firebase before starting the notification.'
                );
            }

            $failed = BroadcastNotificationRecipient::where('broadcast_notification_id', $campaignId)
                ->where('status', BroadcastRecipientStatus::FAILED)
                ->get();

            if ($failed->isEmpty()) {
                throw new Exception('There are no failed recipients to resend.');
            }

            $resetCount = 0;
            foreach ($failed as $recipient) {
                $tokenStillActive = false;
                if ($recipient->user_fcm_token_id) {
                    $tokenStillActive = \App\Models\UserFcmToken::where('user_fcm_token_id', $recipient->user_fcm_token_id)
                        ->where('is_active', true)
                        ->exists();
                }

                if (!$tokenStillActive) {
                    continue;
                }

                $recipient->status = BroadcastRecipientStatus::PENDING;
                $recipient->error_message = null;
                $recipient->response = null;
                $recipient->sent_at = null;
                $recipient->date_updated = now();
                $recipient->save();
                $resetCount++;
            }

            if ($resetCount < 1) {
                throw new Exception('No failed recipients have an active FCM token available for resend.');
            }

            $campaign->pending_count = BroadcastNotificationRecipient::where('broadcast_notification_id', $campaignId)
                ->where('status', BroadcastRecipientStatus::PENDING)
                ->count();
            $campaign->failed_count = BroadcastNotificationRecipient::where('broadcast_notification_id', $campaignId)
                ->where('status', BroadcastRecipientStatus::FAILED)
                ->count();
            $campaign->status = BroadcastNotificationStatus::QUEUED;
            $campaign->completed_at = null;
            $campaign->cancelled_at = null;
            $campaign->started_at = $campaign->started_at ?: now();
            $campaign->date_updated = now();
            $campaign->updatedby_id = Auth::id();
            $campaign->save();

            return $campaign;
        });

        ProcessBroadcastNotificationJob::dispatch($campaign->broadcast_notification_id);

        return $campaign;
    }

    public function delete(string $campaignId): bool
    {
        return DB::transaction(function () use ($campaignId) {
            $campaign = BroadcastNotification::where('broadcast_notification_id', $campaignId)
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertBusinessAccess($campaign);

            if ($campaign->status !== BroadcastNotificationStatus::DRAFT) {
                throw new Exception('Only draft campaigns can be deleted.');
            }

            $campaign->is_deleted = 1;
            $campaign->deletedby_id = Auth::id();
            $campaign->date_deleted = now();
            $campaign->save();

            return true;
        });
    }

    protected function assertBusinessAccess(BroadcastNotification $campaign): void
    {
        if (getRoleName() === RoleNames::SUPERADMIN) {
            return;
        }

        if ($campaign->business_id !== Auth::user()->business_id) {
            throw new Exception('You do not have access to this campaign.');
        }
    }
}
