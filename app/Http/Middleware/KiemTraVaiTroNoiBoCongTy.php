<?php

namespace App\Http\Middleware;

use App\Models\CongTy;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class KiemTraVaiTroNoiBoCongTy
{
    private function errorResponse(
        string $code,
        string $message,
        int $status,
        array $extra = [],
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'code' => $code,
            'message' => $message,
            ...$extra,
        ], $status);
    }

    private function roleLabels(array $roles, ?CongTy $congTy = null): array
    {
        return collect($roles)
            ->map(fn (string $role) => CongTy::nhanVaiTroNoiBo($role, $congTy))
            ->values()
            ->all();
    }

    public function handle(Request $request, Closure $next, string ...$vaiTrosNoiBo): Response
    {
        $explicitPermissions = collect($vaiTrosNoiBo)
            ->filter(fn (string $value) => str_starts_with($value, 'permission:'))
            ->map(fn (string $value) => substr($value, strlen('permission:')))
            ->filter()
            ->values()
            ->all();
        $vaiTrosNoiBo = collect($vaiTrosNoiBo)
            ->reject(fn (string $value) => str_starts_with($value, 'permission:'))
            ->map(fn (string $value) => CongTy::normalizeVaiTroNoiBo($value))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $nguoiDung = $request->user();

        if (!$nguoiDung) {
            return $this->errorResponse(
                'AUTH_UNAUTHENTICATED',
                'Vui lòng đăng nhập để tiếp tục.',
                401,
            );
        }

        $congTy = $nguoiDung->congTyHienTai();

        if (!$congTy) {
            return $this->errorResponse(
                'COMPANY_REQUIRED',
                'Bạn cần tạo hoặc tham gia công ty trước khi sử dụng chức năng này.',
                403,
                [
                    'current_role' => 'nha_tuyen_dung',
                    'required_company' => true,
                ],
            );
        }

        $vaiTroNoiBo = $nguoiDung->layVaiTroNoiBoCongTy($congTy);

        if (!$vaiTroNoiBo || !in_array($vaiTroNoiBo, CongTy::danhSachVaiTroNoiBo($congTy), true)) {
            return $this->errorResponse(
                'COMPANY_ROLE_MISSING',
                'Bạn không thuộc nhóm HR nội bộ của công ty này.',
                403,
                [
                    'company_id' => $congTy->id,
                    'current_company_role' => $vaiTroNoiBo,
                    'current_company_role_label' => CongTy::nhanVaiTroNoiBo($vaiTroNoiBo, $congTy),
                ],
            );
        }

        if (!$vaiTrosNoiBo && !$explicitPermissions) {
            return $next($request);
        }

        $hasAccess = $explicitPermissions
            ? $nguoiDung->coQuyenNoiBoCongTy($explicitPermissions, $congTy)
            : $nguoiDung->coVaiTroNoiBoCongTy($vaiTrosNoiBo, $congTy);

        if (!$hasAccess) {
            return $this->errorResponse(
                'COMPANY_ROLE_FORBIDDEN',
                'Vai trò nội bộ hiện tại không đủ quyền thực hiện thao tác này.',
                403,
                [
                    'company_id' => $congTy->id,
                    'required_company_roles' => $vaiTrosNoiBo,
                    'required_company_role_labels' => $this->roleLabels($vaiTrosNoiBo, $congTy),
                    'required_company_permissions' => $explicitPermissions,
                    'current_company_role' => $vaiTroNoiBo,
                    'current_company_role_label' => CongTy::nhanVaiTroNoiBo($vaiTroNoiBo, $congTy),
                ],
            );
        }

        return $next($request);
    }
}
