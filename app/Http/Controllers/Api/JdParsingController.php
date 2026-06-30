<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesEmployerCompany;
use App\Http\Controllers\Controller;
use App\Models\KyNang;
use App\Models\TinTuyenDung;
use App\Models\TinTuyenDungKyNang;
use App\Models\TinTuyenDungParsing;
use App\Services\Ai\AiClientService;
use App\Support\EncodedId;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class JdParsingController extends Controller
{
    use ResolvesEmployerCompany;

    public function __construct(
        private readonly AiClientService $aiClientService
    ) {
    }

    public function parse(string $id): JsonResponse
    {
        $decodedId = EncodedId::decodeOrFail($id);
        $congTy = $this->getCurrentEmployerCompany();
        $congTyId = $congTy?->id;

        if (!$congTyId) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn chưa có công ty để thao tác JD.',
            ], 403);
        }

        $tin = TinTuyenDung::where('cong_ty_id', $congTyId)->findOrFail($decodedId);
        $this->abortIfCannotManageJobRecord($this->getAuthenticatedEmployer(), $congTy, $tin);

        try {
            $result = $this->aiClientService->parseJd($tin->id, (string) $tin->mo_ta_cong_viec);
            $data = $result['data'] ?? [];

            $parsing = TinTuyenDungParsing::updateOrCreate(
                ['tin_tuyen_dung_id' => $tin->id],
                [
                    'raw_text' => $data['raw_text'] ?? $tin->mo_ta_cong_viec,
                    'parsed_skills_json' => $data['parsed_skills_json'] ?? null,
                    'parsed_requirements_json' => $data['parsed_requirements_json'] ?? null,
                    'parsed_benefits_json' => $data['parsed_benefits_json'] ?? null,
                    'parsed_salary_json' => $data['parsed_salary_json'] ?? null,
                    'parsed_location_json' => $data['parsed_location_json'] ?? null,
                    'parse_status' => ($result['success'] ?? false) ? 1 : 2,
                    'parser_version' => $result['parser_version'] ?? null,
                    'confidence_score' => $result['confidence_score'] ?? null,
                    'error_message' => $result['error'] ?? null,
                ]
            );

            if (isset($data['parsed_skills_json']) && is_array($data['parsed_skills_json'])) {
                TinTuyenDungKyNang::where('tin_tuyen_dung_id', $tin->id)
                    ->where('nguon_du_lieu', 'jd_parser')
                    ->delete();

                foreach ($data['parsed_skills_json'] as $skill) {
                    if (!is_array($skill)) {
                        continue;
                    }

                    $kyNangId = $skill['ky_nang_id'] ?? null;

                    if (!$kyNangId && !empty($skill['skill_name'])) {
                        $kyNangId = KyNang::query()
                            ->whereRaw('LOWER(ten_ky_nang) = ?', [mb_strtolower((string) $skill['skill_name'])])
                            ->value('id');
                    }

                    if (!$kyNangId) {
                        continue;
                    }

                    TinTuyenDungKyNang::updateOrCreate(
                        [
                            'tin_tuyen_dung_id' => $tin->id,
                            'ky_nang_id' => $kyNangId,
                        ],
                        [
                            'muc_do_yeu_cau' => $skill['muc_do_yeu_cau'] ?? null,
                            'bat_buoc' => (bool) ($skill['bat_buoc'] ?? false),
                            'trong_so' => $skill['trong_so'] ?? null,
                            'nguon_du_lieu' => 'jd_parser',
                            'do_tin_cay' => $skill['do_tin_cay'] ?? null,
                        ]
                    );
                }
            }
        } catch (RuntimeException $e) {
            TinTuyenDungParsing::updateOrCreate(
                ['tin_tuyen_dung_id' => $tin->id],
                [
                    'parse_status' => 2,
                    'error_message' => $e->getMessage(),
                ]
            );

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 502);
        }

        return response()->json([
            'success' => true,
            'message' => 'Phân tích JD thành công.',
            'data' => [
                ...$parsing->toArray(),
                'parsed_work_mode' => $data['parsed_work_mode'] ?? null,
                'suggested_skills_json' => $data['suggested_skills_json'] ?? [],
                'quality_warnings_json' => $data['quality_warnings_json'] ?? [],
                'missing_fields_json' => $data['missing_fields_json'] ?? [],
                'review_required' => (bool) ($data['review_required'] ?? false),
            ],
        ]);
    }
}
