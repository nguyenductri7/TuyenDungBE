<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordLinkNotification extends Notification
{
    use Queueable;

    private const FRONTEND_FALLBACK = 'http://localhost:5173';

    public function __construct(
        private readonly string $token
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $frontendUrl = rtrim((string) env('FRONTEND_URL', self::FRONTEND_FALLBACK), '/');
        $resetUrl = $frontendUrl . '/reset-password?token=' . urlencode($this->token) . '&email=' . urlencode((string) $notifiable->email);
        $subject = 'Dat lai mat khau tai khoan AI Recruitment';
        $previewText = 'Chung toi da nhan duoc yeu cau dat lai mat khau cho tai khoan cua ban.';

        return (new MailMessage)
            ->subject($subject)
            ->view('emails.reset-password', [
                'subjectText' => $subject,
                'previewText' => $previewText,
                'candidateName' => $notifiable->ho_ten ?: 'bạn',
                'resetUrl' => $resetUrl,
                'actionUrl' => $frontendUrl . '/login',
            ]);
    }
}
