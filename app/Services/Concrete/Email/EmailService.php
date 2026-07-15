<?php

namespace App\Services\Concrete\Email;

use Exception;
use App\Models\EmailSetting;
use App\Services\Concrete\Email\DTO\EmailData;

class EmailService
{
    public function send(string $business_id, EmailData $email): array
    {
        try {

            $setting = EmailSetting::where('business_id', $business_id)->first();

            if (isset($setting) && !$setting) {
                return [
                    'status' => false,
                    'message' => 'Email settings not found.'
                ];
            }

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
