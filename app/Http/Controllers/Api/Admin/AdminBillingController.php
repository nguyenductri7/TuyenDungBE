<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\BangGiaTinhNangAi;
use App\Models\GiaoDichThanhToan;
use App\Models\GoiDichVu;
use App\Models\GoiDichVuTinhNang;
use App\Models\NguoiDungGoiDichVu;
use App\Models\ViNguoiDung;
use App\Services\AuditLogService;
use App\Services\Billing\MomoWebhookHandlerService;
use App\Services\Billing\VnpayWebhookHandlerService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AdminBillingController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
        private readonly MomoWebhookHandlerService $momoWebhookHandler,
        private readonly VnpayWebhookHandlerService $vnpayWebhookHandler,
    ) {
    }

    public function overview(): JsonResponse
    {
        $successfulPayments = GiaoDichThanhToan::query()
            ->where('trang_thai', GiaoDichThanhToan::TRANG_THAI_THANH_CONG);

        $successfulExternalPayments = (clone $successfulPayments)
            ->whereIn('gateway', [
                GiaoDichThanhToan::GATEWAY_MOMO,
                GiaoDichThanhToan::GATEWAY_VNPAY,
            ]);

        $successfulTopUps = (clone $successfulPayments)
            ->where('loai_giao_dich', GiaoDichThanhToan::LOAI_NAP_VI);

        $successfulSubscriptions = (clone $successfulPayments)
            ->where('loai_giao_dich', GiaoDichThanhToan::LOAI_MUA_GOI);

        $monthly = $this->monthlyOverview();

        $recentPayments = GiaoDichThanhToan::query()
            ->with([
                'nguoiDung:id,ho_ten,email',
                'goiDichVu:id,ma_goi,ten_goi',
            ])
            ->latest('id')
            ->limit(8)
            ->get();

        $statusBreakdown = GiaoDichThanhToan::query()
            ->selectRaw('trang_thai, COUNT(*) as aggregate')
            ->groupBy('trang_thai')
            ->pluck('aggregate', 'trang_thai');

        $topPlans = GiaoDichThanhToan::query()
            ->selectRaw('goi_dich_vu_id, COUNT(*) as purchase_count, COALESCE(SUM(so_tien), 0) as revenue')
            ->with('goiDichVu:id,ma_goi,ten_goi')
            ->where('trang_thai', GiaoDichThanhToan::TRANG_THAI_THANH_CONG)
            ->where('loai_giao_dich', GiaoDichThanhToan::LOAI_MUA_GOI)
            ->whereNotNull('goi_dich_vu_id')
            ->groupBy('goi_dich_vu_id')
            ->orderByDesc('purchase_count')
            ->limit(5)
            ->get()
            ->map(function (GiaoDichThanhToan $payment): array {
                return [
                    'plan_id' => $payment->goi_dich_vu_id,
                    'plan_code' => $payment->goiDichVu?->ma_goi,
                    'plan_name' => $payment->goiDichVu?->ten_goi,
                    'purchase_count' => (int) $payment->purchase_count,
                    'revenue' => (int) $payment->revenue,
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'totals' => [
                    'processed_amount' => (int) ((clone $successfulExternalPayments)->sum('so_tien')),
                    'topup_amount' => (int) ((clone $successfulTopUps)->sum('so_tien')),
                    'subscription_revenue' => (int) ((clone $successfulSubscriptions)->sum('so_tien')),
                    'topup_count' => (int) ((clone $successfulTopUps)->count()),
                    'subscription_count' => (int) ((clone $successfulSubscriptions)->count()),
                    'pending_count' => (int) GiaoDichThanhToan::query()
                        ->where('trang_thai', GiaoDichThanhToan::TRANG_THAI_PENDING)
                        ->count(),
                    'active_subscription_count' => (int) NguoiDungGoiDichVu::query()
                        ->where('trang_thai', NguoiDungGoiDichVu::TRANG_THAI_HOAT_DONG)
                        ->count(),
                    'wallet_balance_amount' => (int) ViNguoiDung::query()->sum('so_du_hien_tai'),
                    'wallet_hold_amount' => (int) ViNguoiDung::query()->sum('so_du_tam_giu'),
                ],
                'status_breakdown' => [
                    'pending' => (int) ($statusBreakdown[GiaoDichThanhToan::TRANG_THAI_PENDING] ?? 0),
                    'success' => (int) ($statusBreakdown[GiaoDichThanhToan::TRANG_THAI_THANH_CONG] ?? 0),
                    'failed' => (int) ($statusBreakdown[GiaoDichThanhToan::TRANG_THAI_THAT_BAI] ?? 0),
                    'cancelled' => (int) ($statusBreakdown[GiaoDichThanhToan::TRANG_THAI_HUY] ?? 0),
                ],
                'monthly_overview' => $monthly,
                'top_plans' => $topPlans,
                'recent_payments' => $recentPayments,
            ],
        ]);
    }

    public function payments(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'loai_giao_dich' => ['nullable', 'string'],
            'trang_thai' => ['nullable', 'string'],
            'gateway' => ['nullable', 'string'],
            'q' => ['nullable', 'string', 'max:120'],
        ]);

        $search = trim((string) ($validated['q'] ?? ''));

        $payments = GiaoDichThanhToan::query()
            ->with([
                'nguoiDung:id,ho_ten,email',
                'goiDichVu:id,ma_goi,ten_goi',
            ])
            ->when(!empty($validated['loai_giao_dich']), fn ($query) => $query->where('loai_giao_dich', (string) $validated['loai_giao_dich']))
            ->when(!empty($validated['trang_thai']), fn ($query) => $query->where('trang_thai', (string) $validated['trang_thai']))
            ->when(!empty($validated['gateway']), fn ($query) => $query->where('gateway', (string) $validated['gateway']))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('ma_giao_dich_noi_bo', 'like', "%{$search}%")
                        ->orWhere('ma_yeu_cau', 'like', "%{$search}%")
                        ->orWhere('ma_giao_dich_gateway', 'like', "%{$search}%")
                        ->orWhereHas('nguoiDung', function ($userQuery) use ($search) {
                            $userQuery->where('email', 'like', "%{$search}%")
                                ->orWhere('ho_ten', 'like', "%{$search}%");
                        });
                });
            })
            ->latest('id')
            ->paginate((int) ($validated['per_page'] ?? 15));

        return response()->json([
            'success' => true,
            'data' => $payments,
        ]);
    }

    public function paymentDetail(string $maGiaoDichNoiBo): JsonResponse
    {
        $payment = GiaoDichThanhToan::query()
            ->with([
                'nguoiDung:id,ho_ten,email',
                'goiDichVu:id,ma_goi,ten_goi',
                'viNguoiDung:id,nguoi_dung_id,so_du_hien_tai,so_du_tam_giu,trang_thai',
            ])
            ->where('ma_giao_dich_noi_bo', $maGiaoDichNoiBo)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => $payment,
        ]);
    }

    public function reconcilePayment(Request $request, string $maGiaoDichNoiBo): JsonResponse
    {
        $payment = GiaoDichThanhToan::query()
            ->where('ma_giao_dich_noi_bo', $maGiaoDichNoiBo)
            ->firstOrFail();

        $before = $payment->only(['trang_thai', 'ma_giao_dich_gateway', 'paid_at']);
        try {
            if ($payment->gateway === GiaoDichThanhToan::GATEWAY_MOMO) {
                $reconciled = $this->momoWebhookHandler->reconcilePayment($payment);
            } elseif ($payment->gateway === GiaoDichThanhToan::GATEWAY_VNPAY) {
                $reconciled = $this->vnpayWebhookHandler->reconcilePayment($payment);
            } else {
                return response()->json([
                    'success' => false,
                    'code' => 'PAYMENT_RECONCILE_UNSUPPORTED_GATEWAY',
                    'message' => 'Giao dịch này không hỗ trợ đối soát thủ công.',
                ], 422);
            }
        } catch (\RuntimeException $exception) {
            return response()->json([
                'success' => false,
                'code' => strtoupper((string) $payment->gateway) . '_RECONCILE_FAILED',
                'message' => $exception->getMessage(),
            ], 502);
        }

        if (
            in_array($reconciled->gateway, [
                GiaoDichThanhToan::GATEWAY_MOMO,
                GiaoDichThanhToan::GATEWAY_VNPAY,
            ], true)
            && $reconciled->trang_thai === GiaoDichThanhToan::TRANG_THAI_PENDING
            && $reconciled->is_payment_link_expired
        ) {
            $reconciled->forceFill([
                'trang_thai' => GiaoDichThanhToan::TRANG_THAI_HUY,
            ])->save();

            $reconciled = $reconciled->fresh(['nguoiDung:id,ho_ten,email', 'goiDichVu:id,ma_goi,ten_goi']);
        }

        $message = 'Đã đối soát giao dịch với ' . strtoupper((string) $reconciled->gateway) . '.';
        $latestQuery = is_array($reconciled->raw_response_json)
            ? ($reconciled->raw_response_json['latest_query'] ?? null)
            : null;

        if (
            $reconciled->gateway === GiaoDichThanhToan::GATEWAY_VNPAY
            && is_array($latestQuery)
            && (string) ($latestQuery['vnp_ResponseCode'] ?? '') === '94'
        ) {
            $message = $reconciled->trang_thai === GiaoDichThanhToan::TRANG_THAI_HUY
                ? 'VNPay từ chối query trùng trong 5 phút, giao dịch đã quá hạn nên được chuyển sang Đã hủy.'
                : 'VNPay từ chối yêu cầu đối soát trùng trong 5 phút. Vui lòng thử lại sau hoặc chờ job nền tự xử lý.';
        }

        $this->auditLogService->logModelAction(
            actor: $request->user(),
            action: 'admin_billing_payment_reconciled',
            description: "Admin đối soát giao dịch thanh toán {$reconciled->ma_giao_dich_noi_bo}.",
            target: $reconciled,
            before: $before,
            after: $reconciled->only(['trang_thai', 'ma_giao_dich_gateway', 'paid_at']),
            metadata: [
                'scope' => 'admin_billing',
                'gateway' => $reconciled->gateway,
                'loai_giao_dich' => $reconciled->loai_giao_dich,
            ],
            request: $request,
        );

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $reconciled,
        ]);
    }

    public function plans(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => GoiDichVu::query()
                ->with('tinhNangs')
                ->withCount('nguoiDungGoiDichVus')
                ->orderByRaw("CASE WHEN is_free = 1 THEN 0 ELSE 1 END")
                ->orderBy('gia')
                ->get(),
        ]);
    }

    public function storePlan(Request $request): JsonResponse
    {
        $data = $this->validatePlan($request);
        $features = $data['features'] ?? [];
        unset($data['features']);

        $plan = DB::transaction(function () use ($data, $features) {
            $plan = GoiDichVu::query()->create($data);
            $this->syncPlanFeatures($plan, $features);

            return $plan->fresh('tinhNangs');
        });

        $this->auditLogService->logModelAction(
            actor: $request->user(),
            action: 'admin_billing_plan_created',
            description: "Admin tạo gói dịch vụ {$plan->ma_goi}.",
            target: $plan,
            after: $plan->toArray(),
            metadata: ['scope' => 'admin_billing'],
            request: $request,
        );

        return response()->json([
            'success' => true,
            'message' => 'Đã tạo gói dịch vụ.',
            'data' => $plan,
        ], 201);
    }

    public function updatePlan(Request $request, GoiDichVu $plan): JsonResponse
    {
        $data = $this->validatePlan($request, $plan);
        $features = $data['features'] ?? null;
        unset($data['features']);

        $before = $plan->load('tinhNangs')->toArray();

        $updated = DB::transaction(function () use ($plan, $data, $features) {
            $plan->update($data);
            if (is_array($features)) {
                $this->syncPlanFeatures($plan, $features);
            }

            return $plan->fresh('tinhNangs');
        });

        $this->auditLogService->logModelAction(
            actor: $request->user(),
            action: 'admin_billing_plan_updated',
            description: "Admin cập nhật gói dịch vụ {$updated->ma_goi}.",
            target: $updated,
            before: $before,
            after: $updated->toArray(),
            metadata: ['scope' => 'admin_billing'],
            request: $request,
        );

        return response()->json([
            'success' => true,
            'message' => 'Đã cập nhật gói dịch vụ.',
            'data' => $updated,
        ]);
    }

    public function prices(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => BangGiaTinhNangAi::query()
                ->orderBy('feature_code')
                ->get(),
        ]);
    }

    public function storePrice(Request $request): JsonResponse
    {
        $data = $this->validatePrice($request);
        $price = BangGiaTinhNangAi::query()->create($data);

        $this->auditLogService->logModelAction(
            actor: $request->user(),
            action: 'admin_billing_price_created',
            description: "Admin tạo bảng giá AI {$price->feature_code}.",
            target: $price,
            after: $price->toArray(),
            metadata: ['scope' => 'admin_billing'],
            request: $request,
        );

        return response()->json([
            'success' => true,
            'message' => 'Đã tạo bảng giá tính năng AI.',
            'data' => $price,
        ], 201);
    }

    public function updatePrice(Request $request, BangGiaTinhNangAi $price): JsonResponse
    {
        $before = $price->toArray();
        $price->update($this->validatePrice($request, $price));

        $this->auditLogService->logModelAction(
            actor: $request->user(),
            action: 'admin_billing_price_updated',
            description: "Admin cập nhật bảng giá AI {$price->feature_code}.",
            target: $price,
            before: $before,
            after: $price->fresh()->toArray(),
            metadata: ['scope' => 'admin_billing'],
            request: $request,
        );

        return response()->json([
            'success' => true,
            'message' => 'Đã cập nhật bảng giá tính năng AI.',
            'data' => $price->fresh(),
        ]);
    }

    public function subscriptions(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'trang_thai' => ['nullable', 'string'],
            'q' => ['nullable', 'string', 'max:120'],
        ]);

        $search = trim((string) ($validated['q'] ?? ''));

        $subscriptions = NguoiDungGoiDichVu::query()
            ->with([
                'nguoiDung:id,ho_ten,email',
                'goiDichVu:id,ma_goi,ten_goi,gia,chu_ky',
                'giaoDichThanhToan:id,ma_giao_dich_noi_bo,so_tien,trang_thai,paid_at',
            ])
            ->when(!empty($validated['trang_thai']), fn ($query) => $query->where('trang_thai', (string) $validated['trang_thai']))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->whereHas('nguoiDung', function ($userQuery) use ($search) {
                        $userQuery->where('email', 'like', "%{$search}%")
                            ->orWhere('ho_ten', 'like', "%{$search}%");
                    })->orWhereHas('goiDichVu', function ($planQuery) use ($search) {
                        $planQuery->where('ma_goi', 'like', "%{$search}%")
                            ->orWhere('ten_goi', 'like', "%{$search}%");
                    });
                });
            })
            ->latest('id')
            ->paginate((int) ($validated['per_page'] ?? 15));

        return response()->json([
            'success' => true,
            'data' => $subscriptions,
        ]);
    }

    private function monthlyOverview(): array
    {
        $startMonth = Carbon::now()->startOfMonth()->subMonths(5);
        $periodExpression = $this->monthlyPeriodExpression();
        $rows = GiaoDichThanhToan::query()
            ->selectRaw($periodExpression . ' as period')
            ->selectRaw('SUM(CASE WHEN trang_thai = ? AND loai_giao_dich = ? THEN so_tien ELSE 0 END) as topup_amount', [
                GiaoDichThanhToan::TRANG_THAI_THANH_CONG,
                GiaoDichThanhToan::LOAI_NAP_VI,
            ])
            ->selectRaw('SUM(CASE WHEN trang_thai = ? AND loai_giao_dich = ? THEN so_tien ELSE 0 END) as subscription_amount', [
                GiaoDichThanhToan::TRANG_THAI_THANH_CONG,
                GiaoDichThanhToan::LOAI_MUA_GOI,
            ])
            ->selectRaw('SUM(CASE WHEN trang_thai = ? AND loai_giao_dich = ? THEN 1 ELSE 0 END) as topup_count', [
                GiaoDichThanhToan::TRANG_THAI_THANH_CONG,
                GiaoDichThanhToan::LOAI_NAP_VI,
            ])
            ->selectRaw('SUM(CASE WHEN trang_thai = ? AND loai_giao_dich = ? THEN 1 ELSE 0 END) as subscription_count', [
                GiaoDichThanhToan::TRANG_THAI_THANH_CONG,
                GiaoDichThanhToan::LOAI_MUA_GOI,
            ])
            ->where('created_at', '>=', $startMonth)
            ->groupBy(DB::raw($periodExpression))
            ->orderBy('period')
            ->get()
            ->keyBy('period');

        return collect(range(0, 5))
            ->map(function (int $offset) use ($startMonth, $rows): array {
                $month = $startMonth->copy()->addMonths($offset);
                $period = $month->format('Y-m');
                $row = $rows->get($period);

                return [
                    'period' => $period,
                    'label' => 'T' . $month->format('n/y'),
                    'topup_amount' => (int) ($row->topup_amount ?? 0),
                    'subscription_amount' => (int) ($row->subscription_amount ?? 0),
                    'topup_count' => (int) ($row->topup_count ?? 0),
                    'subscription_count' => (int) ($row->subscription_count ?? 0),
                ];
            })
            ->values()
            ->all();
    }

    private function monthlyPeriodExpression(): string
    {
        return match (DB::connection()->getDriverName()) {
            'sqlite' => "strftime('%Y-%m', created_at)",
            default => "DATE_FORMAT(created_at, '%Y-%m')",
        };
    }

    private function validatePlan(Request $request, ?GoiDichVu $plan = null): array
    {
        return $request->validate([
            'ma_goi' => [
                'required',
                'string',
                'max:80',
                Rule::unique('goi_dich_vus', 'ma_goi')->ignore($plan?->id),
            ],
            'ten_goi' => ['required', 'string', 'max:150'],
            'mo_ta' => ['nullable', 'string', 'max:1000'],
            'gia' => ['required', 'integer', 'min:0'],
            'chu_ky' => ['required', Rule::in([GoiDichVu::CHU_KY_FREE, GoiDichVu::CHU_KY_THANG, GoiDichVu::CHU_KY_NAM])],
            'trang_thai' => ['required', Rule::in([GoiDichVu::TRANG_THAI_HOAT_DONG, GoiDichVu::TRANG_THAI_NGUNG_HOAT_DONG])],
            'is_free' => ['required', 'boolean'],
            'features' => ['nullable', 'array'],
            'features.*.feature_code' => ['required_with:features', 'string', 'max:80'],
            'features.*.quota' => ['nullable', 'integer', 'min:0'],
            'features.*.reset_cycle' => ['nullable', 'string', 'max:32'],
            'features.*.is_unlimited' => ['nullable', 'boolean'],
        ]);
    }

    private function validatePrice(Request $request, ?BangGiaTinhNangAi $price = null): array
    {
        return $request->validate([
            'feature_code' => [
                'required',
                'string',
                'max:80',
                Rule::unique('bang_gia_tinh_nang_ai', 'feature_code')->ignore($price?->id),
            ],
            'ten_hien_thi' => ['required', 'string', 'max:150'],
            'don_gia' => ['required', 'integer', 'min:0'],
            'don_vi_tinh' => ['required', 'string', 'max:50'],
            'trang_thai' => ['required', Rule::in([BangGiaTinhNangAi::TRANG_THAI_HOAT_DONG, BangGiaTinhNangAi::TRANG_THAI_TAM_NGUNG])],
        ]);
    }

    private function syncPlanFeatures(GoiDichVu $plan, array $features): void
    {
        $featureCodes = collect($features)
            ->pluck('feature_code')
            ->filter()
            ->unique()
            ->values();

        GoiDichVuTinhNang::query()
            ->where('goi_dich_vu_id', $plan->id)
            ->whereNotIn('feature_code', $featureCodes)
            ->delete();

        foreach ($features as $feature) {
            $featureCode = trim((string) ($feature['feature_code'] ?? ''));
            if ($featureCode === '') {
                continue;
            }

            GoiDichVuTinhNang::query()->updateOrCreate(
                [
                    'goi_dich_vu_id' => $plan->id,
                    'feature_code' => $featureCode,
                ],
                [
                    'quota' => !empty($feature['is_unlimited']) ? null : ($feature['quota'] ?? 0),
                    'reset_cycle' => $feature['reset_cycle'] ?? null,
                    'is_unlimited' => (bool) ($feature['is_unlimited'] ?? false),
                ],
            );
        }
    }
}
