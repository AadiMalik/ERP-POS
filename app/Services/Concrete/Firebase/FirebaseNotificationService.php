<?php

namespace App\Services\Concrete\Firebase;

use App\Models\FirebaseSetting;
use App\Models\UserFcmToken;
use App\Services\Concrete\Admin\SystemFeatureFlagService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Reusable FCM HTTP v1 sender for broadcast and future transactional pushes.
 * Resolves credentials per business_id — never uses a global Firebase config.
 */
class FirebaseNotificationService
{
    /** FCM error codes that mean the registration token must not be reused. */
    protected const PERMANENT_TOKEN_ERRORS = [
        'UNREGISTERED',
        'NOT_FOUND',
        'INVALID_ARGUMENT',
        'SENDER_ID_MISMATCH',
    ];

    /**
     * Send a single FCM message to one device token.
     *
     * @param  array<string, mixed>  $data  Custom data payload (values cast to string)
     * @return array{success: bool, response: mixed, error: ?string, permanent_token_error: bool}
     */
    public function sendToToken(
        string $businessId,
        string $fcmToken,
        string $title,
        string $body,
        ?string $image = null,
        array $data = []
    ): array {
        if (!app(SystemFeatureFlagService::class)->isEnabled('push_notifications')) {
            return [
                'success' => false,
                'response' => null,
                'error' => 'Push notifications are disabled platform-wide.',
                'permanent_token_error' => false,
            ];
        }

        $setting = $this->getActiveSetting($businessId);

        if (!$setting) {
            return [
                'success' => false,
                'response' => null,
                'error' => 'Firebase configuration is not configured for this business. Please configure Firebase before starting the notification.',
                'permanent_token_error' => false,
            ];
        }

        try {
            $accessToken = $this->getAccessToken($setting);
        } catch (\Throwable $e) {
            Log::error('Firebase OAuth token failed', [
                'business_id' => $businessId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'response' => null,
                'error' => 'Invalid Firebase credentials: ' . $e->getMessage(),
                'permanent_token_error' => false,
            ];
        }

        $message = [
            'token' => $fcmToken,
            'notification' => array_filter([
                'title' => $title,
                'body' => $body,
                'image' => $image ?: null,
            ]),
        ];

        if (!empty($data)) {
            $message['data'] = $this->stringifyData($data);
        }

        $url = 'https://fcm.googleapis.com/v1/projects/' . $setting->project_id . '/messages:send';

        try {
            $response = Http::withToken($accessToken)
                ->timeout(30)
                ->acceptJson()
                ->post($url, ['message' => $message]);
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'response' => null,
                'error' => 'Network/Firebase request failed: ' . $e->getMessage(),
                'permanent_token_error' => false,
            ];
        }

        $bodyJson = $response->json();
        $status = $response->status();

        if ($response->successful()) {
            return [
                'success' => true,
                'response' => $bodyJson,
                'error' => null,
                'permanent_token_error' => false,
            ];
        }

        $errorCode = data_get($bodyJson, 'error.status')
            ?? data_get($bodyJson, 'error.details.0.errorCode')
            ?? (string) $status;
        $errorMessage = data_get($bodyJson, 'error.message', 'FCM send failed');
        $permanent = $this->isPermanentTokenError($errorCode, (string) $errorMessage);

        return [
            'success' => false,
            'response' => $bodyJson,
            'error' => $errorMessage . ' (' . $errorCode . ')',
            'permanent_token_error' => $permanent,
        ];
    }

    /**
     * Send the same payload to many tokens sequentially (HTTP v1 has no multicast).
     * Caller should pass manageable batches (e.g. 50–100).
     *
     * @param  array<int, string>  $tokens
     * @return array<int, array{token: string, success: bool, response: mixed, error: ?string, permanent_token_error: bool}>
     */
    public function sendToTokens(
        string $businessId,
        array $tokens,
        string $title,
        string $body,
        ?string $image = null,
        array $data = []
    ): array {
        $results = [];
        foreach ($tokens as $token) {
            $result = $this->sendToToken($businessId, $token, $title, $body, $image, $data);
            $results[] = array_merge(['token' => $token], $result);
        }

        return $results;
    }

    public function getActiveSetting(string $businessId): ?FirebaseSetting
    {
        $setting = FirebaseSetting::where('business_id', $businessId)
            ->where('is_active', true)
            ->first();

        if (!$setting || !$setting->isConfigured()) {
            return null;
        }

        return $setting;
    }

    public function hasValidConfiguration(string $businessId): bool
    {
        return $this->getActiveSetting($businessId) !== null;
    }

    /**
     * Mark a stored device token inactive after a permanent FCM rejection.
     */
    public function deactivateToken(?string $userFcmTokenId, ?string $fcmToken = null, ?string $businessId = null): void
    {
        $query = UserFcmToken::query()->where('is_active', true);

        if ($userFcmTokenId) {
            $query->where('user_fcm_token_id', $userFcmTokenId);
        } elseif ($fcmToken && $businessId) {
            $query->where('business_id', $businessId)->where('fcm_token', $fcmToken);
        } else {
            return;
        }

        $query->update([
            'is_active' => false,
            'date_updated' => now(),
        ]);
    }

    protected function getAccessToken(FirebaseSetting $setting): string
    {
        $cacheKey = 'firebase_oauth_' . $setting->business_id;

        $cached = Cache::get($cacheKey);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $jwt = $this->buildServiceAccountJwt($setting);

        $response = Http::asForm()
            ->timeout(20)
            ->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

        if (!$response->successful()) {
            throw new RuntimeException(
                data_get($response->json(), 'error_description')
                    ?? data_get($response->json(), 'error')
                    ?? 'OAuth token exchange failed'
            );
        }

        $accessToken = (string) $response->json('access_token');
        $expiresIn = (int) ($response->json('expires_in') ?? 3600);

        if ($accessToken === '') {
            throw new RuntimeException('OAuth token exchange returned an empty access_token');
        }

        // Cache slightly under expiry to avoid edge races.
        Cache::put($cacheKey, $accessToken, max(60, $expiresIn - 60));

        return $accessToken;
    }

    protected function buildServiceAccountJwt(FirebaseSetting $setting): string
    {
        $now = time();
        $header = $this->base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $claims = $this->base64UrlEncode(json_encode([
            'iss' => $setting->client_email,
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ]));

        $unsigned = $header . '.' . $claims;
        $privateKey = $this->normalizePrivateKey((string) $setting->private_key);

        $key = openssl_pkey_get_private($privateKey);
        if ($key === false) {
            throw new RuntimeException('Unable to parse Firebase private key');
        }

        $signature = '';
        $ok = openssl_sign($unsigned, $signature, $key, OPENSSL_ALGO_SHA256);
        if (!$ok) {
            throw new RuntimeException('Unable to sign Firebase JWT');
        }

        return $unsigned . '.' . $this->base64UrlEncode($signature);
    }

    protected function normalizePrivateKey(string $privateKey): string
    {
        $privateKey = trim($privateKey);
        // Support keys pasted with literal \n sequences from JSON.
        $privateKey = str_replace(['\\n', "\r\n", "\r"], "\n", $privateKey);

        if (strpos($privateKey, 'BEGIN') === false) {
            $privateKey = "-----BEGIN PRIVATE KEY-----\n"
                . chunk_split($privateKey, 64, "\n")
                . '-----END PRIVATE KEY-----';
        }

        return $privateKey;
    }

    protected function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, string>
     */
    protected function stringifyData(array $data): array
    {
        $out = [];
        foreach ($data as $key => $value) {
            if (is_array($value) || is_object($value)) {
                $out[(string) $key] = json_encode($value);
            } elseif (is_bool($value)) {
                $out[(string) $key] = $value ? '1' : '0';
            } elseif ($value === null) {
                $out[(string) $key] = '';
            } else {
                $out[(string) $key] = (string) $value;
            }
        }

        return $out;
    }

    protected function isPermanentTokenError(string $errorCode, string $message): bool
    {
        $code = strtoupper($errorCode);
        if (in_array($code, self::PERMANENT_TOKEN_ERRORS, true)) {
            // INVALID_ARGUMENT is also used for malformed payloads — only treat as
            // permanent when the message clearly refers to the registration token.
            if ($code === 'INVALID_ARGUMENT') {
                $lower = strtolower($message);
                return str_contains($lower, 'registration token')
                    || str_contains($lower, 'not a valid fcm')
                    || str_contains($lower, 'invalid token')
                    || str_contains($lower, 'requested entity was not found');
            }

            return true;
        }

        $lower = strtolower($message);
        return str_contains($lower, 'requested entity was not found')
            || str_contains($lower, 'unregistered');
    }
}
