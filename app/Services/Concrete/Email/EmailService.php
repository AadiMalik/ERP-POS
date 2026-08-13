<?php

namespace App\Services\Concrete\Email;

use Exception;
use App\Models\EmailSetting;
use App\Services\Concrete\Email\DTO\EmailData;

class EmailService
{
    public function send(string $business_id, EmailData $email): array
    {
        $setting = EmailSetting::where('business_id', $business_id)->first();

        if (!$setting) {
            return [
                'status' => false,
                'message' => 'Email settings not found.'
            ];
        }

        return $this->deliver($setting, $email);
    }

    /**
     * Sends using the platform-level channel config (the EmailSetting row
     * with business_id = NULL) instead of a specific business's own
     * settings. Used for Super Admin -> business notifications (e.g.
     * subscription reminders), which must work even for a brand-new
     * business that has no EmailSetting row of its own yet.
     */
    public function sendPlatform(EmailData $email): array
    {
        $setting = EmailSetting::whereNull('business_id')->first();

        if (!$setting) {
            return [
                'status' => false,
                'message' => 'Platform email settings are not configured.'
            ];
        }

        return $this->deliver($setting, $email);
    }

    protected function deliver(EmailSetting $setting, EmailData $email): array
    {
        try {
            if (!$setting->enable_email_notifications) {
                return [
                    'status' => false,
                    'message' => 'Email notifications are disabled.'
                ];
            }

            switch ($setting->mail_mailer) {

                case 'smtp':

                    if (
                        empty($setting->mail_host) ||
                        empty($setting->mail_port) ||
                        empty($setting->mail_username) ||
                        empty($setting->mail_password) ||
                        empty($setting->mail_from_address)
                    ) {
                        return [
                            'status' => false,
                            'message' => 'SMTP configuration is incomplete.'
                        ];
                    }

                    break;

                case 'mailgun':

                    if (
                        empty($setting->mail_host) ||
                        empty($setting->mail_password) ||
                        empty($setting->mail_from_address)
                    ) {
                        return [
                            'status' => false,
                            'message' => 'Mailgun configuration is incomplete.'
                        ];
                    }

                    break;

                case 'sendmail':

                    break;

                default:

                    return [
                        'status' => false,
                        'message' => 'Invalid mail provider.'
                    ];
            }

            $provider = EmailFactory::make($setting);

            $provider->send($email);

            return [
                'status' => true,
                'message' => 'Email sent successfully.'
            ];
        } catch (Exception $e) {

            return [
                'status' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}
