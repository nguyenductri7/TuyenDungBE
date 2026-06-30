<?php

namespace App\Http\Middleware;

use App\Models\NguoiDung;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class KiemTraCapAdmin
{
    private const CAP_LABELS = [
        NguoiDung::CAP_ADMIN_SUPER_ADMIN => 'Super Admin',
        NguoiDung::CAP_ADMIN_ADMIN => 'Admin',
    ];

    private function errorResponse(string $code, string $message, int $status, array $extra = []): JsonResponse
    {
        return response()->json([
            'success' => false,
            'code' => $code,
            'message' => $message,
            ...$extra,
        ], $status);
    }

    public function handle(Request $request, Closure $next, string ...$caps): Response
    {
        $nguoiDung = $request->user();

        if (!$nguoiDung) {
            return $this->errorResponse(
                'AUTH_UNAUTHENTICATED',
                'Vui lòng đăng nhập để tiếp tục.',
                401,
            );
        }

        if (!$nguoiDung->isAdmin()) {
            return $this->errorResponse(
                'ROLE_FORBIDDEN',
                'Chức năng này chỉ dành cho quản trị viên hệ thống.',
                403,
            );
        }

        $requiredCaps = array_values(array_filter($caps));
        if ($requiredCaps === []) {
            $requiredCaps = [NguoiDung::CAP_ADMIN_SUPER_ADMIN];
        }

        if (in_array($nguoiDung->cap_admin, $requiredCaps, true)) {
            return $next($request);
        }

        return $this->errorResponse(
            'ADMIN_SCOPE_FORBIDDEN',
            'Tài khoản admin hiện tại không đủ quyền để truy cập chức năng này.',
            403,
            [
                'required_admin_scopes' => $requiredCaps,
                'required_admin_scope_labels' => array_map(
                    fn (string $cap) => self::CAP_LABELS[$cap] ?? $cap,
                    $requiredCaps,
                ),
                'current_admin_scope' => $nguoiDung->cap_admin,
                'current_admin_scope_label' => self::CAP_LABELS[$nguoiDung->cap_admin ?? ''] ?? null,
            ],
        );
    }
}
