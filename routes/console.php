<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\GiaoDichThanhToan;
use App\Models\AppNotification;
use App\Models\InterviewRound;
use App\Models\NguoiDung;
use App\Models\UngTuyen;
use App\Notifications\InterviewScheduledNotification;
use App\Services\AppNotificationService;
use App\Services\Billing\MomoWebhookHandlerService;
use App\Services\Billing\VnpayWebhookHandlerService;
use App\Services\ReEngagementService;
use Symfony\Component\Console\Command\Command;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('interviews:send-reminders {--hours=24 : Khoảng thời gian trước lịch phỏng vấn cần nhắc} {--dry-run : Chỉ liệt kê, không gửi email}', function () {
    $hours = max(1, (int) $this->option('hours'));
    $now = now();
    $until = $now->copy()->addHours($hours);
    $dryRun = (bool) $this->option('dry-run');

    $rounds = InterviewRound::query()
        ->with(['ungTuyen.hoSo.nguoiDung', 'ungTuyen.tinTuyenDung.congTy'])
        ->whereNotNull('ngay_hen_phong_van')
        ->whereNull('thoi_gian_gui_nhac_lich')
        ->whereHas('ungTuyen', fn ($query) => $query
            ->where('da_rut_don', false)
            ->where('trang_thai', UngTuyen::TRANG_THAI_DA_HEN_PHONG_VAN))
        ->where(function ($query): void {
            $query
                ->whereNull('trang_thai_tham_gia')
                ->orWhereIn('trang_thai_tham_gia', [
                    UngTuyen::PHONG_VAN_CHO_XAC_NHAN,
                    UngTuyen::PHONG_VAN_DA_XAC_NHAN,
                ]);
        })
        ->whereBetween('ngay_hen_phong_van', [$now, $until])
        ->orderBy('ngay_hen_phong_van')
        ->get();

    if ($rounds->isEmpty()) {
        $this->info("Không có lịch phỏng vấn nào cần nhắc trong {$hours} giờ tới.");
        return Command::SUCCESS;
    }

    $sent = 0;
    $skipped = 0;
    $failed = 0;

    foreach ($rounds as $round) {
        $application = $round->ungTuyen;
        $candidate = $application->hoSo?->nguoiDung;
        $jobTitle = $application->tinTuyenDung?->tieu_de ?: 'Chưa xác định';
        $candidateEmail = $candidate?->email ?: 'không có email';
        $interviewTime = optional($round->ngay_hen_phong_van)->timezone('Asia/Ho_Chi_Minh')->format('H:i d/m/Y');

        if (!$candidate || !filter_var($candidate->email, FILTER_VALIDATE_EMAIL)) {
            $skipped++;
            $this->warn("Bỏ qua #{$application->id}: ứng viên không có email hợp lệ.");
            continue;
        }

        if ($dryRun) {
            $this->line("[DRY-RUN] #{$application->id} {$candidateEmail} | {$jobTitle} | {$interviewTime}");
            continue;
        }

        try {
            $candidate->notify(new InterviewScheduledNotification($application, false, true, $round));
            app(AppNotificationService::class)->createForUser(
                $candidate,
                'candidate_interview_reminder',
                'Nhắc lịch phỏng vấn',
                "Bạn có lịch phỏng vấn sắp diễn ra cho vị trí {$jobTitle}.",
                '/applications',
                ['ung_tuyen_id' => $application->id, 'interview_round_id' => $round->id, 'ngay_hen_phong_van' => optional($round->ngay_hen_phong_van)?->toISOString()],
            );
            $round->forceFill(['thoi_gian_gui_nhac_lich' => now()])->save();
            $sent++;
            $this->info("Đã gửi nhắc lịch #{$application->id} tới {$candidateEmail} | {$interviewTime}");
        } catch (Throwable $exception) {
            report($exception);
            $failed++;
            $this->error("Gửi nhắc lịch #{$application->id} thất bại: {$exception->getMessage()}");
        }
    }

    $this->info("Hoàn tất: gửi {$sent}, bỏ qua {$skipped}, lỗi {$failed}.");

    return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
})->purpose('Gửi email nhắc lịch phỏng vấn cho các lịch sắp diễn ra và chưa được nhắc.');

Artisan::command('interviews:notify-overdue-results {--dry-run : Chỉ liệt kê, không tạo thông báo}', function () {
    $dryRun = (bool) $this->option('dry-run');
    $now = now();

    $rounds = InterviewRound::query()
        ->with(['ungTuyen.hoSo.nguoiDung', 'ungTuyen.tinTuyenDung.congTy'])
        ->whereNotNull('ngay_hen_phong_van')
        ->where('ngay_hen_phong_van', '<', $now)
        ->whereHas('ungTuyen', fn ($query) => $query
            ->where('da_rut_don', false)
            ->whereNotIn('trang_thai', UngTuyen::TRANG_THAI_CUOI)
            ->where('trang_thai', '>=', UngTuyen::TRANG_THAI_DA_HEN_PHONG_VAN))
        ->orderBy('ngay_hen_phong_van')
        ->get();

    if ($rounds->isEmpty()) {
        $this->info('Không có lịch phỏng vấn quá hạn cần nhắc cập nhật kết quả.');
        return Command::SUCCESS;
    }

    $notified = 0;
    $skipped = 0;
    $failed = 0;

    foreach ($rounds as $round) {
        $application = $round->ungTuyen;
        $company = $application->tinTuyenDung?->congTy;
        $jobTitle = $application->tinTuyenDung?->tieu_de ?: 'Chưa xác định';
        $candidateName = $application->hoSo?->nguoiDung?->ho_ten
            ?: $application->hoSo?->tieu_de_ho_so
            ?: "Ứng viên #{$application->ho_so_id}";
        $interviewTime = optional($round->ngay_hen_phong_van)->timezone('Asia/Ho_Chi_Minh')->format('H:i d/m/Y');

        if (!$company) {
            $skipped++;
            $this->warn("Bỏ qua #{$application->id}: không tìm thấy công ty.");
            continue;
        }

        $recipients = app(AppNotificationService::class)
            ->recruitmentRecipients($company, $application->hr_phu_trach_id);

        if ($recipients->isEmpty()) {
            $skipped++;
            $this->warn("Bỏ qua #{$application->id}: không có HR nhận thông báo.");
            continue;
        }

        if ($dryRun) {
            $this->line("[DRY-RUN] #{$application->id} {$candidateName} | {$jobTitle} | {$interviewTime} | {$recipients->count()} người nhận");
            continue;
        }

        try {
            foreach ($recipients as $recipientId) {
                $alreadyNotified = AppNotification::query()
                    ->where('nguoi_dung_id', $recipientId)
                    ->where('loai', 'employer_interview_result_overdue')
                    ->where('du_lieu_bo_sung->ung_tuyen_id', $application->id)
                    ->where('created_at', '>=', now()->subDay())
                    ->exists();

                if ($alreadyNotified) {
                    continue;
                }

                app(AppNotificationService::class)->createForUser(
                    $recipientId,
                    'employer_interview_result_overdue',
                    'Cần cập nhật kết quả phỏng vấn',
                    "Lịch phỏng vấn của {$candidateName} cho vị trí {$jobTitle} đã qua lúc {$interviewTime} nhưng chưa có kết quả cuối.",
                    '/employer/interviews',
                    [
                        'ung_tuyen_id' => $application->id,
                        'interview_round_id' => $round->id,
                        'tin_tuyen_dung_id' => $application->tin_tuyen_dung_id,
                        'ngay_hen_phong_van' => optional($round->ngay_hen_phong_van)?->toISOString(),
                    ],
                );
            }

            $notified++;
            $this->info("Đã nhắc cập nhật kết quả #{$application->id} | {$candidateName} | {$interviewTime}");
        } catch (Throwable $exception) {
            report($exception);
            $failed++;
            $this->error("Nhắc cập nhật kết quả #{$application->id} thất bại: {$exception->getMessage()}");
        }
    }

    $this->info("Hoàn tất: xử lý {$notified}, bỏ qua {$skipped}, lỗi {$failed}.");

    return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
})->purpose('Tạo thông báo nội bộ cho HR khi lịch phỏng vấn đã qua nhưng chưa chốt kết quả cuối.');

Artisan::command('reengagement:run {--dry-run : Chỉ liệt kê, không tạo thông báo}', function () {
    $dryRun = (bool) $this->option('dry-run');
    $command = $this;
    $processed = 0;
    $sent = [
        'expiring_notifications' => 0,
        'stale_notifications' => 0,
        'similar_notifications' => 0,
    ];
    $failed = 0;

    NguoiDung::query()
        ->where('vai_tro', NguoiDung::VAI_TRO_UNG_VIEN)
        ->where('trang_thai', 1)
        ->whereHas('tinDaLuus')
        ->withCount('tinDaLuus')
        ->orderBy('id')
        ->chunkById(100, function ($candidates) use ($command, $dryRun, &$processed, &$sent, &$failed): void {
            foreach ($candidates as $candidate) {
                try {
                    $result = app(ReEngagementService::class)->runForCandidate($candidate, $dryRun);
                    $processed++;

                    foreach ($sent as $key => $value) {
                        $sent[$key] = $value + (int) ($result[$key] ?? 0);
                    }

                    if ($dryRun) {
                        $command->line("[DRY-RUN] Ứng viên #{$candidate->id}: expiring={$result['expiring_notifications']}, stale={$result['stale_notifications']}, similar={$result['similar_notifications']}");
                    }
                } catch (Throwable $exception) {
                    report($exception);
                    $failed++;
                    $command->error("Re-engagement cho ứng viên #{$candidate->id} thất bại: {$exception->getMessage()}");
                }
            }
        });

    $this->info(
        "Hoàn tất Re-engagement: xử lý {$processed} ứng viên, "
        . "sắp hết hạn {$sent['expiring_notifications']}, "
        . "follow-up {$sent['stale_notifications']}, "
        . "job tương tự {$sent['similar_notifications']}, "
        . "lỗi {$failed}."
    );

    return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
})->purpose('Tạo notification re-engagement cho ứng viên dựa trên tin đã lưu, hạn ứng tuyển và job tương tự.');

Artisan::command('billing:reconcile-pending-payments {--minutes= : Số phút tối đa cho trạng thái pending, mặc định theo cấu hình từng gateway} {--dry-run : Chỉ liệt kê, không cập nhật}', function () {
    $overrideMinutes = $this->option('minutes');
    $dryRun = (bool) $this->option('dry-run');
    $momoMinutes = max(1, (int) ($overrideMinutes ?: config('services.momo.pending_expire_minutes', 15)));
    $vnpayMinutes = max(1, (int) ($overrideMinutes ?: config('services.vnpay.pending_expire_minutes', 15)));
    $momoCutoff = now()->subMinutes($momoMinutes);
    $vnpayCutoff = now()->subMinutes($vnpayMinutes);

    $payments = GiaoDichThanhToan::query()
        ->with('goiDichVu:id,ma_goi,ten_goi')
        ->where('trang_thai', GiaoDichThanhToan::TRANG_THAI_PENDING)
        ->where(function ($query) use ($momoCutoff, $vnpayCutoff): void {
            $query
                ->where(function ($inner) use ($momoCutoff): void {
                    $inner->where('gateway', GiaoDichThanhToan::GATEWAY_MOMO)
                        ->where('created_at', '<=', $momoCutoff);
                })
                ->orWhere(function ($inner) use ($vnpayCutoff): void {
                    $inner->where('gateway', GiaoDichThanhToan::GATEWAY_VNPAY)
                        ->where('created_at', '<=', $vnpayCutoff);
                });
        })
        ->orderBy('id')
        ->get();

    if ($payments->isEmpty()) {
        $this->info("Không có giao dịch pending nào quá hạn cần reconcile. Mốc hiện tại: MoMo {$momoMinutes} phút, VNPay {$vnpayMinutes} phút.");
        return Command::SUCCESS;
    }

    $reconciled = 0;
    $cancelled = 0;
    $failed = 0;
    $momoHandler = app(MomoWebhookHandlerService::class);
    $vnpayHandler = app(VnpayWebhookHandlerService::class);

    foreach ($payments as $payment) {
        $line = "#{$payment->id} {$payment->ma_giao_dich_noi_bo} | {$payment->gateway} | {$payment->loai_giao_dich} | {$payment->so_tien} VND";

        if ($dryRun) {
            $this->line("[DRY-RUN] {$line}");
            continue;
        }

        try {
            $beforeStatus = $payment->trang_thai;
            $reconciledPayment = match ($payment->gateway) {
                GiaoDichThanhToan::GATEWAY_MOMO => $momoHandler->reconcilePayment($payment),
                GiaoDichThanhToan::GATEWAY_VNPAY => $vnpayHandler->reconcilePayment($payment),
                default => null,
            };

            if (!$reconciledPayment) {
                throw new RuntimeException("Gateway {$payment->gateway} chưa hỗ trợ reconcile pending.");
            }

            $reconciled++;

            if ($reconciledPayment->trang_thai === GiaoDichThanhToan::TRANG_THAI_PENDING) {
                $reconciledPayment->forceFill([
                    'trang_thai' => GiaoDichThanhToan::TRANG_THAI_HUY,
                ])->save();
                $cancelled++;
                $this->warn(strtoupper((string) $payment->gateway) . " vẫn pending sau đối soát, đã chuyển cancelled: {$line}");
            } else {
                $this->info("Đối soát {$beforeStatus} -> {$reconciledPayment->trang_thai}: {$line}");
            }
        } catch (\Throwable $exception) {
            $failed++;
            $this->error("Không thể đối soát {$line}: {$exception->getMessage()}");
        }
    }

    $this->info($dryRun
        ? "Đã liệt kê {$payments->count()} giao dịch pending quá hạn."
        : "Hoàn tất reconcile: đối soát {$reconciled}, cancelled {$cancelled}, lỗi {$failed}.");

    return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
})->purpose('Đánh dấu các giao dịch MoMo/VNPay pending quá lâu là cancelled để hỗ trợ reconciliation và vận hành.');

Schedule::command('interviews:send-reminders --hours=24')
    ->everyFifteenMinutes()
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command('interviews:notify-overdue-results')
    ->hourly()
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command('reengagement:run')
    ->dailyAt('08:00')
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command('billing:reconcile-pending-payments')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->onOneServer();
