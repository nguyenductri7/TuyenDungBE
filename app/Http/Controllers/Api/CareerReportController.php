<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\BillingException;
use App\Http\Controllers\Controller;
use App\Models\HoSo;
use App\Models\KetQuaMatching;
use App\Models\SuDungTinhNangAi;
use App\Models\TuVanNgheNghiep;
use App\Services\Ai\AiClientService;
use App\Services\Billing\FeatureAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class CareerReportController extends Controller
{
    public function __construct(
        private readonly AiClientService $aiClientService,
        private readonly FeatureAccessService $featureAccessService,
    ) {
    }

    public function generate(Request $request, int $id): JsonResponse
    {
        /** @var SuDungTinhNangAi|null $billingUsage */
        $billingUsage = null;

        $hoSo = HoSo::with('parsing')
            ->where('nguoi_dung_id', $request->user()->id)
            ->findOrFail($id);

        $cvProfile = $this->buildCvProfile($request, $hoSo);
        $matchingProfiles = $this->matchingProfilesFor($hoSo);

        try {
            $billingUsage = $this->featureAccessService->beginUsage(
                $request->user(),
                'career_report_generation',
                'ho_so',
                (int) $hoSo->id,
                [
                    'ho_so_id' => $hoSo->id,
                ],
                $this->resolveIdempotencyKey($request, (int) $hoSo->id),
            );

            $result = $this->aiClientService->generateCareerReport(
                $hoSo->id,
                $cvProfile,
                $matchingProfiles,
            );
            $data = $result['data'] ?? [];

            $report = TuVanNgheNghiep::create([
                'nguoi_dung_id' => $request->user()->id,
                'ho_so_id' => $hoSo->id,
                'nghe_de_xuat' => $data['nghe_de_xuat'] ?? 'Chưa xác định',
                'muc_do_phu_hop' => $data['muc_do_phu_hop'] ?? 0,
                'goi_y_ky_nang_bo_sung' => $data['goi_y_ky_nang_bo_sung'] ?? null,
                'bao_cao_chi_tiet' => $data['bao_cao_chi_tiet'] ?? null,
                'model_version' => $data['model_version'] ?? ($result['model_version'] ?? 'career_report_v2_llm'),
            ]);
            $billingUsage = $this->featureAccessService->commitUsage($billingUsage, [
                'report_id' => $report->id,
            ]);
        } catch (BillingException $e) {
            return response()->json([
                'success' => false,
                'code' => $e->errorCode,
                'message' => $e->getMessage(),
                ...$e->context,
            ], $e->status);
        } catch (RuntimeException $e) {
            if ($billingUsage) {
                $this->safeFailUsage($billingUsage, $e->getMessage());
            }

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 502);
        } catch (Throwable $e) {
            if ($billingUsage) {
                $this->safeFailUsage($billingUsage, $e->getMessage());
            }

            throw $e;
        }

        return response()->json([
            'success' => true,
            'message' => 'Sinh báo cáo tư vấn nghề nghiệp thành công.',
            'data' => $report->fresh()->toArray(),
        ]);
    }

    public function destroy(Request $request, int $reportId): JsonResponse
    {
        $report = TuVanNgheNghiep::query()
            ->where('nguoi_dung_id', $request->user()->id)
            ->findOrFail($reportId);

        $report->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa Career Report khỏi lịch sử.',
        ]);
    }

    private function buildCvProfile(Request $request, HoSo $hoSo): array
    {
        return [
            'ho_ten' => $request->user()->ho_ten,
            'tieu_de_ho_so' => $hoSo->tieu_de_ho_so,
            'muc_tieu_nghe_nghiep' => $hoSo->muc_tieu_nghe_nghiep,
            'mo_ta_ban_than' => $hoSo->mo_ta_ban_than,
            'trinh_do' => $hoSo->trinh_do,
            'kinh_nghiem_nam' => $hoSo->kinh_nghiem_nam,
            'vi_tri_ung_tuyen_muc_tieu' => $hoSo->vi_tri_ung_tuyen_muc_tieu,
            'ten_nganh_nghe_muc_tieu' => $hoSo->ten_nganh_nghe_muc_tieu,
            'builder_skills' => $hoSo->ky_nang_json ?? [],
            'builder_experience' => $hoSo->kinh_nghiem_json ?? [],
            'builder_education' => $hoSo->hoc_van_json ?? [],
            'builder_projects' => $hoSo->du_an_json ?? [],
            'builder_certificates' => $hoSo->chung_chi_json ?? [],
            'parsed_name' => $hoSo->parsing?->parsed_name,
            'parsed_skills' => $hoSo->parsing?->parsed_skills_json ?? [],
            'parsed_experience' => $hoSo->parsing?->parsed_experience_json ?? [],
            'parsed_education' => $hoSo->parsing?->parsed_education_json ?? [],
            'raw_text' => $hoSo->parsing?->raw_text,
            'profile_updated_at' => optional($hoSo->updated_at)->toISOString(),
            'parsing_updated_at' => optional($hoSo->parsing?->updated_at)->toISOString(),
        ];
    }

    private function matchingProfilesFor(HoSo $hoSo): array
    {
        return KetQuaMatching::query()
            ->where('ho_so_id', $hoSo->id)
            ->with('tinTuyenDung:id,tieu_de')
            ->orderByDesc('diem_phu_hop')
            ->limit(5)
            ->get()
            ->map(function (KetQuaMatching $item) {
                return [
                    'job_title' => $item->tinTuyenDung?->tieu_de,
                    'diem_phu_hop' => $item->diem_phu_hop,
                    'matched_skills_json' => $item->matched_skills_json,
                    'missing_skills_json' => $item->missing_skills_json,
                    'chi_tiet_diem' => $item->chi_tiet_diem,
                ];
            })
            ->values()
            ->all();
    }

    private function resolveIdempotencyKey(Request $request, int $hoSoId): string
    {
        $headerKey = trim((string) $request->header('X-Idempotency-Key', ''));
        if ($headerKey !== '') {
            return $headerKey;
        }

        return 'career-report:' . $request->user()->id . ':' . $hoSoId . ':' . Str::uuid();
    }

    private function safeFailUsage(SuDungTinhNangAi $usage, string $reason): void
    {
        try {
            $this->featureAccessService->failUsage($usage, $reason);
        } catch (Throwable) {
            // Không làm hỏng response chính nếu rollback billing gặp lỗi.
        }
    }
}
