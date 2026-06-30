<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BangGiaTinhNangAi;
use App\Models\GiaoDichThanhToan;
use App\Services\Billing\BillingEntitlementService;
use App\Services\Billing\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function __construct(
        private readonly WalletService $walletService,
        private readonly BillingEntitlementService $billingEntitlementService,
    ) {
    }

    public function show(Request $request): JsonResponse
    {
        $wallet = $this->walletService->getOrCreateWallet($request->user());

        return response()->json([
            'success' => true,
            'data' => [
                'wallet' => $wallet,
            ],
        ]);
    }

    public function transactions(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $wallet = $this->walletService->getOrCreateWallet($request->user());
        $transactions = $wallet->bienDongVis()
            ->latest('id')
            ->paginate((int) ($validated['per_page'] ?? 20));

        return response()->json([
            'success' => true,
            'data' => $transactions,
        ]);
    }

    public function pricing(Request $request): JsonResponse
    {
        $featureCodes = $this->billingEntitlementService->featureCodesForUser($request->user());
        $prices = BangGiaTinhNangAi::query()
            ->where('trang_thai', BangGiaTinhNangAi::TRANG_THAI_HOAT_DONG)
            ->when(
                $featureCodes->isNotEmpty(),
                fn ($query) => $query->whereIn('feature_code', $featureCodes->all())
            )
            ->orderBy('feature_code')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $prices,
        ]);
    }

    public function entitlements(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->billingEntitlementService->forUser($request->user()),
        ]);
    }

    public function payments(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'loai_giao_dich' => ['nullable', 'string'],
            'trang_thai' => ['nullable', 'string'],
        ]);

        $payments = GiaoDichThanhToan::query()
            ->with('goiDichVu:id,ma_goi,ten_goi')
            ->where('nguoi_dung_id', $request->user()->id)
            ->when(
                !empty($validated['loai_giao_dich']),
                fn ($query) => $query->where('loai_giao_dich', (string) $validated['loai_giao_dich'])
            )
            ->when(
                !empty($validated['trang_thai']),
                fn ($query) => $query->where('trang_thai', (string) $validated['trang_thai'])
            )
            ->latest('id')
            ->paginate((int) ($validated['per_page'] ?? 10));

        return response()->json([
            'success' => true,
            'data' => $payments,
        ]);
    }

    public function paymentDetail(Request $request, string $maGiaoDichNoiBo): JsonResponse
    {
        $payment = GiaoDichThanhToan::query()
            ->with([
                'goiDichVu:id,ma_goi,ten_goi',
                'viNguoiDung:id,nguoi_dung_id,so_du_hien_tai,so_du_tam_giu',
            ])
            ->where('nguoi_dung_id', $request->user()->id)
            ->where('ma_giao_dich_noi_bo', $maGiaoDichNoiBo)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => $payment,
        ]);
    }
}
