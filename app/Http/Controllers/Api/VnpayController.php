<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\BillingException;
use App\Http\Controllers\Controller;
use App\Models\GiaoDichThanhToan;
use App\Models\GoiDichVu;
use App\Services\Billing\VnpayGatewayService;
use App\Services\Billing\VnpayWebhookHandlerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VnpayController extends Controller
{
    public function __construct(
        private readonly VnpayGatewayService $vnpayGatewayService,
        private readonly VnpayWebhookHandlerService $webhookHandler,
    ) {
    }

    public function createTopUp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'so_tien' => ['required', 'integer', 'min:1000'],
            'bank_code' => ['nullable', 'string', 'max:20'],
        ]);

        try {
            [$payment, $gatewayResponse] = $this->vnpayGatewayService->createTopUpPayment(
                $request->user(),
                (int) $validated['so_tien'],
                $validated['bank_code'] ?? null,
                $request->ip(),
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
                'code' => 'VNPAY_CREATE_PAYMENT_FAILED',
                'message' => $exception->getMessage(),
            ], 502);
        }

        return response()->json([
            'success' => true,
            'message' => 'Đã tạo giao dịch nạp tiền qua VNPay.',
            'data' => [
                'payment' => $payment,
                'pay_url' => $payment->redirect_url,
                'gateway_response' => $gatewayResponse,
            ],
        ], 201);
    }

    public function purchasePlan(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ma_goi' => ['required', 'string'],
            'bank_code' => ['nullable', 'string', 'max:20'],
        ]);

        $plan = GoiDichVu::query()
            ->where('ma_goi', (string) $validated['ma_goi'])
            ->where('trang_thai', GoiDichVu::TRANG_THAI_HOAT_DONG)
            ->firstOrFail();

        try {
            [$payment, $gatewayResponse] = $this->vnpayGatewayService->createSubscriptionPayment(
                $request->user(),
                $plan,
                $validated['bank_code'] ?? null,
                $request->ip(),
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
                'code' => 'VNPAY_CREATE_SUBSCRIPTION_PAYMENT_FAILED',
                'message' => $exception->getMessage(),
            ], 502);
        }

        return response()->json([
            'success' => true,
            'message' => 'Đã tạo giao dịch mua gói qua VNPay.',
            'data' => [
                'payment' => $payment,
                'plan' => $plan,
                'pay_url' => $payment->redirect_url,
                'gateway_response' => $gatewayResponse,
            ],
        ], 201);
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
        $txnRef = (string) ($payload['vnp_TxnRef'] ?? '');
        $payment = $this->webhookHandler->recordReturnPayload($txnRef, $payload);
        $payment = $this->webhookHandler->autoCompleteFromReturnForLocal($txnRef, $payload) ?? $payment;

        $frontendBaseUrl = rtrim((string) config('app.frontend_url', 'http://localhost:5173'), '/');
        $paymentCode = $payment?->ma_giao_dich_noi_bo ?: $txnRef;
        $isGatewaySuccess = (string) ($payload['vnp_ResponseCode'] ?? '') === '00'
            && (string) ($payload['vnp_TransactionStatus'] ?? '') === '00';
        $isCompleted = $payment?->trang_thai === GiaoDichThanhToan::TRANG_THAI_THANH_CONG;
        $isPending = $payment?->trang_thai === GiaoDichThanhToan::TRANG_THAI_PENDING;

        if ($isCompleted && $payment?->loai_giao_dich === GiaoDichThanhToan::LOAI_NAP_VI) {
            $query = [
                'topup' => 'success',
                'orderId' => $paymentCode,
                'message' => 'Nạp tiền thành công',
            ];
            $targetUrl = $frontendBaseUrl . $this->walletPathForPayment($payment) . '?' . http_build_query($query);
        } elseif ($payment?->loai_giao_dich === GiaoDichThanhToan::LOAI_MUA_GOI) {
            $query = array_filter([
                'subscription' => $isCompleted ? 'success' : ($isPending || $isGatewaySuccess ? 'pending' : 'failed'),
                'plan' => $payment?->goiDichVu?->ma_goi,
                'orderId' => $paymentCode !== '' ? $paymentCode : null,
                'message' => isset($payload['vnp_ResponseCode'])
                    ? 'VNPay response ' . (string) $payload['vnp_ResponseCode']
                    : null,
            ], static fn ($value) => $value !== null && $value !== '');

            $targetUrl = $frontendBaseUrl . '/plans';
            if ($query !== []) {
                $targetUrl .= '?' . http_build_query($query);
            }
        } elseif ($payment?->nguoiDung?->isNhaTuyenDung() && $payment?->loai_giao_dich === GiaoDichThanhToan::LOAI_NAP_VI) {
            $query = array_filter([
                'resultCode' => isset($payload['vnp_ResponseCode']) ? (string) $payload['vnp_ResponseCode'] : null,
                'transactionStatus' => isset($payload['vnp_TransactionStatus']) ? (string) $payload['vnp_TransactionStatus'] : null,
                'orderId' => $paymentCode !== '' ? $paymentCode : null,
            ], static fn ($value) => $value !== null && $value !== '');

            $targetUrl = $frontendBaseUrl . '/employer/billing/payment-result/' . rawurlencode($paymentCode !== '' ? $paymentCode : 'unknown');
            if ($query !== []) {
                $targetUrl .= '?' . http_build_query($query);
            }
        } else {
            $query = array_filter([
                'resultCode' => isset($payload['vnp_ResponseCode']) ? (string) $payload['vnp_ResponseCode'] : null,
                'transactionStatus' => isset($payload['vnp_TransactionStatus']) ? (string) $payload['vnp_TransactionStatus'] : null,
                'orderId' => $paymentCode !== '' ? $paymentCode : null,
            ], static fn ($value) => $value !== null && $value !== '');

            $targetUrl = $frontendBaseUrl . '/wallet/payment-result/' . rawurlencode($paymentCode !== '' ? $paymentCode : 'unknown');
            if ($query !== []) {
                $targetUrl .= '?' . http_build_query($query);
            }
        }

        return redirect()->away($targetUrl);
    }

    public function handleIpn(Request $request): JsonResponse
    {
        return response()->json($this->webhookHandler->handleIpn($request->query()));
    }
}
