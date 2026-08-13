<?php

namespace App\Services\Concrete\Admin;

use App\Models\BusinessSubscription;
use App\Services\Concrete\Email\DTO\EmailData;
use App\Services\Concrete\Email\EmailService;
use App\Services\Concrete\SMS\DTO\SMSData;
use App\Services\Concrete\SMS\SMSService;
use App\Services\Concrete\Whatsapp\DTO\WhatsappData;
use App\Services\Concrete\Whatsapp\WhatsappService;

/**
 * Platform (Super Admin) -> business notifications for subscription
 * lifecycle events. Sends through the platform-level Email/SMS/Whatsapp
 * settings (business_id = NULL row) via the *Service::sendPlatform()
 * methods added alongside this class, so a brand-new business without its
 * own configured channels still receives its first reminder/notice.
 */
class SubscriptionNotificationService
{
    protected EmailService $email_service;
    protected SMSService $sms_service;
    protected WhatsappService $whatsapp_service;
    protected DocumentSendLogService $send_log;

    public function __construct(
        EmailService $email_service,
        SMSService $sms_service,
        WhatsappService $whatsapp_service,
        DocumentSendLogService $send_log
    ) {
        $this->email_service = $email_service;
        $this->sms_service = $sms_service;
        $this->whatsapp_service = $whatsapp_service;
        $this->send_log = $send_log;
    }

    public function sendReminder(BusinessSubscription $subscription, int $thresholdDays): array
    {
        $business = $subscription->business;
        $package = $subscription->package;

        $subject = $thresholdDays > 0
            ? "Your subscription expires in {$thresholdDays} day" . ($thresholdDays === 1 ? '' : 's')
            : 'Your subscription expires today';

        $message = "Hi " . ($business->owner_name ?? $business->name) . ",\n\n"
            . "Your \"{$package?->name}\" subscription for {$business->name} "
            . ($thresholdDays > 0 ? "expires in {$thresholdDays} day(s)" : "expires today")
            . " on " . optional($subscription->end_at)->format('d-m-Y') . ".\n"
            . "Please renew soon to avoid any interruption to your account.";

        return $this->dispatchAll($subscription, 'subscription_reminder', $subject, $message);
    }

    public function notifyEvent(BusinessSubscription $subscription, string $eventType, string $message): array
    {
        $subject = 'Subscription update: ' . ucwords(str_replace('_', ' ', $eventType));

        return $this->dispatchAll($subscription, 'subscription_event', $subject, $message);
    }

    protected function dispatchAll(BusinessSubscription $subscription, string $module, string $subject, string $message): array
    {
        $business = $subscription->business;
        $results = [];

        if (!empty($business->owner_email)) {
            $response = $this->email_service->sendPlatform(new EmailData([
                'to' => $business->owner_email,
                'subject' => $subject,
                'body' => nl2br(e($message)),
            ]));

            $this->send_log->log(
                $business->business_id,
                $module,
                $subscription->business_subscription_id,
                'email',
                $business->owner_email,
                $response['status'] ? 'sent' : 'failed',
                $response['status'] ? null : $response['message'],
                null
            );

            $results['email'] = $response;
        }

        if (!empty($business->owner_phone)) {
            $sms_response = $this->sms_service->sendPlatform(new SMSData([
                'phone' => $business->owner_phone,
                'message' => $message,
            ]));

            $this->send_log->log(
                $business->business_id,
                $module,
                $subscription->business_subscription_id,
                'sms',
                $business->owner_phone,
                $sms_response['status'] ? 'sent' : 'failed',
                $sms_response['status'] ? null : $sms_response['message'],
                null
            );

            $results['sms'] = $sms_response;

            $whatsapp_response = $this->whatsapp_service->sendPlatform(new WhatsappData([
                'phone' => $business->owner_phone,
                'message' => $message,
            ]));

            $this->send_log->log(
                $business->business_id,
                $module,
                $subscription->business_subscription_id,
                'whatsapp',
                $business->owner_phone,
                $whatsapp_response['status'] ? 'sent' : 'failed',
                $whatsapp_response['status'] ? null : $whatsapp_response['message'],
                null
            );

            $results['whatsapp'] = $whatsapp_response;
        }

        return $results;
    }
}
