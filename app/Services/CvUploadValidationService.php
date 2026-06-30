<?php

namespace App\Services;

use App\Services\Ai\AiClientService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class CvUploadValidationService
{
    private const INVALID_CV_MESSAGE = 'File tải lên không giống một CV/hồ sơ ứng tuyển';

    public function __construct(private readonly AiClientService $aiClientService)
    {
    }

    public function validate(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension());
        $tempPath = 'cv_upload_checks/' . Str::uuid() . '.' . $extension;

        try {
            Storage::disk('public')->putFileAs('cv_upload_checks', $file, basename($tempPath));
            $result = $this->aiClientService->parseCv(0, $tempPath);

            if (!($result['success'] ?? false)) {
                throw ValidationException::withMessages([
                    'file_cv' => [self::INVALID_CV_MESSAGE],
                ]);
            }

            $data = $result['data'] ?? [];
            if (!$this->looksLikeCv($data, (float) ($result['confidence_score'] ?? 0))) {
                throw ValidationException::withMessages([
                    'file_cv' => [self::INVALID_CV_MESSAGE],
                ]);
            }

            return $result;
        } catch (ValidationException $e) {
            throw $e;
        } catch (RuntimeException $e) {
            throw ValidationException::withMessages([
                'file_cv' => [self::INVALID_CV_MESSAGE],
            ]);
        } catch (Throwable $e) {
            throw ValidationException::withMessages([
                'file_cv' => [self::INVALID_CV_MESSAGE],
            ]);
        } finally {
            Storage::disk('public')->delete($tempPath);
        }
    }

    private function looksLikeCv(array $data, float $confidenceScore): bool
    {
        $rawText = trim((string) ($data['raw_text'] ?? ''));
        if (mb_strlen($rawText) < 120) {
            return false;
        }

        $score = 0;
        $normalized = Str::lower($this->removeVietnameseMarks($rawText));
        $hasContact = !empty($data['parsed_email']) || !empty($data['parsed_phone']);
        $cvSignalCount = 0;

        if (!empty($data['parsed_email'])) {
            $score += 2;
        }

        if (!empty($data['parsed_phone'])) {
            $score += 2;
        }

        if (!empty($data['parsed_name'])) {
            $score += 1;
        }

        if (!empty($data['parsed_skills_json'])) {
            $score += 2;
            $cvSignalCount++;
        }

        if (!empty($data['parsed_experience_json'])) {
            $score += 2;
            $cvSignalCount++;
        }

        if (!empty($data['parsed_education_json'])) {
            $score += 1;
            $cvSignalCount++;
        }

        $keywordGroups = [
            ['cv', 'resume', 'curriculum vitae', 'ho so', 'ung tuyen'],
            ['kinh nghiem', 'experience', 'work history', 'employment'],
            ['hoc van', 'education', 'university', 'college', 'truong'],
            ['ky nang', 'skills', 'technical skills', 'cong nghe'],
            ['du an', 'projects', 'certifications', 'chung chi'],
        ];

        foreach ($keywordGroups as $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($normalized, $keyword)) {
                    $score += 1;
                    $cvSignalCount++;
                    break;
                }
            }
        }

        if ($confidenceScore >= 0.65) {
            $score += 2;
        } elseif ($confidenceScore >= 0.55) {
            $score += 1;
        }

        return $score >= 5 && $cvSignalCount >= 2 && ($hasContact || !empty($data['parsed_skills_json']));
    }

    private function removeVietnameseMarks(string $value): string
    {
        $converted = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);

        return $converted !== false ? $converted : $value;
    }
}
