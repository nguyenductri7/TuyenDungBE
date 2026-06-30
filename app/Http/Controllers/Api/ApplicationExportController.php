<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesEmployerCompany;
use App\Http\Controllers\Controller;
use App\Models\UngTuyen;
use App\Services\ApplicationExportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApplicationExportController extends Controller
{
    use ResolvesEmployerCompany;

    public function __construct(private readonly ApplicationExportService $exportService)
    {
    }

    public function candidate(Request $request, int $id, string $document): Response
    {
        $application = UngTuyen::query()
            ->whereKey($id)
            ->whereHas('hoSo', fn ($query) => $query->withTrashed()->where('nguoi_dung_id', $request->user()->id))
            ->firstOrFail();

        return $this->exportService->download($application, $document, 'candidate');
    }

    public function employer(Request $request, int $id, string $document): Response
    {
        $company = $this->getCurrentEmployerCompany();
        abort_unless($company, 403, 'Bạn cần tạo hoặc tham gia công ty trước khi export hồ sơ.');

        $application = UngTuyen::query()
            ->whereKey($id)
            ->whereHas('tinTuyenDung', fn ($query) => $query->where('cong_ty_id', $company->id))
            ->with('tinTuyenDung:id,cong_ty_id,hr_phu_trach_id')
            ->firstOrFail();

        $this->abortIfCannotManageApplicationRecord($request->user(), $company, $application);

        return $this->exportService->download($application, $document, 'employer');
    }
}
