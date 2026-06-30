<?php

namespace App\Notifications;

use App\Models\UngTuyen;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApplicationStatusNotification extends Notification
{
    use Queueable;

    private const FRONTEND_FALLBACK = 'http://localhost:5173';

    public function __construct(
        private readonly UngTuyen $ungTuyen,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $ungTuyen = $this->ungTuyen;
        $tin = $ungTuyen->tinTuyenDung;
        $congTy = $tin?->congTy;
        $isAccepted = (int) $ungTuyen->trang_thai === UngTuyen::TRANG_THAI_TRUNG_TUYEN;
        $frontEndUrl = rtrim((string) env('FRONTEND_URL', self::FRONTEND_FALLBACK), '/');
        $tenNguoiNhan = $notifiable->ho_ten ?: 'bạn';
        $tenViTri = $tin?->tieu_de ?: 'Chưa xác định';
        $tenCongTy = $congTy?->ten_cong_ty ?: 'Chưa xác định';
        $subject = $isAccepted
            ? "Chuc mung! Ban da trung tuyen vi tri {$tenViTri} tai {$tenCongTy}"
            : "Thong bao ket qua ung tuyen vi tri {$tenViTri} tai {$tenCongTy}";
        $previewText = $isAccepted
            ? 'Nha tuyen dung da xac nhan ban trung tuyen va se lien he voi ban cho cac buoc tiep theo.'
            : 'Nha tuyen dung da hoan tat danh gia va gui ket qua ung tuyen den ban.';

        return (new MailMessage)
            ->subject($subject)
            ->view('emails.application-status', [
                'subjectText' => $subject,
                'previewText' => $previewText,
                'isAccepted' => $isAccepted,
                'candidateName' => $tenNguoiNhan,
                'jobTitle' => $tenViTri,
                'companyName' => $tenCongTy,
                'ctaLabel' => $isAccepted ? 'Xem kết quả ứng tuyển' : 'Xem lịch sử ứng tuyển',
                'actionUrl' => $frontEndUrl . '/applications',
            ]);
    }
}
