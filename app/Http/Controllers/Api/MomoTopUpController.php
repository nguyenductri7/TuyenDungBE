<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\BillingException;
use App\Http\Controllers\Controller;
use App\Models\GiaoDichThanhToan;
use App\Services\Billing\Contracts\PaymentGatewayInterface;
use App\Services\Billing\MomoWebhookHandlerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MomoTopUpController extends Controller
{
    public function __construct(
        private readonly PaymentGatewayInterface $paymentGateway,
        private readonly MomoWebhookHandlerService $webhookHandler,
    ) {
    }

    public function create(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'so_tien' => ['required', 'integer', 'min:1000'],
        ]);

        try {
            [$payment, $gatewayResponse] = $this->paymentGateway->createTopUpPayment(
                $request->user(),
                (int) $validated['so_tien'],
            );
        } catch (BillingException $exception) {
            return response()->json([
                'success' => false,
                'code' => $exception->errorCode,
                'message' => $exception->getMessage(),
                ...$exception->context,
            ], $exception->status);
        } catch (\RuntimeException $exception) {
            return response()->json([
                'success' => false,
                'code' => 'MOMO_CREATE_PAYMENT_FAILED',
                'message' => $exception->getMessage(),
            ], 502);
        }

        return response()->json([
            'success' => true,
            'message' => 'Đã tạo giao dịch nạp tiền qua MoMo.',
            'data' => [
                'payment' => $payment,
                'pay_url' => $payment->redirect_url,
                'gateway_response' => $gatewayResponse,
            ],
        ], 201);
    }

    public function show(Request $request, string $maGiaoDichNoiBo): JsonResponse
    {
        $payment = GiaoDichThanhToan::query()
            ->where('nguoi_dung_id', $request->user()->id)
            ->where('ma_giao_dich_noi_bo', $maGiaoDichNoiBo)
            ->whereIn('gateway', [
                GiaoDichThanhToan::GATEWAY_MOMO,
                GiaoDichThanhToan::GATEWAY_VNPAY,
            ])
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => $payment,
        ]);
    }

    private function walletPathForPayment(?GiaoDichThanhToan $payment): string
    {
        $payment?->loadMissing('nguoiDung:id,vai_tro');

        return $payment?->nguoiDung?->isNhaTuyenDung()
            ? '/employer/billing'
            : '/wallet';
    }

    public function handleReturn(Request $request): RedirectResponse
    {
        $payload = $request->query();
        $autoCompleteEnabled = (bool) config('services.momo.auto_complete_return_local', false);
        $payment = $this->webhookHandler->recordReturnPayload((string) ($payload['orderId'] ?? ''), $payload);
        $payment = $this->webhookHandler->autoCompleteFromReturnForLocal(
            (string) ($payload['orderId'] ?? ''),
            $payload,
        ) ?? $payment;
        $frontendBaseUrl = rtrim((string) config('app.frontend_url', 'http://localhost:5173'), '/');
        $paymentCode = $payment?->ma_giao_dich_noi_bo ?: (string) ($payload['orderId'] ?? '');
        $query = array_filter([
            'resultCode' => isset($payload['resultCode']) ? (string) $payload['resultCode'] : null,
            'message' => isset($payload['message']) ? (string) $payload['message'] : null,
            'orderId' => $paymentCode !== '' ? $paymentCode : null,
        ], static fn ($value) => $value !== null && $value !== '');

        $redirectToWallet = $autoCompleteEnabled
            && $payment?->trang_thai === GiaoDichThanhToan::TRANG_THAI_THANH_CONG
            && ((int) ($payload['resultCode'] ?? -1) === 0);

        if ($redirectToWallet && $payment?->loai_giao_dich === GiaoDichThanhToan::LOAI_NAP_VI) {
            $walletQuery = array_filter([
                'topup' => 'success',
                'orderId' => $paymentCode !== '' ? $paymentCode : null,
                'message' => 'Nạp tiền thành công',
            ], static fn ($value) => $value !== null && $value !== '');

            $targetUrl = $frontendBaseUrl . $this->walletPathForPayment($payment);
            if ($walletQuery !== []) {
                $targetUrl .= '?' . http_build_query($walletQuery);
            }
        } elseif ($payment?->loai_giao_dich === GiaoDichThanhToan::LOAI_MUA_GOI) {
            $subscriptionQuery = array_filter([
                'subscription' => $redirectToWallet ? 'success' : (((int) ($payload['resultCode'] ?? -1) === 0) ? 'pending' : 'failed'),
                'plan' => $payment?->goiDichVu?->ma_goi,
                'orderId' => $paymentCode !== '' ? $paymentCode : null,
                'message' => isset($payload['message']) ? (string) $payload['message'] : null,
            ], static fn ($value) => $value !== null && $value !== '');

            $targetUrl = $frontendBaseUrl . '/plans';
            if ($subscriptionQuery !== []) {
                $targetUrl .= '?' . http_build_query($subscriptionQuery);
            }
        } elseif ($payment?->nguoiDung?->isNhaTuyenDung() && $payment?->loai_giao_dich === GiaoDichThanhToan::LOAI_NAP_VI) {
            $targetUrl = $frontendBaseUrl . '/employer/billing/payment-result/' . rawurlencode($paymentCode !== '' ? $paymentCode : 'unknown');
            if ($query !== []) {
                $targetUrl .= '?' . http_build_query($query);
            }
        } else {
            $targetUrl = $frontendBaseUrl . '/wallet/payment-result/' . rawurlencode($paymentCode !== '' ? $paymentCode : 'unknown');
            if ($query !== []) {
                $targetUrl .= '?' . http_build_query($query);
            }
        }

        return redirect()->away($targetUrl);
    }

    public function handleIpn(Request $request)
    {
        $this->webhookHandler->handleIpn($request->all());

        return response()->noContent();
    }
}
