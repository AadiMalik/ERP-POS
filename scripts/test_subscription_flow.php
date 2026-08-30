<?php

/**
 * One-off E2E flow test for unified subscription invoices.
 * Run: php artisan test:subscription-flow
 * Or:  php scripts/test_subscription_flow.php  (via bootstrap below if artisan command)
 */

use App\Enums\RoleNames;
use App\Enums\Status;
use App\Models\Business;
use App\Models\Package;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionPayment;
use App\Models\User;
use App\Services\Concrete\Admin\Intro\BusinessRegistrationService;
use App\Services\Concrete\Admin\Intro\ContactInquiryService;
use App\Services\Concrete\Admin\PaymentService;
use App\Services\Concrete\Admin\SubscriptionService;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$failures = [];
$pass = function (string $msg) {
    echo "[PASS] {$msg}\n";
};
$fail = function (string $msg) use (&$failures) {
    echo "[FAIL] {$msg}\n";
    $failures[] = $msg;
};

echo "=== Subscription / Intro E2E Flow Test ===\n";

$package = Package::where('is_deleted', 0)
    ->where('status', 1)
    ->where(function ($q) {
        $q->where('is_custom', 0)->orWhereNull('is_custom');
    })
    ->orderBy('order')
    ->first();

if (!$package) {
    $fail('No active non-custom package found');
    exit(1);
}
$pass('Package found: ' . $package->name . ' (' . $package->duration_type . ')');

$suffix = substr(str_replace('.', '', uniqid('', true)), -8);
$email = "e2e.owner.{$suffix}@example.test";
$bizName = "E2E Biz {$suffix}";

/** @var BusinessRegistrationService $regService */
$regService = app(BusinessRegistrationService::class);

try {
    $registration = $regService->registerFromIntro([
        'package_id' => $package->package_id,
        'billing_cycle' => $package->duration_type ?: 'monthly',
        'business_name' => $bizName,
        'owner_name' => 'E2E Owner',
        'owner_email' => $email,
        'owner_phone' => '03001234567',
        'business_email' => $email,
        'city' => 'Karachi',
        'notes' => 'E2E automated test registration',
    ]);
    $pass('Intro registration created: ' . $registration->intro_business_registration_id);
} catch (Throwable $e) {
    $fail('Intro registration failed: ' . $e->getMessage());
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

$business = Business::find($registration->business_id);
if (!$business) {
    $fail('Business row missing after registration');
    exit(1);
}

if ($business->status !== Status::PENDING && $business->status !== 'pending') {
    $fail("Business status expected pending, got {$business->status}");
} else {
    $pass('Business status is pending');
}

if ($business->subscription_start !== null || $business->subscription_end !== null) {
    $fail('Business dates should be null until payment confirm (got start=' . var_export($business->subscription_start, true) . ' end=' . var_export($business->subscription_end, true) . ')');
} else {
    $pass('Business subscription dates frozen (null)');
}

$invoice = SubscriptionInvoice::where('business_id', $business->business_id)
    ->where('is_deleted', 0)
    ->orderByDesc('date_created')
    ->first();

if (!$invoice) {
    $fail('Unpaid invoice not created');
    exit(1);
}
$pass('Invoice created: ' . $invoice->invoice_no . ' type=' . ($invoice->request_type ?: 'null') . ' status=' . $invoice->status);

if ($invoice->request_type !== 'new') {
    $fail("Invoice request_type expected new, got {$invoice->request_type}");
} else {
    $pass('Invoice request_type is new');
}

$payment = SubscriptionPayment::where('subscription_invoice_id', $invoice->subscription_invoice_id)
    ->where('is_deleted', 0)
    ->orderByDesc('date_created')
    ->first();

if (!$payment) {
    $fail('Pending payment row not created');
    exit(1);
}
if ($payment->status !== Status::PENDING) {
    $fail("Payment status expected pending, got {$payment->status}");
} else {
    $pass('Payment pending row exists method=' . $payment->payment_method);
}

// Simulate bank transfer details + receipt filename
$payment->update([
    'payment_method' => 'bank_transfer',
    'payment_reference' => 'E2E-BANK-REF-' . $suffix,
    'payment_proof' => 'e2e_receipt_' . $suffix . '.png',
    'notes' => 'E2E bank transfer test',
]);
$pass('Bank reference + receipt fields set on payment');

// Create Business Admin user for login test
$baRole = Role::where('name', RoleNames::BUSINESSADMIN)->whereNull('business_id')->first()
    ?: Role::where('name', RoleNames::BUSINESSADMIN)->first();

$userPassword = 'Test@12345';
$user = User::create([
    'name' => 'E2E Admin ' . $suffix,
    'email' => $email,
    'password' => Hash::make($userPassword),
    'business_id' => $business->business_id,
    'status' => 1,
    'is_deleted' => 0,
    'date_created' => now(),
]);
if ($baRole) {
    $user->assignRole($baRole);
    $pass('Business Admin user created: ' . $email . ' / ' . $userPassword);
} else {
    $fail('Business Admin role template not found — user created without role');
}

/** @var SubscriptionService $subService */
$subService = app(SubscriptionService::class);
if (!$subService->isAccessRestricted($business->fresh())) {
    $fail('Pending business should be access-restricted before payment confirm');
} else {
    $pass('Pending business is access-restricted (My Subscription only)');
}

// Confirm payment
try {
    app(PaymentService::class)->approve($payment->fresh(), $user->id);
    $pass('Payment confirmed');
} catch (Throwable $e) {
    $fail('Payment approve failed: ' . $e->getMessage());
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

$business->refresh();
$invoice->refresh();
$payment->refresh();

if ($business->status !== Status::ACTIVE) {
    $fail("After confirm business status expected active, got {$business->status}");
} else {
    $pass('Business is active after confirm');
}

if (!$business->subscription_end) {
    $fail('After confirm subscription_end should be set');
} else {
    $pass('subscription_end set: ' . $business->subscription_end);
}

if ($invoice->status !== Status::PAID && $invoice->status !== 'paid') {
    $fail("Invoice status expected paid, got {$invoice->status}");
} else {
    $pass('Invoice is paid');
}

if ($payment->status !== Status::CONFIRMED) {
    $fail("Payment status expected confirmed, got {$payment->status}");
} else {
    $pass('Payment is confirmed');
}

// Confirm then reject should fail
try {
    app(PaymentService::class)->reject($payment->fresh(), 'should fail');
    $fail('Rejected a confirmed payment (should have been blocked)');
} catch (Throwable $e) {
    $pass('Confirmed payment cannot be rejected: ' . $e->getMessage());
}

if ($subService->isAccessRestricted($business->fresh())) {
    $fail('Active paid business should NOT be access-restricted');
} else {
    $pass('Active business has full access');
}

// Intro registration status sync
$registration->refresh();
if ($registration->status !== 'activated') {
    $fail("Intro registration status expected activated, got {$registration->status}");
} else {
    $pass('Intro registration synced to activated');
}

// Auth attempt
if (!auth()->attempt(['email' => $email, 'password' => $userPassword])) {
    $fail('Auth::attempt failed for new business admin');
} else {
    $pass('Business admin can authenticate');
    auth()->logout();
}

// Renew unpaid flow — expiry should stay previous
$prevEnd = $business->subscription_end;
try {
    $renewed = $subService->renew($business->fresh(), [
        'package_id' => $package->package_id,
        'billing_cycle' => $package->duration_type ?: 'monthly',
        'request_type' => 'renew',
        'payment' => [
            'confirm' => false,
            'method' => 'bank_transfer',
            'reference' => 'E2E-RENEW-' . $suffix,
        ],
    ]);
    $pass('Renew unpaid subscription created: ' . $renewed->business_subscription_id);
} catch (Throwable $e) {
    $fail('Renew unpaid failed: ' . $e->getMessage());
    $renewed = null;
}

$business->refresh();
if ((string) $business->subscription_end !== (string) $prevEnd) {
    $fail("Renew unpaid changed subscription_end (was {$prevEnd}, now {$business->subscription_end})");
} else {
    $pass('Renew unpaid kept previous subscription_end');
}

$renewInvoice = SubscriptionInvoice::where('business_id', $business->business_id)
    ->where('request_type', 'renew')
    ->where('is_deleted', 0)
    ->orderByDesc('date_created')
    ->first();

if (!$renewInvoice) {
    $fail('Renew invoice not found');
} else {
    $pass('Renew invoice: ' . $renewInvoice->invoice_no);
    $renewPay = $renewInvoice->payments()->where('is_deleted', 0)->latest('date_created')->first();
    if ($renewPay && $renewPay->status === Status::PENDING) {
        $pass('Renew payment pending');
    } else {
        $fail('Renew pending payment missing');
    }
}

// Contact inquiry register path (light)
try {
    /** @var ContactInquiryService $contactService */
    $contactService = app(ContactInquiryService::class);
    $inquiry = $contactService->create([
        'name' => 'E2E Contact ' . $suffix,
        'email' => "e2e.contact.{$suffix}@example.test",
        'phone' => '03007654321',
        'subject' => 'E2E contact',
        'message' => 'Please register us',
    ]);
    $pass('Contact inquiry created');

    $suffix2 = $suffix . 'c';
    $linked = $contactService->registerBusiness($inquiry->intro_contact_inquiry_id, [
        'package_id' => $package->package_id,
        'business_name' => 'E2E Contact Biz ' . $suffix2,
        'owner_name' => 'Contact Owner',
        'owner_email' => "e2e.contact.owner.{$suffix2}@example.test",
        'payment_method' => 'bank_transfer',
        'payment_reference' => 'CONTACT-REF-' . $suffix2,
        'activate' => false,
    ]);
    if ($linked->business_id) {
        $pass('Contact → business registered linked');
    } else {
        $fail('Contact register did not link business_id');
    }
} catch (Throwable $e) {
    $fail('Contact register flow failed: ' . $e->getMessage());
}

// Pending payment count
$count = app(\App\Services\Concrete\Admin\InvoiceService::class)->pendingPaymentCount();
$pass('Pending payment count now: ' . $count);

echo "\n=== Summary ===\n";
echo 'Failures: ' . count($failures) . "\n";
foreach ($failures as $f) {
    echo " - {$f}\n";
}

echo "\nTest credentials (new business admin):\n";
echo "  email: {$email}\n";
echo "  password: {$userPassword}\n";
echo "  business: {$bizName}\n";
echo "  business_id: {$business->business_id}\n";

exit(count($failures) > 0 ? 1 : 0);
