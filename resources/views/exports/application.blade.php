<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: "DejaVu Sans", sans-serif; color: #0f172a; font-size: 12px; line-height: 1.55; }
        .page { padding: 28px; }
        .header { border-bottom: 2px solid #2563eb; padding-bottom: 14px; margin-bottom: 18px; }
        .eyebrow { color: #2563eb; font-size: 10px; text-transform: uppercase; letter-spacing: .08em; font-weight: 700; }
        h1 { margin: 4px 0 0; font-size: 22px; }
        h2 { margin: 20px 0 8px; font-size: 15px; color: #1d4ed8; border-bottom: 1px solid #dbeafe; padding-bottom: 5px; }
        h3 { margin: 8px 0 4px; font-size: 13px; }
        .muted { color: #64748b; }
        .grid { width: 100%; border-collapse: collapse; margin-top: 8px; }
        .grid td { vertical-align: top; width: 50%; padding: 6px 10px; border: 1px solid #e2e8f0; }
        .label { display: block; color: #64748b; font-size: 10px; text-transform: uppercase; font-weight: 700; }
        .value { font-weight: 600; }
        .section { page-break-inside: avoid; }
        .box { border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px; margin-top: 8px; }
        .pill { display: inline-block; padding: 2px 7px; border-radius: 999px; background: #eff6ff; color: #1d4ed8; font-size: 10px; font-weight: 700; }
        .timeline { border-left: 2px solid #bfdbfe; margin-left: 6px; padding-left: 12px; }
        .timeline-item { margin: 0 0 10px; }
        .footer { margin-top: 24px; padding-top: 10px; border-top: 1px solid #e2e8f0; font-size: 10px; color: #64748b; }
        ul { margin: 6px 0 0 18px; padding: 0; }
    </style>
</head>
<body>
@php
    $job = $application->tinTuyenDung;
    $company = $job?->congTy;
    $profile = $application->hoSo;
    $candidate = $profile?->nguoiDung;
    $rounds = $application->interviewRounds ?? collect();
    $plan = $application->onboardingPlan;
    $showOverview = $document === 'full';
    $showInterview = in_array($document, ['full', 'interview'], true);
    $showOffer = in_array($document, ['full', 'offer'], true);
    $showOnboarding = in_array($document, ['full', 'onboarding'], true);
    $statusLabel = match ((int) $application->trang_thai) {
        \App\Models\UngTuyen::TRANG_THAI_DA_XEM => 'Đã xem',
        \App\Models\UngTuyen::TRANG_THAI_DA_HEN_PHONG_VAN => 'Đã hẹn phỏng vấn',
        \App\Models\UngTuyen::TRANG_THAI_QUA_PHONG_VAN => 'Qua phỏng vấn',
        \App\Models\UngTuyen::TRANG_THAI_TRUNG_TUYEN => 'Trúng tuyển',
        \App\Models\UngTuyen::TRANG_THAI_TU_CHOI => 'Từ chối',
        default => 'Chờ duyệt',
    };
    $offerLabel = match ((int) ($application->trang_thai_offer ?? 0)) {
        \App\Models\UngTuyen::OFFER_DA_GUI => 'Đã gửi offer',
        \App\Models\UngTuyen::OFFER_DA_CHAP_NHAN => 'Đã nhận việc',
        \App\Models\UngTuyen::OFFER_TU_CHOI => 'Từ chối offer',
        default => 'Chưa gửi offer',
    };
    $fmt = fn ($value) => $value ? $value->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i') : 'Chưa cập nhật';
@endphp
<div class="page">
    <div class="header">
        <div class="eyebrow">AI Recruitment Export • {{ strtoupper($document) }}</div>
        <h1>{{ $job?->tieu_de ?? 'Hồ sơ ứng tuyển' }}</h1>
        <div class="muted">{{ $company?->ten_cong_ty ?? 'Công ty đang cập nhật' }} • Xuất lúc {{ $generatedAt->format('d/m/Y H:i') }}</div>
    </div>

    @if($showOverview)
        <div class="section">
            <h2>Tổng quan ứng tuyển</h2>
            <table class="grid">
                <tr>
                    <td><span class="label">Ứng viên</span><span class="value">{{ $candidate?->ho_ten ?: $candidate?->email ?: 'Chưa cập nhật' }}</span></td>
                    <td><span class="label">Email</span><span class="value">{{ $candidate?->email ?: 'Chưa cập nhật' }}</span></td>
                </tr>
                <tr>
                    <td><span class="label">Hồ sơ</span><span class="value">{{ $profile?->tieu_de_ho_so ?: 'Hồ sơ #' . $application->ho_so_id }}</span></td>
                    <td><span class="label">Ngày nộp</span><span class="value">{{ $fmt($application->thoi_gian_ung_tuyen) }}</span></td>
                </tr>
                <tr>
                    <td><span class="label">Trạng thái ứng tuyển</span><span class="pill">{{ $statusLabel }}</span></td>
                    <td><span class="label">Trạng thái offer</span><span class="pill">{{ $offerLabel }}</span></td>
                </tr>
            </table>
            @if($application->thu_xin_viec)
                <div class="box"><strong>Thư xin việc:</strong><br>{{ $application->thu_xin_viec }}</div>
            @endif
        </div>

        <div class="section">
            <h2>Timeline</h2>
            <div class="timeline">
                @foreach(app(\App\Services\ApplicationTimelineService::class)->build($application) as $item)
                    <div class="timeline-item">
                        <strong>{{ $item['order'] }}. {{ $item['title'] }}</strong>
                        <span class="muted"> • {{ $item['occurred_at'] ?? $item['scheduled_at'] ?? $item['due_at'] ?? 'Chưa cập nhật' }}</span><br>
                        <span>{{ $item['description'] ?? '' }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if($showInterview)
        <div class="section">
            <h2>Phỏng vấn</h2>
            @if($rounds->isEmpty() && $application->ngay_hen_phong_van)
                <div class="box">
                    <h3>Lịch phỏng vấn tổng</h3>
                    Thời gian: {{ $fmt($application->ngay_hen_phong_van) }}<br>
                    Hình thức: {{ $application->hinh_thuc_phong_van ?: 'Chưa cập nhật' }}<br>
                    Người phỏng vấn: {{ $application->nguoi_phong_van ?: 'Chưa cập nhật' }}<br>
                    Link/địa điểm: {{ $application->link_phong_van ?: 'Chưa cập nhật' }}<br>
                    Kết quả: {{ $application->ket_qua_phong_van ?: 'Chưa cập nhật' }}
                </div>
            @endif
            @forelse($rounds as $round)
                <div class="box">
                    <h3>Vòng {{ $round->thu_tu }}: {{ $round->ten_vong }}</h3>
                    Thời gian: {{ $fmt($round->ngay_hen_phong_van) }}<br>
                    Loại vòng: {{ $round->loai_vong ?: 'Chưa cập nhật' }} • Hình thức: {{ $round->hinh_thuc_phong_van ?: 'Chưa cập nhật' }}<br>
                    Người phỏng vấn: {{ $round->nguoi_phong_van ?: $round->interviewer?->ho_ten ?: 'Chưa cập nhật' }}<br>
                    Link/địa điểm: {{ $round->link_phong_van ?: 'Chưa cập nhật' }}<br>
                    Điểm/Kết quả: {{ $round->diem_so ?? 'Chưa có điểm' }} • {{ $round->ket_qua ?: 'Chưa cập nhật kết quả' }}<br>
                    @if($round->ghi_chu)<strong>Ghi chú:</strong> {{ $round->ghi_chu }}@endif
                </div>
            @empty
                @unless($application->ngay_hen_phong_van)<p class="muted">Chưa có dữ liệu phỏng vấn.</p>@endunless
            @endforelse
        </div>
    @endif

    @if($showOffer)
        <div class="section">
            <h2>Offer / Nhận việc</h2>
            <table class="grid">
                <tr>
                    <td><span class="label">Trạng thái</span><span class="value">{{ $offerLabel }}</span></td>
                    <td><span class="label">Gửi lúc</span><span class="value">{{ $fmt($application->thoi_gian_gui_offer) }}</span></td>
                </tr>
                <tr>
                    <td><span class="label">Hạn phản hồi</span><span class="value">{{ $fmt($application->han_phan_hoi_offer) }}</span></td>
                    <td><span class="label">Phản hồi lúc</span><span class="value">{{ $fmt($application->thoi_gian_phan_hoi_offer) }}</span></td>
                </tr>
            </table>
            <div class="box">{{ $application->ghi_chu_offer ?: 'Chưa có tóm tắt offer.' }}</div>
            @if($application->ghi_chu_phan_hoi_offer)<div class="box"><strong>Ghi chú phản hồi:</strong> {{ $application->ghi_chu_phan_hoi_offer }}</div>@endif
            @if($application->link_offer)<p class="muted">Tài liệu offer: {{ $application->link_offer }}</p>@endif
        </div>
    @endif

    @if($showOnboarding)
        <div class="section">
            <h2>Onboarding</h2>
            @if($plan)
                <table class="grid">
                    <tr>
                        <td><span class="label">Ngày bắt đầu</span><span class="value">{{ $plan->ngay_bat_dau?->format('d/m/Y') ?: 'Chưa cập nhật' }}</span></td>
                        <td><span class="label">Địa điểm</span><span class="value">{{ $plan->dia_diem_lam_viec ?: 'Chưa cập nhật' }}</span></td>
                    </tr>
                    <tr>
                        <td><span class="label">Trạng thái</span><span class="value">{{ $plan->trang_thai }}</span></td>
                        <td><span class="label">Tiến độ</span><span class="value">{{ $plan->progress['done'] ?? 0 }}/{{ $plan->progress['total'] ?? 0 }} checklist</span></td>
                    </tr>
                </table>
                @if($plan->loi_chao_mung)<div class="box">{{ $plan->loi_chao_mung }}</div>@endif
                @if($plan->tai_lieu_can_chuan_bi)
                    <h3>Tài liệu cần chuẩn bị</h3>
                    <ul>@foreach($plan->tai_lieu_can_chuan_bi as $doc)<li>{{ $doc }}</li>@endforeach</ul>
                @endif
                <h3>Checklist</h3>
                @forelse($plan->tasks as $task)
                    <div class="box">
                        <strong>{{ $task->tieu_de }}</strong> <span class="muted">({{ $task->nguoi_phu_trach }} • {{ $task->trang_thai }})</span><br>
                        @if($task->mo_ta){{ $task->mo_ta }}<br>@endif
                        Hạn: {{ $task->han_hoan_tat?->format('d/m/Y') ?: 'Chưa cập nhật' }}
                    </div>
                @empty
                    <p class="muted">Chưa có checklist onboarding.</p>
                @endforelse
            @else
                <p class="muted">Chưa có onboarding.</p>
            @endif
        </div>
    @endif

    <div class="footer">
        Tài liệu được tạo tự động từ hệ thống AI Recruitment. Nội dung phản ánh dữ liệu tại thời điểm export.
    </div>
</div>
</body>
</html>
