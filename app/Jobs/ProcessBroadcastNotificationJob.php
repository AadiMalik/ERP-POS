<?php

namespace App\Jobs;

use App\Enums\BroadcastNotificationStatus;
use App\Enums\BroadcastRecipientStatus;
use App\Models\BroadcastNotification;
use App\Models\BroadcastNotificationRecipient;
use App\Services\Concrete\Firebase\FirebaseNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Processes one batch of pending FCM recipients for a broadcast campaign,
 * then re-dispatches itself until the campaign is finished or cancelled.
 */
class ProcessBroadcastNotificationJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = 15;
    public $timeout = 300;
    public $uniqueFor = 600;

    protected string $broadcast_notification_id;

    /** Recipients claimed per job run. */
    protected int $batchSize = 50;

    public function __construct(string $broadcast_notification_id)
    {
        $this->broadcast_notification_id = $broadcast_notification_id;
    }

    public function uniqueId(): string
    {
        return 'broadcast-fcm-' . $this->broadcast_notification_id;
    }

    public function handle(FirebaseNotificationService $firebaseService): void
    {
        $campaign = BroadcastNotification::where('broadcast_notification_id', $this->broadcast_notification_id)
            ->where('is_deleted', 0)
            ->first();

        if (!$campaign) {
            return;
        }

        if ($campaign->status === BroadcastNotificationStatus::CANCELLED) {
            return;
        }

        if (!in_array($campaign->status, [
            BroadcastNotificationStatus::QUEUED,
            BroadcastNotificationStatus::PROCESSING,
        ], true)) {
            return;
        }

        if (!$firebaseService->hasValidConfiguration($campaign->business_id)) {
            $campaign->status = BroadcastNotificationStatus::FAILED;
            $campaign->date_updated = now();
            $campaign->completed_at = now();
            $campaign->save();
            Log::error('Broadcast campaign failed: missing Firebase configuration', [
                'broadcast_notification_id' => $campaign->broadcast_notification_id,
            ]);

            return;
        }

        // Mark processing once we begin work.
        if ($campaign->status === BroadcastNotificationStatus::QUEUED) {
            BroadcastNotification::where('broadcast_notification_id', $campaign->broadcast_notification_id)
                ->where('status', BroadcastNotificationStatus::QUEUED)
                ->update([
                    'status' => BroadcastNotificationStatus::PROCESSING,
                    'date_updated' => now(),
                ]);
            $campaign->status = BroadcastNotificationStatus::PROCESSING;
        }

        $recipients = $this->claimPendingRecipients($campaign->broadcast_notification_id);

        if ($recipients->isEmpty()) {
            $this->finalizeIfDone($campaign->broadcast_notification_id);

            return;
        }

        foreach ($recipients as $recipient) {
            // Re-check cancellation between sends.
            $freshStatus = BroadcastNotification::where('broadcast_notification_id', $campaign->broadcast_notification_id)
                ->value('status');

            if ($freshStatus === BroadcastNotificationStatus::CANCELLED) {
                BroadcastNotificationRecipient::where('broadcast_notification_recipient_id', $recipient->broadcast_notification_recipient_id)
                    ->where('status', BroadcastRecipientStatus::SENDING)
                    ->update([
                        'status' => BroadcastRecipientStatus::CANCELLED,
                        'date_updated' => now(),
                    ]);

                continue;
            }

            $result = $firebaseService->sendToToken(
                $campaign->business_id,
                $recipient->fcm_token,
                $campaign->title,
                $campaign->body,
                $campaign->image,
                is_array($campaign->data) ? $campaign->data : []
            );

            $this->applyRecipientResult($campaign->broadcast_notification_id, $recipient, $result, $firebaseService);
        }

        $this->recalculateCounters($campaign->broadcast_notification_id);

        $remaining = BroadcastNotificationRecipient::where('broadcast_notification_id', $campaign->broadcast_notification_id)
            ->where('status', BroadcastRecipientStatus::PENDING)
            ->count();

        $fresh = BroadcastNotification::where('broadcast_notification_id', $campaign->broadcast_notification_id)->first();
        if ($fresh && $fresh->status === BroadcastNotificationStatus::CANCELLED) {
            return;
        }

        if ($remaining > 0) {
            // Unique lock releases when this job finishes; dispatch next batch.
            self::dispatch($this->broadcast_notification_id)->delay(now()->addSeconds(2));

            return;
        }

        $this->finalizeIfDone($campaign->broadcast_notification_id);
    }

    protected function claimPendingRecipients(string $campaignId)
    {
        return DB::transaction(function () use ($campaignId) {
            $ids = BroadcastNotificationRecipient::where('broadcast_notification_id', $campaignId)
                ->where('status', BroadcastRecipientStatus::PENDING)
                ->orderBy('date_created')
                ->limit($this->batchSize)
                ->lockForUpdate()
                ->pluck('broadcast_notification_recipient_id');

            if ($ids->isEmpty()) {
                return collect();
            }

            BroadcastNotificationRecipient::whereIn('broadcast_notification_recipient_id', $ids)
                ->update([
                    'status' => BroadcastRecipientStatus::SENDING,
                    'date_updated' => now(),
                ]);

            return BroadcastNotificationRecipient::whereIn('broadcast_notification_recipient_id', $ids)->get();
        });
    }

    protected function applyRecipientResult(
        string $campaignId,
        BroadcastNotificationRecipient $recipient,
        array $result,
        FirebaseNotificationService $firebaseService
    ): void {
        DB::transaction(function () use ($campaignId, $recipient, $result, $firebaseService) {
            $row = BroadcastNotificationRecipient::where('broadcast_notification_recipient_id', $recipient->broadcast_notification_recipient_id)
                ->lockForUpdate()
                ->first();

            if (!$row || $row->status === BroadcastRecipientStatus::CANCELLED) {
                return;
            }

            // Idempotency: skip if another worker already finalized this row.
            if (in_array($row->status, [BroadcastRecipientStatus::SENT, BroadcastRecipientStatus::FAILED], true)) {
                return;
            }

            $row->attempts = (int) $row->attempts + 1;
            $row->response = $result['response'] ?? null;
            $row->date_updated = now();

            if (!empty($result['success'])) {
                $row->status = BroadcastRecipientStatus::SENT;
                $row->sent_at = now();
                $row->error_message = null;
                $row->save();

                BroadcastNotification::where('broadcast_notification_id', $campaignId)
                    ->increment('success_count');

                return;
            }

            $row->status = BroadcastRecipientStatus::FAILED;
            $row->error_message = $result['error'] ?? 'Unknown FCM error';
            $row->save();

            BroadcastNotification::where('broadcast_notification_id', $campaignId)
                ->increment('failed_count');

            if (!empty($result['permanent_token_error'])) {
                $firebaseService->deactivateToken(
                    $row->user_fcm_token_id,
                    $row->fcm_token,
                    BroadcastNotification::where('broadcast_notification_id', $campaignId)->value('business_id')
                );
            }
        });
    }

    protected function recalculateCounters(string $campaignId): void
    {
        $counts = BroadcastNotificationRecipient::where('broadcast_notification_id', $campaignId)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        BroadcastNotification::where('broadcast_notification_id', $campaignId)->update([
            'pending_count' => (int) ($counts[BroadcastRecipientStatus::PENDING] ?? 0)
                + (int) ($counts[BroadcastRecipientStatus::SENDING] ?? 0),
            'success_count' => (int) ($counts[BroadcastRecipientStatus::SENT] ?? 0),
            'failed_count' => (int) ($counts[BroadcastRecipientStatus::FAILED] ?? 0),
            'cancelled_count' => (int) ($counts[BroadcastRecipientStatus::CANCELLED] ?? 0),
            'total_count' => (int) array_sum($counts->toArray()),
            'date_updated' => now(),
        ]);
    }

    protected function finalizeIfDone(string $campaignId): void
    {
        DB::transaction(function () use ($campaignId) {
            $campaign = BroadcastNotification::where('broadcast_notification_id', $campaignId)
                ->lockForUpdate()
                ->first();

            if (!$campaign) {
                return;
            }

            if ($campaign->status === BroadcastNotificationStatus::CANCELLED) {
                $this->recalculateCounters($campaignId);

                return;
            }

            $pendingOrSending = BroadcastNotificationRecipient::where('broadcast_notification_id', $campaignId)
                ->whereIn('status', [BroadcastRecipientStatus::PENDING, BroadcastRecipientStatus::SENDING])
                ->count();

            if ($pendingOrSending > 0) {
                return;
            }

            $this->recalculateCounters($campaignId);

            $campaign->refresh();
            $campaign->status = BroadcastNotificationStatus::COMPLETED;
            $campaign->completed_at = now();
            $campaign->date_updated = now();
            $campaign->save();
        });
    }
}
