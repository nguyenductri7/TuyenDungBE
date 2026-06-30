<?php

namespace App\Http\Middleware;

use App\Models\NguoiDung;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class KiemTraQuyenAdmin
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        /** @var NguoiDung|null $nguoiDung */
        $nguoiDung = $request->user();

        if (!$nguoiDung || !$nguoiDung->isAdmin()) {
            return response()->json([
                'success' => false,
                'code' => 'ADMIN_PERMISSION_DENIED',
                'message' => 'Bạn không có quyền truy cập chức năng quản trị này.',
            ], 403);
        }

        if ($nguoiDung->isSuperAdmin()) {
            return $next($request);
        }

        $allowedKeys = NguoiDung::adminPermissionKeys();
        $requiredPermissions = array_values(array_intersect($permissions, $allowedKeys));

        if (!$requiredPermissions || !$nguoiDung->hasAdminPermission(...$requiredPermissions)) {
            $labelMap = NguoiDung::adminPermissionLabelMap();

            return response()->json([
                'success' => false,
                'code' => 'ADMIN_PERMISSION_DENIED',
                'message' => 'Tài khoản admin hiện tại chưa được cấp quyền cho chức năng này.',
                'required_admin_permissions' => $requiredPermissions,
                'required_admin_permission_labels' => array_values(array_map(
                    fn (string $permission): string => $labelMap[$permission] ?? $permission,
                    $requiredPermissions
                )),
                'current_admin_permissions' => $nguoiDung->resolved_admin_permissions,
            ], 403);
        }

        return $next($request);
    }
}
