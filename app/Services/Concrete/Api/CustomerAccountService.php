<?php

namespace App\Services\Concrete\Api;

use App\Enums\RoleNames;
use App\Enums\Status;
use App\Models\CustomerAddress;
use App\Models\CustomerProfile;
use App\Models\LoginSecuritySetting;
use App\Models\User;
use App\Services\Concrete\Admin\CustomerService;
use App\Services\Concrete\Admin\LoyaltyPointService;
use Exception;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rules\Password;

/**
 * Storefront customer account helpers - profile, addresses, password change,
 * and ensuring a CustomerProfile exists for the active business_id.
 */
class CustomerAccountService
{
    protected $customer_service;
    protected $loyalty_point_service;

    public function __construct(CustomerService $customer_service, LoyaltyPointService $loyalty_point_service)
    {
        $this->customer_service = $customer_service;
        $this->loyalty_point_service = $loyalty_point_service;
    }

    public static function passwordRules(): array
    {
        return [
            'required',
            'string',
            'confirmed',
            Password::min(8)->mixedCase()->numbers(),
        ];
    }

    /**
     * Each business plugs in its own Google/Facebook/reCAPTCHA project via
     * Settings > Social Login & Security - there is no platform-wide
     * fallback, so a business that hasn't configured/enabled a provider
     * simply can't use it.
     */
    public function getLoginSecuritySetting(string $businessId): ?LoginSecuritySetting
    {
        return LoginSecuritySetting::where('business_id', $businessId)->first();
    }

    /**
     * Verify a Google Sign-In id_token and return its decoded claims
     * (email, name, sub, ...). Uses Google's tokeninfo endpoint, which
     * validates signature/expiry for us - no JWKS handling needed.
     * ponytail: tokeninfo is rate-limited by Google; switch to local JWKS
     * verification (e.g. firebase/php-jwt) if login volume ever needs it.
     */
    public function verifyGoogleIdToken(string $idToken, string $businessId): array
    {
        $setting = $this->getLoginSecuritySetting($businessId);
        if (!$setting || !$setting->is_google_enabled) {
            throw new Exception('Google login is not enabled for this business.');
        }

        $response = Http::get('https://oauth2.googleapis.com/tokeninfo', ['id_token' => $idToken]);

        if (!$response->ok()) {
            throw new Exception('Invalid Google token.');
        }

        $payload = $response->json();
        $allowed_client_ids = array_filter([
            $setting->google_client_id,
            $setting->google_android_client_id,
            $setting->google_ios_client_id,
        ]);

        if (empty($payload['email']) || !in_array($payload['aud'] ?? null, $allowed_client_ids, true)) {
            throw new Exception('Invalid Google token.');
        }

        return $payload;
    }

    /**
     * Verify a Facebook access token by asking the Graph API who it belongs
     * to - a failed/expired/forged token comes back as an error payload.
     */
    public function verifyFacebookAccessToken(string $accessToken, string $businessId): array
    {
        $setting = $this->getLoginSecuritySetting($businessId);
        if (!$setting || !$setting->is_facebook_enabled) {
            throw new Exception('Facebook login is not enabled for this business.');
        }

        $response = Http::get('https://graph.facebook.com/me', [
            'fields' => 'id,name,email',
            'access_token' => $accessToken,
        ]);

        $payload = $response->json();

        if (!$response->ok() || !empty($payload['error']) || empty($payload['email'])) {
            throw new Exception('Invalid Facebook token.');
        }

        return $payload;
    }

    /**
     * Find the user by email (linking the provider id if missing) or create
     * a new passwordless customer account, mirroring the OTP onboarding
     * User::create() shape in AuthController::verifyOtp().
     */
    public function findOrCreateSocialUser(string $provider, string $providerId, string $email, ?string $name): User
    {
        $column = $provider === 'google' ? 'google_id' : 'facebook_id';
        $email = strtolower(trim($email));

        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();

        if ($user) {
            if (empty($user->{$column})) {
                $user->update([$column => $providerId]);
            }

            return $user;
        }

        $user = User::create([
            'name' => $name ?: explode('@', $email)[0],
            'email' => $email,
            'password' => null,
            'status' => 'active',
            'email_verified_at' => now(),
            $column => $providerId,
        ]);
        $user->assignRole(RoleNames::USER);

        return $user;
    }

    /**
     * Ensure the user has an active CustomerProfile for this business.
     * Creates one when missing so first storefront login/register works.
     */
    public function ensureProfile(User $user, string $business_id, ?string $branch_id = null): CustomerProfile
    {
        $profile = $this->customer_service->getProfile($user->id, $business_id);

        if ($profile) {
            if ($profile->status !== Status::ACTIVE || $profile->is_deleted) {
                throw new Exception('This account is disabled for this store. Please contact support.');
            }

            // Keep COA in sync with Accounting Settings on every login/profile ensure.
            $account_id = $this->customer_service->resolveDefaultCustomerAccountId($business_id);
            if ($profile->account_id !== $account_id) {
                $profile->update([
                    'account_id'   => $account_id,
                    'date_updated' => now(),
                ]);
                $profile->refresh();
            }

            return $profile;
        }

        return $this->customer_service->upsertProfile($user->id, $business_id, [
            'branch_id' => $branch_id,
            'contact_person' => $user->name,
        ]);
    }

    /**
     * True when this email already has a customer profile for the business.
     */
    public function emailExistsForBusiness(string $email, string $business_id): bool
    {
        $user = User::whereRaw('LOWER(email) = ?', [strtolower(trim($email))])->first();
        if (!$user) {
            return false;
        }

        return CustomerProfile::where('user_id', $user->id)
            ->where('business_id', $business_id)
            ->where('is_deleted', 0)
            ->exists();
    }

    /**
     * Phone uniqueness across users (non-empty phones only).
     */
    public function phoneTaken(?string $phone, ?int $except_user_id = null): bool
    {
        $normalized = $this->normalizePhone($phone);
        if ($normalized === '') {
            return false;
        }

        $query = User::whereNotNull('phone')
            ->where('phone', '!=', '')
            ->where(function ($q) use ($normalized, $phone) {
                $q->where('phone', $phone)
                    ->orWhereRaw("REPLACE(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '(', ''), ')', '') = ?", [$normalized]);
            });

        if ($except_user_id) {
            $query->where('id', '!=', $except_user_id);
        }

        return $query->exists();
    }

    public function normalizePhone(?string $phone): string
    {
        return preg_replace('/[^0-9]/', '', (string) $phone) ?? '';
    }

    public function getProfilePayload(User $user, string $business_id): array
    {
        $this->ensureProfile($user, $business_id);

        $addresses = CustomerAddress::where('user_id', $user->id)
            ->where('business_id', $business_id)
            ->where('is_deleted', 0)
            ->orderByDesc('is_default')
            ->orderBy('date_created')
            ->get()
            ->map(fn ($a) => $this->mapAddress($a))
            ->values()
            ->all();

        $loyalty_enabled = $this->loyalty_point_service->isEnabled($business_id);
        $loyalty_balances = $loyalty_enabled
            ? $this->loyalty_point_service->getBalances($business_id, $user->id)
            : ['available' => 0, 'reserved' => 0];

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'status' => $user->status,
            'createdAt' => optional($user->created_at)->toIso8601String(),
            'addresses' => $addresses,
            'loyalty' => [
                'enabled' => $loyalty_enabled,
                'available' => (float) $loyalty_balances['available'],
                'reserved' => (float) $loyalty_balances['reserved'],
            ],
        ];
    }

    public function updateProfile(User $user, string $business_id, array $data): array
    {
        $this->ensureProfile($user, $business_id);

        if (array_key_exists('phone', $data) && $this->phoneTaken($data['phone'], $user->id)) {
            throw new Exception('This phone number is already registered.');
        }

        $user->update([
            'name' => $data['name'] ?? $user->name,
            'phone' => $data['phone'] ?? $user->phone,
        ]);

        return $this->getProfilePayload($user->fresh(), $business_id);
    }

    public function changePassword(User $user, string $current_password, string $new_password): void
    {
        if (empty($user->password) || !Hash::check($current_password, $user->password)) {
            throw new Exception('Current password is incorrect.');
        }

        if (Hash::check($new_password, $user->password)) {
            throw new Exception('New password must be different from your current password.');
        }

        $user->update(['password' => Hash::make($new_password)]);
        $user->tokens()->delete();
    }

    public function saveAddress(User $user, string $business_id, array $data): array
    {
        $this->ensureProfile($user, $business_id);

        $address_id = $data['id'] ?? null;
        $make_default = !empty($data['isDefault']) || !empty($data['is_default']);

        if ($address_id) {
            $address = CustomerAddress::where('customer_address_id', $address_id)
                ->where('user_id', $user->id)
                ->where('business_id', $business_id)
                ->where('is_deleted', 0)
                ->first();

            if (!$address) {
                throw new Exception('Address not found.');
            }

            $address->update([
                'label' => $data['label'] ?? $address->label,
                'full_name' => $data['fullName'] ?? $data['full_name'] ?? $address->full_name,
                'phone' => $data['phone'] ?? $address->phone,
                'email' => $data['email'] ?? $address->email,
                'address' => $data['address'] ?? $address->address,
                'city' => $data['city'] ?? $address->city,
                'state' => $data['state'] ?? $address->state,
                'zip' => $data['zip'] ?? $address->zip,
                'country' => $data['country'] ?? $address->country,
                'date_updated' => now(),
            ]);
        } else {
            $has_any = CustomerAddress::where('user_id', $user->id)
                ->where('business_id', $business_id)
                ->where('is_deleted', 0)
                ->exists();

            $address = CustomerAddress::create([
                'customer_address_id' => generateUuid(),
                'user_id' => $user->id,
                'business_id' => $business_id,
                'label' => $data['label'] ?? null,
                'full_name' => $data['fullName'] ?? $data['full_name'],
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? $user->email,
                'address' => $data['address'],
                'city' => $data['city'] ?? null,
                'state' => $data['state'] ?? null,
                'zip' => $data['zip'] ?? null,
                'country' => $data['country'] ?? null,
                'is_default' => !$has_any || $make_default,
                'date_created' => now(),
            ]);

            if (!$has_any) {
                $make_default = true;
            }
        }

        if ($make_default) {
            $this->setDefaultAddress($user->id, $business_id, $address->customer_address_id);
            $address->refresh();
        }

        return $this->mapAddress($address);
    }

    public function deleteAddress(User $user, string $business_id, string $address_id): void
    {
        $address = CustomerAddress::where('customer_address_id', $address_id)
            ->where('user_id', $user->id)
            ->where('business_id', $business_id)
            ->where('is_deleted', 0)
            ->first();

        if (!$address) {
            throw new Exception('Address not found.');
        }

        $was_default = $address->is_default;

        $address->update([
            'is_deleted' => 1,
            'is_default' => false,
            'date_deleted' => now(),
        ]);

        if ($was_default) {
            $next = CustomerAddress::where('user_id', $user->id)
                ->where('business_id', $business_id)
                ->where('is_deleted', 0)
                ->orderBy('date_created')
                ->first();

            if ($next) {
                $next->update(['is_default' => true, 'date_updated' => now()]);
            }
        }
    }

    public function setDefaultAddress(int $user_id, string $business_id, string $address_id): void
    {
        CustomerAddress::where('user_id', $user_id)
            ->where('business_id', $business_id)
            ->where('is_deleted', 0)
            ->update(['is_default' => false]);

        CustomerAddress::where('customer_address_id', $address_id)
            ->where('user_id', $user_id)
            ->where('business_id', $business_id)
            ->where('is_deleted', 0)
            ->update(['is_default' => true, 'date_updated' => now()]);
    }

    public function mapAddress(CustomerAddress $address): array
    {
        return [
            'id' => $address->customer_address_id,
            'label' => $address->label,
            'fullName' => $address->full_name,
            'phone' => $address->phone,
            'email' => $address->email,
            'address' => $address->address,
            'city' => $address->city,
            'state' => $address->state,
            'zip' => $address->zip,
            'country' => $address->country,
            'isDefault' => (bool) $address->is_default,
        ];
    }
}
