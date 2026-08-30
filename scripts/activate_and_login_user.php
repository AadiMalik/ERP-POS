<?php

use App\Enums\RoleNames;
use App\Enums\Status;
use App\Models\Business;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionPayment;
use App\Models\User;
use App\Services\Concrete\Admin\PaymentService;
use App\Services\Concrete\Admin\SubscriptionService;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$bizId = $argv[1] ?? null;
$email = $argv[2] ?? null;

if (!$bizId || !$email) {
    // Use latest pending business from intro
    $business = Business::where('status', Status::PENDING)->orderByDesc('date_created')->first();
    if (!$business) {
        fwrite(STDERR, "No pending business found\n");
        exit(1);
    }
    $bizId = $business->business_id;
    $email = $business->owner_email ?: ('ba.' . substr($bizId, 0, 8) . '@example.test');
} else {
    $business = Business::findOrFail($bizId);
}

echo "Business: {$business->name} ({$business->business_id}) status={$business->status}\n";

$password = 'Test@12345';
$user = User::where('email', $email)->first();
if (!$user) {
    $user = User::create([
        'name' => $business->owner_name ?: 'BA User',
        'email' => $email,
        'password' => Hash::make($password),
        'business_id' => $business->business_id,
        'status' => 1,
        'is_deleted' => 0,
        'must_change_password' => 0,
        'date_created' => now(),
    ]);
    $role = Role::where('name', RoleNames::BUSINESSADMIN)->whereNull('business_id')->first()
        ?: Role::where('name', RoleNames::BUSINESSADMIN)->first();
    if ($role) {
        $user->assignRole($role);
    }
    echo "Created user {$email} / {$password}\n";
} else {
    $user->forceFill([
        'password' => Hash::make($password),
        'business_id' => $business->business_id,
        'must_change_password' => 0,
        'status' => 1,
    ])->save();
    echo "Updated user {$email} / {$password}\n";
}

$sub = app(SubscriptionService::class);
echo 'Restricted before confirm: ' . ($sub->isAccessRestricted($business->fresh()) ? 'yes' : 'no') . "\n";

$invoice = SubscriptionInvoice::where('business_id', $business->business_id)->where('is_deleted', 0)->orderByDesc('date_created')->first();
$payment = SubscriptionPayment::where('subscription_invoice_id', $invoice->subscription_invoice_id)->where('is_deleted', 0)->where('status', Status::PENDING)->latest('date_created')->first();

if ($payment) {
    $payment->update([
        'payment_method' => 'bank_transfer',
        'payment_reference' => 'HTTP-BANK-' . substr($business->business_id, 0, 6),
        'payment_proof' => 'http_receipt_demo.png',
    ]);
    app(PaymentService::class)->approve($payment->fresh(), $user->id);
    echo "Payment confirmed. Invoice={$invoice->invoice_no}\n";
} else {
    echo "No pending payment (maybe already confirmed)\n";
}

$business->refresh();
echo "Business status after: {$business->status}, end={$business->subscription_end}\n";
echo 'Restricted after confirm: ' . ($sub->isAccessRestricted($business->fresh()) ? 'yes' : 'no') . "\n";

$ok = auth()->attempt(['email' => $email, 'password' => $password]);
echo 'Auth attempt: ' . ($ok ? 'OK' : 'FAIL') . "\n";
if ($ok) {
    auth()->logout();
}

echo "LOGIN_EMAIL={$email}\n";
echo "LOGIN_PASSWORD={$password}\n";
