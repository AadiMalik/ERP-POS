<?php

namespace Tests\Feature;

use App\Enums\BroadcastNotificationStatus;
use App\Enums\BroadcastRecipientStatus;
use App\Enums\RoleNames;
use App\Jobs\ProcessBroadcastNotificationJob;
use App\Models\BroadcastNotification;
use App\Models\BroadcastNotificationRecipient;
use App\Models\FirebaseSetting;
use App\Models\User;
use App\Models\UserFcmToken;
use App\Services\Concrete\Admin\BroadcastNotificationService;
use App\Services\Concrete\Firebase\FirebaseNotificationService;
use Exception;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use App\Models\Role;
use Tests\TestCase;

/**
 * End-to-end-ish FCM broadcast pipeline tests with Http::fake (no real Google).
 * Uses DatabaseTransactions so the shared MySQL DB is left clean.
 */
class BroadcastNotificationSendTest extends TestCase
{
    use DatabaseTransactions;

    private string $businessId;
    private User $actor;
    private User $recipientUser;

    /** Deterministic RSA key for JWT signing in FirebaseNotificationService. */
    private string $privateKey = <<<'PEM'
-----BEGIN PRIVATE KEY-----
MIIEvgIBADANBgkqhkiG9w0BAQEFAASCBKgwggSkAgEAAoIBAQCwZqGaA/XdB+zj
jrq9y2PC7rHnUj+t7tRwMTPJzrpDUqotn1WbB53qtcfXSuq/v0cRnwCkVqf1hKXh
C4iFtLQzCy5liy4usiQWMpezGLl/BLDONfTAzL0XIOrkfcVR/5ReDG5Ggsz+h2jp
TnF2Bjb0DaamfYpfqH8m3WMiptdnBqpHbCx+nGX51SvvJIixhYHBrmlgMPj6VHQs
0Voii9kwKsfIjPNn0u5N3lleJisIomi5MjGTs6p67PGtBOdq2AUUtNxa9XlVW5n5
+Z4OqQ5+NNWQYX5CPvMvQpQ4GMzOdTfEVwjIYo9NcwqfAHbVuiPup/VA7MYBzMuH
QSWpQID3AgMBAAECggEAHe+s6ShW50XbC9tDEcacjY5Ynrs0l136q3eOJGdy8M3s
l4V8z6yBPj3PRlgucpARjYoAX+Nc6auzQvfkGVzLYAY7v3vZQI921FWNrRU7FbVE
FBrQCJA2XuB1PIWjoQDQuw09AbZc960fPsjTNIZleRGAAK05eHZt7biQhGbwE0mj
98TRUVoFEpSsnMfJakD/0rFHK8vkOhBFIq21GL3fa43xOgCxyH5L2EXcqJUZxqah
pdSw9eopP6avbmOMD34RuW4QhUje2xZ9mbfWHVIb9Kk9Zc9ha41dtxJaEWYDhxFi
mcd/C9jGF2JlJ64jxNHmaOEru3GsMOihtkDK58kCsQKBgQDkVYFulB+hwIn1HHbY
T2248kYMLQz7r1XXyvdMkgFG7wq0znLcJWSwPdsxADTlPdTQoJRRwZJsEdC8XFkI
7f6L3mo+d3+kx+ya4CiqtkkzQHm6nAqSx8q3R+AW1voaKI/0pQW1rEBDszBa9mav
WLG113jTKq3bpyc1YrxNlUpcbQKBgQDFxkH/vfDbUcpJ/GxE2EFClF2YVHIEnRA2
8g9GRO4AwFWYP8zw+idu2K9mFuqLFJZv/TfXwDJLw9iyOqToULL11wzWzXg4ioxl
naCGlYy2BvfHJZQ1wdYHafvxn4qVEZifKlFaB76hl5RLKacYlG0gRg6qGxfXGR7I
NU9e8mdscwKBgQCCX4Ajz1ewV+ttlO8W22Ne8pakZSTAoIB3UmCZy9QG2H5YdniG
0qMHLop1FBUfv4pABTAq7kfYhOOWaXQ88QcifcBUIo8zWyPx2oPd1W8+YFYhAu/W
l1VcCSIeaGktfnOT0JXOAag//5Rgm8hN3mq51WobyIa0oGB2zwWNlux6kQKBgE8Z
Kt5AtirRPGl4xkiGgRtCwWgiJfPIaWrARvGgsdulENaydaHPOqQvj37yHV4AnuYt
TP9CBBufOXSW1cuAMwL1vlHOnY1nhB1D/Ka2+y71/HUuh2c0nggbEEOjvkY+Yl8O
CrlKLajtOccR4p2HB1oICudnrJI/nrsN7y+XHVwXAoGBAMl82sXjTklZAIr7JmUy
rG4Tn142hbo5j7T1ZOn26SfZNPDHUgFguwLCZLbVjHWYGM6w2Si0xCA9EJtTDldd
eqwG9SeZTsDzTgg+C1YLjHeNgjvKkdo7lWzxBpFOHu2SRcpbXwBQMqgkxsTr/sq5
cyreOell4h8/wS1F+ct0exS6
-----END PRIVATE KEY-----
PEM;

    protected function setUp(): void
    {
        parent::setUp();

        $this->businessId = generateUuid();
        $this->actor = $this->makeUser('fcm-actor-' . uniqid() . '@test.local');
        $this->recipientUser = $this->makeUser('fcm-recv-' . uniqid() . '@test.local');

        $role = Role::where('name', RoleNames::SUPERADMIN)->whereNull('business_id')->first();
        if ($role) {
            $this->actor->assignRole($role);
        }

        $this->actingAs($this->actor);
        Cache::flush();
    }

    public function test_start_is_blocked_when_firebase_not_configured(): void
    {
        $token = $this->makeActiveToken('token-no-firebase');
        $campaign = $this->makeDraftCampaign([$token]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Firebase configuration is not configured for this business');

        app(BroadcastNotificationService::class)->start($campaign->broadcast_notification_id);
    }

    public function test_successful_fcm_delivery_marks_recipient_sent(): void
    {
        $this->configureFirebase();
        $this->fakeFcmSuccess();

        $token = $this->makeActiveToken('token-success-1');
        $campaign = $this->makeDraftCampaign([$token]);

        app(BroadcastNotificationService::class)->start($campaign->broadcast_notification_id);

        $campaign->refresh();
        $recipient = $campaign->recipients()->first();

        $this->assertSame(BroadcastNotificationStatus::COMPLETED, $campaign->status);
        $this->assertSame(1, (int) $campaign->success_count);
        $this->assertSame(0, (int) $campaign->failed_count);
        $this->assertSame(0, (int) $campaign->pending_count);
        $this->assertSame(BroadcastRecipientStatus::SENT, $recipient->status);
        $this->assertNotNull($recipient->sent_at);
        $this->assertNull($recipient->error_message);
    }

    public function test_invalid_fcm_token_fails_and_deactivates_token(): void
    {
        $this->configureFirebase();
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'fake-access',
                'expires_in' => 3600,
            ], 200),
            'fcm.googleapis.com/*' => Http::response([
                'error' => [
                    'code' => 404,
                    'message' => 'Requested entity was not found.',
                    'status' => 'NOT_FOUND',
                    'details' => [
                        ['errorCode' => 'UNREGISTERED'],
                    ],
                ],
            ], 404),
        ]);

        $token = $this->makeActiveToken('token-dead');
        $campaign = $this->makeDraftCampaign([$token]);
        $campaign->update(['status' => BroadcastNotificationStatus::QUEUED, 'started_at' => now()]);

        (new ProcessBroadcastNotificationJob($campaign->broadcast_notification_id))
            ->handle(app(FirebaseNotificationService::class));

        $token->refresh();
        $recipient = $campaign->recipients()->first();
        $campaign->refresh();

        $this->assertFalse((bool) $token->is_active);
        $this->assertSame(BroadcastRecipientStatus::FAILED, $recipient->status);
        $this->assertNotEmpty($recipient->error_message);
        $this->assertSame(1, (int) $campaign->failed_count);
        $this->assertSame(BroadcastNotificationStatus::COMPLETED, $campaign->status);
    }

    public function test_temporary_fcm_error_does_not_deactivate_token(): void
    {
        $this->configureFirebase();
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'fake-access',
                'expires_in' => 3600,
            ], 200),
            'fcm.googleapis.com/*' => Http::response([
                'error' => [
                    'code' => 503,
                    'message' => 'The service is currently unavailable.',
                    'status' => 'UNAVAILABLE',
                ],
            ], 503),
        ]);

        $token = $this->makeActiveToken('token-temp-fail');
        $campaign = $this->makeDraftCampaign([$token]);
        $campaign->update(['status' => BroadcastNotificationStatus::QUEUED, 'started_at' => now()]);

        (new ProcessBroadcastNotificationJob($campaign->broadcast_notification_id))
            ->handle(app(FirebaseNotificationService::class));

        $token->refresh();
        $recipient = $campaign->recipients()->first();

        $this->assertTrue((bool) $token->is_active);
        $this->assertSame(BroadcastRecipientStatus::FAILED, $recipient->status);
        $this->assertStringContainsString('UNAVAILABLE', (string) $recipient->error_message);
    }

    public function test_cancel_marks_pending_cancelled_and_keeps_sent(): void
    {
        $this->configureFirebase();
        $this->fakeFcmSuccess();

        $tokenSent = $this->makeActiveToken('token-cancel-sent');
        $tokenPending = $this->makeActiveToken('token-cancel-pending');
        $campaign = $this->makeDraftCampaign([$tokenSent, $tokenPending]);

        // Pretend one already sent while campaign is processing.
        $sentRow = $campaign->recipients()->where('fcm_token', 'token-cancel-sent')->first();
        $sentRow->update([
            'status' => BroadcastRecipientStatus::SENT,
            'sent_at' => now(),
            'attempts' => 1,
        ]);
        $campaign->update([
            'status' => BroadcastNotificationStatus::PROCESSING,
            'started_at' => now(),
            'success_count' => 1,
            'pending_count' => 1,
        ]);

        app(BroadcastNotificationService::class)->cancel($campaign->broadcast_notification_id);

        $campaign->refresh();
        $this->assertSame(BroadcastNotificationStatus::CANCELLED, $campaign->status);

        $this->assertSame(
            BroadcastRecipientStatus::SENT,
            $campaign->recipients()->where('fcm_token', 'token-cancel-sent')->value('status')
        );
        $this->assertSame(
            BroadcastRecipientStatus::CANCELLED,
            $campaign->recipients()->where('fcm_token', 'token-cancel-pending')->value('status')
        );
        $this->assertGreaterThanOrEqual(1, (int) $campaign->cancelled_count);
    }

    public function test_resend_failed_only_resets_active_token_failures(): void
    {
        $this->configureFirebase();
        $this->fakeFcmSuccess();

        $activeToken = $this->makeActiveToken('token-resend-active');
        $deadToken = $this->makeActiveToken('token-resend-dead');
        $deadToken->update(['is_active' => false]);

        $campaign = $this->makeDraftCampaign([$activeToken, $deadToken]);

        foreach ($campaign->recipients as $row) {
            $row->update([
                'status' => BroadcastRecipientStatus::FAILED,
                'error_message' => 'previous fail',
                'attempts' => 1,
            ]);
        }

        $campaign->update([
            'status' => BroadcastNotificationStatus::COMPLETED,
            'failed_count' => 2,
            'pending_count' => 0,
            'success_count' => 0,
            'completed_at' => now(),
        ]);

        // With QUEUE=sync, resendFailed will reset then dispatch job which sends.
        app(BroadcastNotificationService::class)->resendFailed($campaign->broadcast_notification_id);

        $campaign->refresh();
        $activeRecipient = $campaign->recipients()->where('fcm_token', 'token-resend-active')->first();
        $deadRecipient = $campaign->recipients()->where('fcm_token', 'token-resend-dead')->first();

        $this->assertSame(BroadcastRecipientStatus::SENT, $activeRecipient->status);
        $this->assertSame(BroadcastRecipientStatus::FAILED, $deadRecipient->status);
        $this->assertSame(1, (int) $campaign->success_count);
        $this->assertSame(BroadcastNotificationStatus::COMPLETED, $campaign->status);
    }

    public function test_partial_success_and_failure_in_same_campaign(): void
    {
        $this->configureFirebase();

        $okToken = 'token-partial-ok';
        $badToken = 'token-partial-bad';

        Http::fake(function ($request) use ($okToken, $badToken) {
            if (str_contains($request->url(), 'oauth2.googleapis.com/token')) {
                return Http::response(['access_token' => 'fake-access', 'expires_in' => 3600], 200);
            }

            $payload = $request->data();
            $token = $payload['message']['token'] ?? '';

            if ($token === $okToken) {
                return Http::response(['name' => 'projects/test/messages/1'], 200);
            }

            return Http::response([
                'error' => [
                    'code' => 404,
                    'message' => 'Requested entity was not found.',
                    'status' => 'NOT_FOUND',
                ],
            ], 404);
        });

        $t1 = $this->makeActiveToken($okToken);
        $t2 = $this->makeActiveToken($badToken);
        $campaign = $this->makeDraftCampaign([$t1, $t2]);
        $campaign->update(['status' => BroadcastNotificationStatus::QUEUED, 'started_at' => now()]);

        (new ProcessBroadcastNotificationJob($campaign->broadcast_notification_id))
            ->handle(app(FirebaseNotificationService::class));

        $campaign->refresh();
        $this->assertSame(1, (int) $campaign->success_count);
        $this->assertSame(1, (int) $campaign->failed_count);
        $this->assertSame(BroadcastNotificationStatus::COMPLETED, $campaign->status);
        $this->assertNotEquals(BroadcastNotificationStatus::FAILED, $campaign->status);
    }

    protected function configureFirebase(): void
    {
        FirebaseSetting::create([
            'firebase_setting_id' => generateUuid(),
            'business_id' => $this->businessId,
            'project_id' => 'fcm-test-project',
            'client_email' => 'firebase-adminsdk@fcm-test-project.iam.gserviceaccount.com',
            'private_key' => $this->privateKey,
            'is_active' => true,
            'date_created' => now(),
        ]);
    }

    protected function fakeFcmSuccess(): void
    {
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'fake-access-token',
                'expires_in' => 3600,
            ], 200),
            'fcm.googleapis.com/*' => Http::response([
                'name' => 'projects/fcm-test-project/messages/0:test',
            ], 200),
        ]);
    }

    protected function makeUser(string $email): User
    {
        return User::create([
            'name' => 'FCM Test User',
            'email' => $email,
            'password' => bcrypt('password'),
            'business_id' => $this->businessId,
            'status' => 'active',
            'is_deleted' => 0,
            'date_created' => now(),
        ]);
    }

    protected function makeActiveToken(string $fcmToken): UserFcmToken
    {
        return UserFcmToken::create([
            'user_fcm_token_id' => generateUuid(),
            'business_id' => $this->businessId,
            'user_id' => $this->recipientUser->id,
            'fcm_token' => $fcmToken,
            'device_type' => 'android',
            'is_active' => true,
            'date_created' => now(),
        ]);
    }

    /**
     * @param  array<int, UserFcmToken>  $tokens
     */
    protected function makeDraftCampaign(array $tokens): BroadcastNotification
    {
        $campaign = BroadcastNotification::create([
            'broadcast_notification_id' => generateUuid(),
            'business_id' => $this->businessId,
            'title' => 'Test Broadcast',
            'body' => 'Hello from FCM test',
            'status' => BroadcastNotificationStatus::DRAFT,
            'total_count' => count($tokens),
            'pending_count' => count($tokens),
            'success_count' => 0,
            'failed_count' => 0,
            'cancelled_count' => 0,
            'created_by' => $this->actor->id,
            'date_created' => now(),
        ]);

        foreach ($tokens as $token) {
            BroadcastNotificationRecipient::create([
                'broadcast_notification_recipient_id' => generateUuid(),
                'broadcast_notification_id' => $campaign->broadcast_notification_id,
                'user_id' => $token->user_id,
                'user_fcm_token_id' => $token->user_fcm_token_id,
                'fcm_token' => $token->fcm_token,
                'status' => BroadcastRecipientStatus::PENDING,
                'attempts' => 0,
                'date_created' => now(),
            ]);
        }

        return $campaign->fresh(['recipients']);
    }
}
