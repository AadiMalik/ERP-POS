<?php

namespace App\Services\Concrete\Admin\Intro;

use App\Enums\Status;
use App\Models\IntroContactInquiry;
use App\Models\IntroContactReply;
use App\Models\Package;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionPayment;
use App\Repository\Repository;
use App\Services\Concrete\Admin\PaymentService;
use App\Services\Concrete\Email\DTO\EmailData;
use App\Services\Concrete\Email\EmailService;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

class ContactInquiryService
{
    protected $repo;
    protected $email_service;
    protected $registration_service;
    protected $payment_service;

    public function __construct(
        EmailService $email_service,
        BusinessRegistrationService $registration_service,
        PaymentService $payment_service
    ) {
        $this->repo = new Repository(new IntroContactInquiry());
        $this->email_service = $email_service;
        $this->registration_service = $registration_service;
        $this->payment_service = $payment_service;
    }

    public function getData($obj = [])
    {
        $q = $this->repo->getModel()::with('business')->where('is_deleted', 0)->orderByDesc('date_created');
        if (!empty($obj['status_filter'])) {
            $q->where('status', $obj['status_filter']);
        }

        return DataTables::of($q)
            ->addColumn('status_badge', function ($item) {
                $map = [
                    'new' => 'danger',
                    'read' => 'warning',
                    'replied' => 'success',
                    'closed' => 'secondary',
                ];
                $cls = $map[$item->status] ?? 'secondary';
                return '<span class="badge bg-label-' . $cls . '">' . e(ucfirst($item->status)) . '</span>';
            })
            ->addColumn('business_name', fn ($item) => $item->business?->name ?? '-')
            ->addColumn('date_created', fn ($item) => localDateTime($item->date_created))
            ->addColumn('action', function ($item) {
                return "
                    <a class='btn btn-icon btn-outline-primary mr-2' id='viewIntroInquiry' data-id='{$item->intro_contact_inquiry_id}'><i class='fa fa-eye'></i></a>
                    <a class='btn btn-icon btn-outline-danger deleteIntroItem' data-id='{$item->intro_contact_inquiry_id}'><i class='fa fa-trash'></i></a>
                ";
            })
            ->rawColumns(['status_badge', 'action'])
            ->make(true);
    }

    public function create(array $obj)
    {
        $obj['intro_contact_inquiry_id'] = generateUuid();
        $obj['status'] = 'new';
        $obj['date_created'] = now();
        return $this->repo->create($obj);
    }

    public function getById($id)
    {
        $inquiry = $this->repo->find($id)->load(['replies', 'business.currentSubscription', 'subscriptionInvoice.payments']);
        if ($inquiry->status === 'new') {
            $inquiry->update(['status' => 'read']);
        }
        return $inquiry->fresh(['replies', 'business.currentSubscription', 'subscriptionInvoice.payments']);
    }

    public function updateStatus($id, string $status)
    {
        return $this->repo->update(['status' => $status], $id);
    }

    /**
     * Registers a business from an intro contact inquiry (reuses intro registration).
     * Optionally confirms payment immediately to activate the business.
     */
    public function registerBusiness($id, array $data)
    {
        $inquiry = $this->repo->find($id);

        if ($inquiry->business_id) {
            throw new Exception('A business is already linked to this inquiry.');
        }

        $package = Package::where('package_id', $data['package_id'])
            ->where('is_deleted', 0)
            ->where('status', 1)
            ->first();

        if (!$package) {
            throw new Exception('Selected package is not available.');
        }

        return DB::transaction(function () use ($inquiry, $data, $package) {
            $registration = $this->registration_service->registerFromIntro([
                'package_id' => $package->package_id,
                'billing_cycle' => $data['billing_cycle'] ?? $package->duration_type ?? 'monthly',
                'business_name' => $data['business_name'] ?? $inquiry->name,
                'owner_name' => $data['owner_name'] ?? $inquiry->name,
                'owner_email' => $data['owner_email'] ?? $inquiry->email,
                'owner_phone' => $data['owner_phone'] ?? $inquiry->phone,
                'business_email' => $data['business_email'] ?? $inquiry->email,
                'business_phone' => $data['business_phone'] ?? $inquiry->phone,
                'city' => $data['city'] ?? null,
                'address' => $data['address'] ?? null,
                'notes' => $data['notes'] ?? ('Registered from contact inquiry ' . $inquiry->intro_contact_inquiry_id),
            ]);

            $invoice = SubscriptionInvoice::where('business_id', $registration->business_id)
                ->where('is_deleted', 0)
                ->orderByDesc('date_created')
                ->first();

            if (!empty($data['payment_reference']) && $invoice) {
                $payment = $invoice->payments()->where('is_deleted', 0)->where('status', Status::PENDING)->latest('date_created')->first();
                if ($payment) {
                    $payment->update([
                        'payment_method' => $data['payment_method'] ?? $payment->payment_method,
                        'payment_reference' => $data['payment_reference'],
                        'notes' => $data['payment_notes'] ?? $payment->notes,
                        'updatedby_id' => Auth::id(),
                        'date_updated' => now(),
                    ]);
                }
            }

            $inquiry->update([
                'business_id' => $registration->business_id,
                'subscription_invoice_id' => $invoice?->subscription_invoice_id,
                'status' => 'closed',
            ]);

            if (!empty($data['activate']) && $invoice) {
                $this->activateBusiness($inquiry->intro_contact_inquiry_id);
            }

            return $this->getById($inquiry->intro_contact_inquiry_id);
        });
    }

    /**
     * Confirms the linked pending payment and activates the business.
     */
    public function activateBusiness($id)
    {
        $inquiry = $this->repo->find($id)->load('subscriptionInvoice.payments');

        if (!$inquiry->business_id || !$inquiry->subscription_invoice_id) {
            throw new Exception('No business/invoice is linked to this inquiry. Register the business first.');
        }

        $invoice = SubscriptionInvoice::with('payments')->find($inquiry->subscription_invoice_id);
        if (!$invoice) {
            throw new Exception('Linked invoice not found.');
        }

        $payment = $invoice->payments->where('is_deleted', 0)->where('status', Status::PENDING)->sortByDesc('date_created')->first();

        if (!$payment) {
            $confirmed = $invoice->payments->where('is_deleted', 0)->where('status', Status::CONFIRMED)->first();
            if ($confirmed) {
                return $this->getById($id);
            }
            throw new Exception('No pending payment found to confirm.');
        }

        $this->payment_service->approve($payment, Auth::id());

        $inquiry->update(['status' => 'closed']);

        return $this->getById($id);
    }

    public function updatePayment($id, array $data)
    {
        $inquiry = $this->repo->find($id);

        if (!$inquiry->subscription_invoice_id) {
            throw new Exception('No invoice linked. Register the business first.');
        }

        $invoice = SubscriptionInvoice::findOrFail($inquiry->subscription_invoice_id);
        $payment = SubscriptionPayment::where('subscription_invoice_id', $invoice->subscription_invoice_id)
            ->where('is_deleted', 0)
            ->where('status', Status::PENDING)
            ->latest('date_created')
            ->first();

        if (!$payment) {
            throw new Exception('No pending payment to update.');
        }

        $payment->update([
            'payment_method' => $data['payment_method'] ?? $payment->payment_method,
            'payment_reference' => $data['payment_reference'] ?? $payment->payment_reference,
            'amount' => $data['amount'] ?? $payment->amount,
            'notes' => $data['notes'] ?? $payment->notes,
            'updatedby_id' => Auth::id(),
            'date_updated' => now(),
        ]);

        return $payment->fresh();
    }

    public function reply($id, string $message)
    {
        $inquiry = $this->repo->find($id);

        $result = $this->email_service->sendPlatform(new EmailData([
            'to' => $inquiry->email,
            'subject' => 'Re: ' . ($inquiry->subject ?: 'Your message to Dukanaz'),
            'body' => nl2br(e($message)),
        ]));

        $reply = IntroContactReply::create([
            'intro_contact_reply_id' => generateUuid(),
            'intro_contact_inquiry_id' => $inquiry->intro_contact_inquiry_id,
            'reply_message' => $message,
            'send_status' => $result['status'] ? 'sent' : 'failed',
            'error_message' => $result['status'] ? null : ($result['message'] ?? 'Send failed'),
            'repliedby_id' => Auth::id(),
            'date_created' => now(),
        ]);

        if (!$result['status']) {
            throw new Exception($result['message'] ?? 'Failed to send email.');
        }

        $inquiry->update(['status' => 'replied']);
        return $reply;
    }

    public function delete($id)
    {
        return $this->repo->update([
            'is_deleted' => 1,
            'deletedby_id' => Auth::id(),
            'date_deleted' => now(),
        ], $id);
    }
}
