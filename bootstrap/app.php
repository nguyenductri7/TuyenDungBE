<?php

use App\Support\ApiErrorMessage;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Exceptions\InvalidSignatureException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        channels: __DIR__.'/../routes/channels.php',
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withBroadcasting(
        __DIR__ . '/../routes/channels.php',
        ['middleware' => ['auth:sanctum']],
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Đăng ký middleware alias cho vai trò
        $middleware->alias([
            'role' => \App\Http\Middleware\KiemTraVaiTro::class,
            'company_role' => \App\Http\Middleware\KiemTraVaiTroNoiBoCongTy::class,
            'super_admin' => \App\Http\Middleware\KiemTraCapAdmin::class,
            'admin_permission' => \App\Http\Middleware\KiemTraQuyenAdmin::class,
        ]);

        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->is('api/*')) {
                return null;
            }

            return '/login';
        });

        // Ghi chú: Hiện tại không dùng statefulApi() vì project dùng Bearer Token thuần túy
        // statefulApi() chỉ cần khi dùng cookie-based auth (SPA + Sanctum session)
        // Cấu hình Sanctum cho API
        // $middleware->statefulApi();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (InvalidSignatureException $e, Request $request) {
            $route = $request->route();
            $routeName = $route?->getName();
            $emailActionRoutes = [
                'ung-vien.ung-tuyens.confirm-interview-email' => 'interview',
                'ung-vien.ung-tuyens.interview-rounds.confirm-email' => 'interview',
                'ung-vien.ung-tuyens.confirm-offer-email' => 'offer',
            ];

            if (!isset($emailActionRoutes[$routeName])) {
                return null;
            }

            $expiresAt = $request->query('expires');
            $status = is_numeric($expiresAt) && (int) $expiresAt < now()->timestamp
                ? 'expired'
                : 'invalid';
            $applicationId = (string) ($route?->parameter('id') ?? '');
            $frontEndUrl = rtrim((string) env('FRONTEND_URL', 'http://localhost:5173'), '/');

            return redirect($frontEndUrl . '/application-action-result'
                . '?type=' . urlencode($emailActionRoutes[$routeName])
                . '&status=' . urlencode($status)
                . '&application_id=' . urlencode($applicationId));
        });

        // Trả về JSON thay vì HTML khi request là API
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'code' => 'AUTH_UNAUTHENTICATED',
                    'message' => 'Phiên đăng nhập đã hết hạn hoặc chưa hợp lệ. Vui lòng đăng nhập lại.',
                ], 401);
            }
        });

        $exceptions->render(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'code' => 'RESOURCE_NOT_FOUND',
                    'message' => 'Không tìm thấy dữ liệu yêu cầu.',
                ], 404);
            }
        });

        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'code' => 'VALIDATION_FAILED',
                    'message' => 'Dữ liệu không hợp lệ.',
                    'errors' => $e->errors(),
                ], 422);
            }
        });

        $exceptions->render(function (\Illuminate\Auth\Access\AuthorizationException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'code' => 'FORBIDDEN',
                    'message' => $e->getMessage() ?: 'Bạn không có quyền thực hiện thao tác này.',
                ], 403);
            }
        });

        $exceptions->render(function (\Throwable $e, Request $request) {
            if (!($request->expectsJson() || $request->is('api/*'))) {
                return null;
            }

            $status = method_exists($e, 'getStatusCode')
                ? (int) $e->getStatusCode()
                : 500;

            if ($status < 400 || $status > 599) {
                $status = 500;
            }

            $isServerError = $status >= 500;
            $message = ApiErrorMessage::fromThrowable($e, $status);

            return response()->json([
                'success' => false,
                'code' => $isServerError ? 'SERVER_ERROR' : 'REQUEST_ERROR',
                'message' => $message,
                'details' => null,
            ], $status);
        });
    })->create();
