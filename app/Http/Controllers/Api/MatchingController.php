<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HoSo;
use App\Models\KetQuaMatching;
use App\Models\TinTuyenDung;
use App\Services\Ai\AiClientService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class MatchingController extends Controller
{
    public function __construct(
        private readonly AiClientService $aiClientService
    ) {
    }

    public function generate(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'tin_tuyen_dung_id' => ['required', 'integer', 'exists:tin_tuyen_dungs,id'],
        ]);

        $hoSo = HoSo::with('parsing')
            ->where('nguoi_dung_id', $request->user()->id)
            ->findOrFail($id);

        $tin = TinTuyenDung::with(['parsing', 'kyNangYeuCaus.kyNang'])
            ->findOrFail((int) $request->tin_tuyen_dung_id);

        try {
            $result = $this->aiClientService->matchCvJd(
                $hoSo->id,
                $tin->id,
                $this->buildCvProfile($hoSo),
                $this->buildJdProfile($tin),
                false
            );
            $data = $result['data'] ?? [];
            $matching = $this->persistMatching($hoSo, $tin, $data, $result);
        } catch (RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 502);
        }

        return response()->json([
            'success' => true,
            'message' => 'Sinh kết quả matching thành công.',
            'data' => $matching->fresh(),
        ]);
    }

    public function generateBatch(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'tin_tuyen_dung_ids' => ['required', 'array', 'min:1', 'max:12'],
            'tin_tuyen_dung_ids.*' => ['integer', 'distinct', 'exists:tin_tuyen_dungs,id'],
        ]);

        $hoSo = HoSo::with('parsing')
            ->where('nguoi_dung_id', $request->user()->id)
            ->findOrFail($id);

        $jobs = TinTuyenDung::with(['parsing', 'kyNangYeuCaus.kyNang'])
            ->whereIn('id', $validated['tin_tuyen_dung_ids'])
            ->get()
            ->keyBy('id');

        $cvProfile = $this->buildCvProfile($hoSo);
        $parallelRequests = [];

        foreach ($validated['tin_tuyen_dung_ids'] as $jobId) {
            $tin = $jobs->get((int) $jobId);
            if (!$tin) {
                continue;
            }

            $parallelRequests[(string) $tin->id] = [
                'ho_so_id' => $hoSo->id,
                'tin_tuyen_dung_id' => $tin->id,
                'cv_profile' => $cvProfile,
                'jd_profile' => $this->buildJdProfile($tin),
                'include_llm_explanation' => false,
            ];
        }

        try {
            $results = $this->aiClientService->matchCvJdParallel($parallelRequests);
        } catch (RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 502);
        }

        $matchings = [];
        foreach ($results as $requestKey => $result) {
            if (($result['success'] ?? false) !== true) {
                continue;
            }

            $tin = $jobs->get((int) $requestKey);
            if (!$tin) {
                continue;
            }

            $matchings[] = $this->persistMatching($hoSo, $tin, $result['data'] ?? [], $result)->fresh();
        }

        return response()->json([
            'success' => true,
            'message' => 'Sinh nhanh kết quả matching thành công.',
            'data' => $matchings,
        ]);
    }

    private function buildCvProfile(HoSo $hoSo): array
    {
        return [
            'tieu_de_ho_so' => $hoSo->tieu_de_ho_so,
            'trinh_do' => $hoSo->trinh_do,
            'kinh_nghiem_nam' => $hoSo->kinh_nghiem_nam,
            'raw_text' => $hoSo->parsing?->raw_text,
            'parsed_skills' => $hoSo->parsing?->parsed_skills_json ?? [],
            'parsed_experience' => $hoSo->parsing?->parsed_experience_json ?? [],
            'parsed_education' => $hoSo->parsing?->parsed_education_json ?? [],
        ];
    }

    private function buildJdProfile(TinTuyenDung $tin): array
    {
        return [
            'tieu_de' => $tin->tieu_de,
            'cap_bac' => $tin->cap_bac,
            'kinh_nghiem_yeu_cau' => $tin->kinh_nghiem_yeu_cau,
            'trinh_do_yeu_cau' => $tin->trinh_do_yeu_cau,
            'dia_diem_lam_viec' => $tin->dia_diem_lam_viec,
            'hinh_thuc_lam_viec' => $tin->hinh_thuc_lam_viec,
            'muc_luong_tu' => $tin->muc_luong_tu,
            'muc_luong_den' => $tin->muc_luong_den,
            'don_vi_luong' => $tin->don_vi_luong,
            'parsed_salary_json' => $tin->parsing?->parsed_salary_json ?? [],
            'parsed_location_json' => $tin->parsing?->parsed_location_json ?? [],
            'raw_text' => $tin->parsing?->raw_text ?? $tin->mo_ta_cong_viec,
            'parsed_skills' => $tin->parsing?->parsed_skills_json ?? [],
            'required_skills' => $tin->kyNangYeuCaus
                ->map(function ($item) {
                    return [
                        'skill_name' => $item->kyNang?->ten_ky_nang,
                        'bat_buoc' => (bool) $item->bat_buoc,
                        'trong_so' => $item->trong_so ?? 1.0,
                    ];
                })
                ->filter(fn ($item) => !empty($item['skill_name']))
                ->values()
                ->all(),
        ];
    }

    private function persistMatching(HoSo $hoSo, TinTuyenDung $tin, array $data, array $result): KetQuaMatching
    {
        return KetQuaMatching::updateOrCreate(
            [
                'ho_so_id' => $hoSo->id,
                'tin_tuyen_dung_id' => $tin->id,
                'model_version' => $data['model_version'] ?? ($result['model_version'] ?? 'matching_v1'),
            ],
            [
                'diem_phu_hop' => $data['diem_phu_hop'] ?? 0,
                'diem_ky_nang' => $data['diem_ky_nang'] ?? null,
                'diem_kinh_nghiem' => $data['diem_kinh_nghiem'] ?? null,
                'diem_hoc_van' => $data['diem_hoc_van'] ?? null,
                'chi_tiet_diem' => $data['chi_tiet_diem'] ?? null,
                'matched_skills_json' => $data['matched_skills_json'] ?? null,
                'missing_skills_json' => $data['missing_skills_json'] ?? null,
                'explanation' => $data['explanation'] ?? null,
                'danh_sach_ky_nang_thieu' => $data['danh_sach_ky_nang_thieu'] ?? null,
            ]
        );
    }
}
