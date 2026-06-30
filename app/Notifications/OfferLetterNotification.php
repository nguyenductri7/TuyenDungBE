<?php

namespace App\Notifications;

use App\Models\UngTuyen;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class OfferLetterNotification extends Notification
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
        $tenViTri = $tin?->tieu_de ?: 'Chưa xác định';
        $tenCongTy = $congTy?->ten_cong_ty ?: 'Chưa xác định';
        $candidateId = (int) ($ungTuyen->hoSo?->nguoiDung?->id ?? $notifiable->id ?? 0);
        $frontEndUrl = rtrim((string) env('FRONTEND_URL', self::FRONTEND_FALLBACK), '/');
        $expiresAt = $ungTuyen->han_phan_hoi_offer && $ungTuyen->han_phan_hoi_offer->isFuture()
            ? $ungTuyen->han_phan_hoi_offer
            : now('UTC')->addDays(14);

        $acceptUrl = $candidateId > 0
            ? URL::temporarySignedRoute(
                'ung-vien.ung-tuyens.confirm-offer-email',
                $expiresAt,
                [
                    'id' => $ungTuyen->id,
                    'action' => 'accept',
                    'user' => $candidateId,
                ],
            )
            : null;

        $declineUrl = $candidateId > 0
            ? URL::temporarySignedRoute(
                'ung-vien.ung-tuyens.confirm-offer-email',
                $expiresAt,
                [
                    'id' => $ungTuyen->id,
                    'action' => 'decline',
                    'user' => $candidateId,
                ],
            )
            : null;

        return (new MailMessage)
            ->subject("De nghi nhan viec - {$tenViTri} tai {$tenCongTy}")
            ->view('emails.offer-letter', [
                'subjectText' => "De nghi nhan viec - {$tenViTri} tai {$tenCongTy}",
                'previewText' => 'Nha tuyen dung da gui de nghi nhan viec va dang cho phan hoi cua ban.',
                'candidateName' => $notifiable->ho_ten ?: 'bạn',
                'jobTitle' => $tenViTri,
                'companyName' => $tenCongTy,
                'offerNote' => $ungTuyen->ghi_chu_offer,
                'offerLink' => $ungTuyen->link_offer,
                'offerDeadline' => $ungTuyen->han_phan_hoi_offer?->timezone('Asia/Ho_Chi_Minh')->format('H:i d/m/Y'),
                'acceptUrl' => $acceptUrl,
                'declineUrl' => $declineUrl,
                'actionUrl' => $frontEndUrl . '/applications',
            ]);
    }

}
