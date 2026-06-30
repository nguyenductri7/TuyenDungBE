<?php

namespace App\Services;

use App\Models\UngTuyen;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class ApplicationExportService
{
    public const DOCUMENT_FULL = 'full';
    public const DOCUMENT_OFFER = 'offer';
    public const DOCUMENT_INTERVIEW = 'interview';
    public const DOCUMENT_ONBOARDING = 'onboarding';

    public const DOCUMENT_TYPES = [
        self::DOCUMENT_FULL,
        self::DOCUMENT_OFFER,
        self::DOCUMENT_INTERVIEW,
        self::DOCUMENT_ONBOARDING,
    ];

    public function download(UngTuyen $application, string $document, string $scope): Response
    {
        $document = $this->normalizeDocument($document);
        $this->assertDocumentAvailable($application, $document);

        $application->loadMissing([
            'tinTuyenDung.congTy',
            'tinTuyenDung.hrPhuTrach:id,ho_ten,email',
            'hoSo.nguoiDung:id,ho_ten,email,so_dien_thoai',
            'hrPhuTrach:id,ho_ten,email',
            'interviewRounds.interviewer:id,ho_ten,email',
            'onboardingPlan.tasks.completedBy:id,ho_ten,email',
            'onboardingPlan.hrPhuTrach:id,ho_ten,email',
        ]);

        $pdf = Pdf::loadView('exports.application', [
            'application' => $application,
            'document' => $document,
            'scope' => $scope,
            'generatedAt' => now('Asia/Ho_Chi_Minh'),
        ])->setPaper('a4')->setOptions([
            'defaultFont' => 'DejaVu Sans',
            'isRemoteEnabled' => false,
            'isHtml5ParserEnabled' => true,
        ]);

        return $pdf->download($this->filename($application, $document));
    }

    public function normalizeDocument(string $document): string
    {
        $document = strtolower(trim($document));

        abort_unless(in_array($document, self::DOCUMENT_TYPES, true), 404, 'Loại tài liệu export không hợp lệ.');

        return $document;
    }

    private function assertDocumentAvailable(UngTuyen $application, string $document): void
    {
        if ($document === self::DOCUMENT_OFFER) {
            abort_if((int) ($application->trang_thai_offer ?? UngTuyen::OFFER_CHUA_GUI) === UngTuyen::OFFER_CHUA_GUI, 422, 'Đơn ứng tuyển này chưa có offer để export.');
        }

        if ($document === self::DOCUMENT_INTERVIEW) {
            $hasInterviewData = $application->ngay_hen_phong_van
                || $application->interviewRounds()->exists()
                || $application->rubric_danh_gia_phong_van
                || $application->ket_qua_phong_van;

            abort_unless($hasInterviewData, 422, 'Đơn ứng tuyển này chưa có dữ liệu phỏng vấn để export.');
        }

        if ($document === self::DOCUMENT_ONBOARDING) {
            abort_unless($application->onboardingPlan()->exists(), 422, 'Đơn ứng tuyển này chưa có onboarding để export.');
        }
    }

    private function filename(UngTuyen $application, string $document): string
    {
        $prefix = match ($document) {
            self::DOCUMENT_OFFER => 'offer',
            self::DOCUMENT_INTERVIEW => 'interview-report',
            self::DOCUMENT_ONBOARDING => 'onboarding-checklist',
            default => 'application-dossier',
        };

        return "{$prefix}-{$application->id}-" . now('Asia/Ho_Chi_Minh')->format('Ymd-His') . '.pdf';
    }
}
