<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class VerifyEmailLinkNotification extends Notification
{
    use Queueable;

    private const FRONTEND_FALLBACK = 'http://localhost:5173';

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1((string) $notifiable->getEmailForVerification()),
            ]
        );

        $subject = 'Xac thuc email de kich hoat tai khoan AI Recruitment';
        $previewText = 'Vui long xac thuc email de kich hoat tai khoan va su dung day du cac tinh nang cua he thong.';
        $frontEndUrl = rtrim((string) env('FRONTEND_URL', self::FRONTEND_FALLBACK), '/');

        return (new MailMessage)
            ->subject($subject)
            ->view('emails.verify-email', [
                'subjectText' => $subject,
                'previewText' => $previewText,
                'candidateName' => $notifiable->ho_ten ?: 'bạn',
                'verificationUrl' => $verificationUrl,
                'actionUrl' => $frontEndUrl . '/login',
            ]);
    }
}
