<?php

namespace App\Http\Middleware;

use App\Models\NguoiDung;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware kiểm tra quyền truy cập theo vai trò.
 *
 * Cách dùng trong routes:
 *   ->middleware('role:admin')
 *   ->middleware('role:nha_tuyen_dung')
 *   ->middleware('role:admin,nha_tuyen_dung')   // Cho phép nhiều vai trò
 */
class KiemTraVaiTro
{
    private const ROLE_MAP = [
        'admin' => NguoiDung::VAI_TRO_ADMIN,
        'nha_tuyen_dung' => NguoiDung::VAI_TRO_NHA_TUYEN_DUNG,
        'ung_vien' => NguoiDung::VAI_TRO_UNG_VIEN,
    ];

    private const ROLE_LABELS = [
        'admin' => 'Admin',
        'nha_tuyen_dung' => 'Nhà tuyển dụng',
        'ung_vien' => 'Ứng viên',
    ];

    private const ROLE_AREA_LABELS = [
        'admin' => 'khu vực quản trị',
        'nha_tuyen_dung' => 'khu vực nhà tuyển dụng',
        'ung_vien' => 'khu vực ứng viên',
    ];

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

    private function roleKey(?int $role): ?string
    {
        return match ($role) {
            NguoiDung::VAI_TRO_ADMIN => 'admin',
            NguoiDung::VAI_TRO_NHA_TUYEN_DUNG => 'nha_tuyen_dung',
            NguoiDung::VAI_TRO_UNG_VIEN => 'ung_vien',
            default => null,
        };
    }

    private function roleLabel(?string $roleKey): string
    {
        return self::ROLE_LABELS[$roleKey ?? ''] ?? 'Không xác định';
    }

    private function forbiddenMessage(?string $currentRole, array $requiredRoles): string
    {
        if (count($requiredRoles) === 1) {
            $requiredRole = $requiredRoles[0];
            $area = self::ROLE_AREA_LABELS[$requiredRole] ?? 'chức năng này';

            return "Tài khoản {$this->roleLabel($currentRole)} không có quyền truy cập {$area}.";
        }

        $requiredLabels = collect($requiredRoles)
            ->map(fn (string $role) => $this->roleLabel($role))
            ->implode(', ');

        return "Tài khoản {$this->roleLabel($currentRole)} không đủ quyền. Chức năng này chỉ dành cho: {$requiredLabels}.";
    }

    public function handle(Request $request, Closure $next, string ...$vaiTros): Response
    {
        $nguoiDung = $request->user();

        if (!$nguoiDung) {
            return $this->errorResponse(
                'AUTH_UNAUTHENTICATED',
                'Vui lòng đăng nhập để tiếp tục.',
                401,
            );
        }

        if (!$nguoiDung->isActive()) {
            return $this->errorResponse(
                'ACCOUNT_LOCKED',
                'Tài khoản đã bị khoá. Vui lòng liên hệ quản trị viên.',
                403,
                [
                    'current_role' => $this->roleKey((int) $nguoiDung->vai_tro),
                    'current_role_label' => $this->roleLabel($this->roleKey((int) $nguoiDung->vai_tro)),
                ],
            );
        }

        foreach ($vaiTros as $vaiTro) {
            if (isset(self::ROLE_MAP[$vaiTro]) && $nguoiDung->vai_tro === self::ROLE_MAP[$vaiTro]) {
                return $next($request);
            }
        }

        $requiredRoles = array_values(array_filter($vaiTros, fn (string $role) => isset(self::ROLE_MAP[$role])));
        $currentRole = $this->roleKey((int) $nguoiDung->vai_tro);

        return $this->errorResponse(
            'ROLE_FORBIDDEN',
            $this->forbiddenMessage($currentRole, $requiredRoles),
            403,
            [
                'required_roles' => $requiredRoles,
                'required_role_labels' => collect($requiredRoles)
                    ->map(fn (string $role) => $this->roleLabel($role))
                    ->values()
                    ->all(),
                'current_role' => $currentRole,
                'current_role_label' => $this->roleLabel($currentRole),
            ],
        );
    }
}
