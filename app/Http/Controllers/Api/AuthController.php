<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CongTy\TaoCongTyRequest;
use App\Http\Requests\NguoiDung\DangKyRequest;
use App\Http\Requests\NguoiDung\DangNhapRequest;
use App\Http\Requests\NguoiDung\DatLaiMatKhauRequest;
use App\Http\Requests\NguoiDung\DoiMatKhauRequest;
use App\Http\Requests\NguoiDung\CapNhatHoSoRequest;
use App\Http\Requests\NguoiDung\QuenMatKhauRequest;
use App\Models\CongTy;
use App\Models\NguoiDung;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Laravel\Socialite\Facades\Socialite;

/**
 * AuthController - Xác thực & quản lý hồ sơ cá nhân
 *
 * Áp dụng cho: Admin, Nhà tuyển dụng, Ứng viên
 */
class AuthController extends Controller
{
    private function frontendUrl(): string
    {
        return rtrim((string) env('FRONTEND_URL', 'http://localhost:5173'), '/');
    }

    private function googleLoginPath(int $roleHint): string
    {
        return '/login';
    }

    private function normalizeFrontendRedirect(mixed $redirect): ?string
    {
        if (!is_string($redirect)) {
            return null;
        }

        $redirect = trim($redirect);

        if ($redirect === '' || !str_starts_with($redirect, '/') || str_starts_with($redirect, '//')) {
            return null;
        }

        return $redirect;
    }

    private function buildGoogleState(int $roleHint, ?string $redirect): string
    {
        return Crypt::encryptString(json_encode([
            'role_hint' => $roleHint,
            'redirect' => $this->normalizeFrontendRedirect($redirect),
        ], JSON_UNESCAPED_UNICODE));
    }

    private function parseGoogleState(?string $state): array
    {
        $fallback = [
            'role_hint' => NguoiDung::VAI_TRO_UNG_VIEN,
            'redirect' => null,
        ];

        if (!$state) {
            return $fallback;
        }

        try {
            $payload = json_decode(Crypt::decryptString($state), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $exception) {
            return $fallback;
        }

        $roleHint = (int) ($payload['role_hint'] ?? NguoiDung::VAI_TRO_UNG_VIEN);

        return [
            'role_hint' => in_array($roleHint, [NguoiDung::VAI_TRO_UNG_VIEN, NguoiDung::VAI_TRO_NHA_TUYEN_DUNG], true)
                ? $roleHint
                : NguoiDung::VAI_TRO_UNG_VIEN,
            'redirect' => $this->normalizeFrontendRedirect($payload['redirect'] ?? null),
        ];
    }

    private function redirectGoogleError(string $message, int $roleHint, ?string $email = null): RedirectResponse
    {
        $query = ['google_error' => $message];

        if ($email) {
            $query['email'] = $email;
        }

        return redirect($this->frontendUrl() . $this->googleLoginPath($roleHint) . '?' . http_build_query($query));
    }

    private function redirectGoogleSuccess(NguoiDung $nguoiDung, ?string $redirect = null): RedirectResponse
    {
        $nguoiDung->tokens()->delete();
        $token = $nguoiDung->createToken('auth_token')->plainTextToken;

        $query = ['token' => $token];

        if ($redirect) {
            $query['redirect'] = $redirect;
        }

        return redirect($this->frontendUrl() . '/oauth/google/callback?' . http_build_query($query));
    }

    private function mapUserData(NguoiDung $nguoiDung): array
    {
        $data = $nguoiDung->toArray();
        $data['avatar_url'] = $nguoiDung->anh_dai_dien
            ? url('/api/v1/anh-dai-dien?path=' . urlencode($nguoiDung->anh_dai_dien))
            : null;
        $data['da_xac_thuc_email'] = !is_null($nguoiDung->email_verified_at);
        $data['ten_cap_admin'] = $nguoiDung->ten_cap_admin;
        $data['quyen_admin'] = $nguoiDung->resolved_admin_permissions;

        return $data;
    }

    /**
     * POST /api/dang-ky
     * Đăng ký tài khoản mới (ứng viên hoặc nhà tuyển dụng).
     */
    public function dangKy(DangKyRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['vai_tro'] = $data['vai_tro'] ?? NguoiDung::VAI_TRO_UNG_VIEN;
        $laNhaTuyenDung = (int) $data['vai_tro'] === NguoiDung::VAI_TRO_NHA_TUYEN_DUNG;

        if ($laNhaTuyenDung) {
            Validator::make(
                [
                    'ten_cong_ty' => $data['ten_cong_ty'] ?? null,
                    'dien_thoai' => $data['so_dien_thoai'] ?? null,
                    'email' => $data['email'] ?? null,
                ],
                TaoCongTyRequest::registrationRules(),
                TaoCongTyRequest::registrationMessages(),
            )->validate();
        }

        $nguoiDung = DB::transaction(function () use ($data, $laNhaTuyenDung): NguoiDung {
            $nguoiDung = NguoiDung::create($data);

            if ($laNhaTuyenDung) {
                $congTy = CongTy::create([
                    'nguoi_dung_id' => $nguoiDung->id,
                    'ten_cong_ty' => $data['ten_cong_ty'],
                    'ma_so_thue' => 'DKNTD' . $nguoiDung->id,
                    'dien_thoai' => $data['so_dien_thoai'] ?? null,
                    'email' => $data['email'],
                    'trang_thai' => CongTy::TRANG_THAI_HOAT_DONG,
                ]);

                $congTy->thanhViens()->attach($nguoiDung->id, [
                    'vai_tro_noi_bo' => CongTy::VAI_TRO_NOI_BO_OWNER,
                    'quyen_noi_bo' => json_encode(CongTy::defaultHrPermissions()),
                    'duoc_tao_boi' => $nguoiDung->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return $nguoiDung;
        });

        dispatch(function () use ($nguoiDung): void {
            $nguoiDung->fresh()?->sendEmailVerificationNotification();
        })->afterResponse();

        return response()->json([
            'success' => true,
            'message' => 'Đăng ký tài khoản thành công. Vui lòng kiểm tra email để xác thực tài khoản trước khi đăng nhập.',
            'data' => $this->mapUserData($nguoiDung),
        ], 201);
    }

    /**
     * POST /api/dang-nhap
     * Đăng nhập - áp dụng cho tất cả vai trò.
     */
    public function dangNhap(DangNhapRequest $request): JsonResponse
    {
        $nguoiDung = NguoiDung::where('email', $request->email)->first();

        if (!$nguoiDung || !Hash::check($request->mat_khau, $nguoiDung->mat_khau)) {
            return response()->json([
                'success' => false,
                'code' => 'INVALID_CREDENTIALS',
                'message' => 'Email hoặc mật khẩu không đúng.',
            ], 401);
        }

        if (!$nguoiDung->isActive()) {
            return response()->json([
                'success' => false,
                'code' => 'ACCOUNT_LOCKED',
                'message' => 'Tài khoản đã bị khoá. Vui lòng liên hệ quản trị viên.',
                'current_role' => match ((int) $nguoiDung->vai_tro) {
                    NguoiDung::VAI_TRO_ADMIN => 'admin',
                    NguoiDung::VAI_TRO_NHA_TUYEN_DUNG => 'nha_tuyen_dung',
                    NguoiDung::VAI_TRO_UNG_VIEN => 'ung_vien',
                    default => null,
                },
                'current_role_label' => $nguoiDung->ten_vai_tro,
            ], 403);
        }

        if (!$nguoiDung->isAdmin() && !$nguoiDung->hasVerifiedEmail()) {
            return response()->json([
                'success' => false,
                'code' => 'EMAIL_NOT_VERIFIED',
                'message' => 'Tài khoản chưa xác thực email. Vui lòng kiểm tra hộp thư và xác nhận trước khi đăng nhập.',
                'data' => [
                    'requires_email_verification' => true,
                    'email' => $nguoiDung->email,
                ],
            ], 403);
        }

        // Xoá token cũ, tạo token mới
        $nguoiDung->tokens()->delete();
        $token = $nguoiDung->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Đăng nhập thành công.',
            'data' => [
                'nguoi_dung' => $this->mapUserData($nguoiDung),
                'access_token' => $token,
                'token_type' => 'Bearer',
                'vai_tro' => $nguoiDung->ten_vai_tro,
            ],
        ]);
    }

    public function redirectGoogle(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'role_hint' => ['nullable', 'integer', 'in:0,1'],
            'redirect' => ['nullable', 'string', 'max:2048'],
        ]);

        if (!config('services.google.client_id') || !config('services.google.client_secret') || !config('services.google.redirect')) {
            return $this->redirectGoogleError(
                'Hệ thống chưa cấu hình đăng nhập Google.',
                (int) ($validated['role_hint'] ?? NguoiDung::VAI_TRO_UNG_VIEN)
            );
        }

        $roleHint = (int) ($validated['role_hint'] ?? NguoiDung::VAI_TRO_UNG_VIEN);
        $redirect = $this->normalizeFrontendRedirect($validated['redirect'] ?? null);

        return Socialite::driver('google')
            ->stateless()
            ->with([
                'prompt' => 'select_account',
                'state' => $this->buildGoogleState($roleHint, $redirect),
            ])
            ->redirect();
    }

    public function callbackGoogle(Request $request): RedirectResponse
    {
        $state = $this->parseGoogleState($request->query('state'));
        $roleHint = (int) $state['role_hint'];
        $redirect = $state['redirect'];

        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
        } catch (\Throwable $exception) {
            return $this->redirectGoogleError('Không thể xác thực tài khoản Google. Vui lòng thử lại.', $roleHint);
        }

        $email = mb_strtolower(trim((string) $googleUser->getEmail()));
        $name = trim((string) ($googleUser->getName() ?: $googleUser->getNickname() ?: ''));

        if ($email === '') {
            return $this->redirectGoogleError('Tài khoản Google chưa cung cấp email hợp lệ.', $roleHint);
        }

        $nguoiDung = NguoiDung::where('email', $email)->first();

        if (!$nguoiDung) {
            if ($roleHint === NguoiDung::VAI_TRO_NHA_TUYEN_DUNG) {
                return $this->redirectGoogleError(
                    'Tài khoản Google này chưa được đăng ký dưới vai trò nhà tuyển dụng. Vui lòng đăng ký doanh nghiệp trước.',
                    $roleHint,
                    $email
                );
            }

            $nguoiDung = NguoiDung::create([
                'ho_ten' => $name !== '' ? $name : 'Người dùng Google',
                'email' => $email,
                'mat_khau' => Str::random(40),
                'vai_tro' => NguoiDung::VAI_TRO_UNG_VIEN,
                'trang_thai' => 1,
                'email_verified_at' => now(),
            ]);
        }

        if (!$nguoiDung->isActive()) {
            return $this->redirectGoogleError('Tài khoản đã bị khoá. Vui lòng liên hệ quản trị viên.', $roleHint, $email);
        }

        if ($roleHint === NguoiDung::VAI_TRO_NHA_TUYEN_DUNG && !$nguoiDung->isNhaTuyenDung()) {
            return $this->redirectGoogleError(
                'Email Google này không thuộc tài khoản nhà tuyển dụng.',
                $roleHint,
                $email
            );
        }

        if (!$nguoiDung->hasVerifiedEmail()) {
            $nguoiDung->forceFill([
                'email_verified_at' => now(),
            ])->save();
        }

        if (!$nguoiDung->ho_ten && $name !== '') {
            $nguoiDung->forceFill([
                'ho_ten' => $name,
            ])->save();
        }

        return $this->redirectGoogleSuccess($nguoiDung->fresh(), $redirect);
    }

    /**
     * POST /api/quen-mat-khau
     * Tạo token đặt lại mật khẩu cho môi trường web/app hiện tại.
     */
    public function quenMatKhau(QuenMatKhauRequest $request): JsonResponse
    {
        $data = $request->validated();

        $nguoiDung = NguoiDung::where('email', $data['email'])->first();

        if (!$nguoiDung) {
            return response()->json([
                'success' => true,
                'message' => 'Nếu email tồn tại trong hệ thống, chúng tôi đã gửi liên kết đặt lại mật khẩu.',
            ]);
        }

        if (!$nguoiDung->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Tài khoản đã bị khoá. Vui lòng liên hệ quản trị viên.',
            ], 403);
        }

        try {
            $token = Password::broker()->createToken($nguoiDung);

            dispatch(function () use ($nguoiDung, $token): void {
                $nguoiDung->fresh()?->sendPasswordResetNotification($token);
            })->afterResponse();
        } catch (\Throwable $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể gửi email đặt lại mật khẩu vào lúc này.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Nếu email tồn tại trong hệ thống, chúng tôi đã gửi liên kết đặt lại mật khẩu.',
        ]);
    }

    public function guiLaiEmailXacThuc(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $nguoiDung = NguoiDung::where('email', $data['email'])->first();

        if ($nguoiDung && $nguoiDung->isActive() && !$nguoiDung->hasVerifiedEmail()) {
            dispatch(function () use ($nguoiDung): void {
                $nguoiDung->fresh()?->sendEmailVerificationNotification();
            })->afterResponse();
        }

        return response()->json([
            'success' => true,
            'message' => 'Nếu email tồn tại và chưa xác thực, chúng tôi đã gửi lại email xác thực.',
        ]);
    }

    public function xacThucEmail(Request $request, int $id, string $hash): RedirectResponse
    {
        $nguoiDung = NguoiDung::findOrFail($id);
        $frontendUrl = rtrim((string) env('FRONTEND_URL', 'http://127.0.0.1:5173'), '/');
        $loginPath = '/login';

        if (!hash_equals((string) $hash, sha1((string) $nguoiDung->getEmailForVerification()))) {
            return redirect($frontendUrl . $loginPath . '?verified=0');
        }

        if (!$nguoiDung->hasVerifiedEmail()) {
            $nguoiDung->markEmailAsVerified();
        }

        return redirect($frontendUrl . $loginPath . '?verified=1&email=' . urlencode((string) $nguoiDung->email));
    }

    /**
     * POST /api/dang-xuat
     * Đăng xuất - thu hồi token hiện tại.
     */
    public function dangXuat(Request $request): JsonResponse
    {
        $nguoiDung = $request->user();

        if (!$nguoiDung) {
            return response()->json([
                'success' => false,
                'message' => 'Phiên đăng nhập không còn hợp lệ.',
            ], 401);
        }

        $nguoiDung->currentAccessToken()?->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đăng xuất thành công.',
        ]);
    }

    /**
     * GET /api/ho-so
     * Xem thông tin hồ sơ của người dùng đang đăng nhập.
     */
    public function hoSo(Request $request): JsonResponse
    {
        $nguoiDung = $request->user();

        if (!$nguoiDung) {
            return response()->json([
                'success' => false,
                'message' => 'Phiên đăng nhập không còn hợp lệ.',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'data' => $this->mapUserData($nguoiDung),
        ]);
    }

    /**
     * PUT /api/ho-so
     * Cập nhật hồ sơ cá nhân.
     */
    public function capNhatHoSo(CapNhatHoSoRequest $request): JsonResponse
    {
        $nguoiDung = $request->user();

        if (!$nguoiDung) {
            return response()->json([
                'success' => false,
                'message' => 'Phiên đăng nhập không còn hợp lệ.',
            ], 401);
        }

        $data = $request->validated();

        if ($request->hasFile('anh_dai_dien')) {
            if ($nguoiDung->anh_dai_dien) {
                Storage::disk('public')->delete($nguoiDung->anh_dai_dien);
            }
            $data['anh_dai_dien'] = $request->file('anh_dai_dien')
                ->store('anh_dai_dien', 'public');
        }

        $nguoiDung->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật hồ sơ thành công.',
            'data' => $this->mapUserData($nguoiDung->fresh()),
        ]);
    }

    public function avatar(Request $request)
    {
        $path = (string) $request->query('path', '');

        abort_unless(
            $path !== '' && str_starts_with($path, 'anh_dai_dien/'),
            404
        );

        abort_unless(Storage::disk('public')->exists($path), 404);

        return response()->file(Storage::disk('public')->path($path));
    }

    /**
     * POST /api/dat-lai-mat-khau
     * Đặt lại mật khẩu bằng token đã cấp.
     */
    public function datLaiMatKhau(DatLaiMatKhauRequest $request): JsonResponse
    {
        $data = $request->validated();

        $status = Password::broker()->reset(
            [
                'email' => $data['email'],
                'token' => $data['token'],
                'password' => $data['mat_khau'],
                'password_confirmation' => $data['mat_khau_confirmation'],
            ],
            function (NguoiDung $nguoiDung, string $password): void {
                $nguoiDung->forceFill([
                    'mat_khau' => $password,
                ])->save();

                $nguoiDung->tokens()->delete();

                event(new PasswordReset($nguoiDung));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return response()->json([
                'success' => false,
                'message' => 'Token đặt lại mật khẩu không hợp lệ hoặc đã hết hạn.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Đặt lại mật khẩu thành công. Vui lòng đăng nhập lại.',
        ]);
    }

    /**
     * POST /api/doi-mat-khau
     * Đổi mật khẩu - giữ phiên hiện tại, thu hồi các token khác.
     */
    public function doiMatKhau(DoiMatKhauRequest $request): JsonResponse
    {
        $nguoiDung = $request->user();

        if (!$nguoiDung) {
            return response()->json([
                'success' => false,
                'message' => 'Phiên đăng nhập không còn hợp lệ.',
            ], 401);
        }

        if (!Hash::check($request->mat_khau_cu, $nguoiDung->mat_khau)) {
            return response()->json([
                'success' => false,
                'message' => 'Mật khẩu cũ không đúng.',
            ], 422);
        }

        $currentTokenId = $nguoiDung->currentAccessToken()?->id;

        $nguoiDung->update(['mat_khau' => $request->mat_khau_moi]);

        if ($currentTokenId) {
            $nguoiDung->tokens()
                ->where('id', '!=', $currentTokenId)
                ->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Đổi mật khẩu thành công.',
        ]);
    }
}
