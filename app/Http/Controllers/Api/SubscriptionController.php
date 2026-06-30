<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\BillingException;
use App\Http\Controllers\Controller;
use App\Models\GoiDichVu;
use App\Services\Billing\Contracts\PaymentGatewayInterface;
use App\Services\Billing\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function __construct(
        private readonly SubscriptionService $subscriptionService,
    ) {
    }

    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->subscriptionService->getActivePlans(),
        ]);
    }

    public function current(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->subscriptionService->getActiveSubscription($request->user()),
        ]);
    }

    public function purchaseMomo(Request $request, PaymentGatewayInterface $paymentGateway): JsonResponse
    {
        $validated = $request->validate([
            'ma_goi' => ['required', 'string'],
        ]);

        $plan = GoiDichVu::query()
            ->where('ma_goi', (string) $validated['ma_goi'])
            ->where('trang_thai', GoiDichVu::TRANG_THAI_HOAT_DONG)
            ->firstOrFail();

        try {
            [$payment, $gatewayResponse] = $paymentGateway->createSubscriptionPayment($request->user(), $plan);
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
                'code' => 'MOMO_CREATE_SUBSCRIPTION_PAYMENT_FAILED',
                'message' => $exception->getMessage(),
            ], 502);
        }

        return response()->json([
            'success' => true,
            'message' => 'Đã tạo giao dịch mua gói qua MoMo.',
            'data' => [
                'payment' => $payment,
                'plan' => $plan,
                'pay_url' => $payment->redirect_url,
                'gateway_response' => $gatewayResponse,
            ],
        ], 201);
    }

    public function purchaseWallet(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ma_goi' => ['required', 'string'],
        ]);

        $plan = GoiDichVu::query()
            ->where('ma_goi', (string) $validated['ma_goi'])
            ->where('trang_thai', GoiDichVu::TRANG_THAI_HOAT_DONG)
            ->firstOrFail();

        try {
            [$payment, $subscription, $walletTransaction] = $this->subscriptionService->purchaseWithWallet(
                $request->user(),
                $plan,
            );
        } catch (BillingException $exception) {
            return response()->json([
                'success' => false,
                'code' => $exception->errorCode,
                'message' => $exception->getMessage(),
                ...$exception->context,
            ], $exception->status);
        }

        return response()->json([
            'success' => true,
            'message' => 'Đã kích hoạt gói Pro bằng ví AI.',
            'data' => [
                'payment' => $payment,
                'plan' => $plan,
                'subscription' => $subscription,
                'wallet_transaction' => $walletTransaction,
                'pay_url' => null,
            ],
        ], 201);
    }
}
