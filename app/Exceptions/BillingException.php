<?php

namespace App\Exceptions;

use RuntimeException;

class BillingException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly string $errorCode = 'BILLING_ERROR',
        public readonly int $status = 422,
        public readonly array $context = [],
    ) {
        parent::__construct($message);
    }

    public static function insufficientBalance(int $available, int $required): self
    {
        return new self(
            'Số dư ví không đủ để thực hiện tính năng AI này. Vui lòng nạp thêm tiền.',
            'WALLET_INSUFFICIENT_BALANCE',
            402,
            [
                'available_balance' => $available,
                'required_amount' => $required,
            ],
        );
    }

    public static function insufficientSubscriptionBalance(int $available, int $required): self
    {
        return new self(
            'Số dư ví AI không đủ để mua gói Pro này. Vui lòng nạp thêm tiền hoặc chọn cổng thanh toán khác.',
            'WALLET_SUBSCRIPTION_INSUFFICIENT_BALANCE',
            402,
            [
                'available_balance' => $available,
                'required_amount' => $required,
            ],
        );
    }

    public static function walletLocked(): self
    {
        return new self(
            'Ví của bạn hiện không khả dụng. Vui lòng liên hệ quản trị viên.',
            'WALLET_LOCKED',
            403,
        );
    }

    public static function featureNotPriced(string $featureCode): self
    {
        return new self(
            'Tính năng AI này chưa được cấu hình bảng giá.',
            'AI_FEATURE_PRICE_MISSING',
            503,
            ['feature_code' => $featureCode],
        );
    }

    public static function duplicateRequest(string $idempotencyKey): self
    {
        return new self(
            'Yêu cầu đang được xử lý hoặc đã được gửi trước đó. Vui lòng chờ hoặc thử lại với thao tác mới.',
            'BILLING_DUPLICATE_REQUEST',
            409,
            ['idempotency_key' => $idempotencyKey],
        );
    }

    public static function invalidPaymentAmount(int $minAmount): self
    {
        return new self(
            "Số tiền nạp không hợp lệ. Mức tối thiểu là {$minAmount} VNĐ.",
            'PAYMENT_INVALID_AMOUNT',
            422,
            ['min_amount' => $minAmount],
        );
    }

    public static function paymentGatewayUnavailable(string $gateway): self
    {
        return new self(
            'Cổng thanh toán hiện chưa được cấu hình đầy đủ hoặc đang tạm thời không khả dụng.',
            'PAYMENT_GATEWAY_UNAVAILABLE',
            503,
            ['gateway' => $gateway],
        );
    }

    public static function invalidSubscriptionPlan(string $planCode): self
    {
        return new self(
            'Gói dịch vụ bạn chọn hiện không thể thanh toán qua cổng này.',
            'SUBSCRIPTION_PLAN_INVALID',
            422,
            ['plan_code' => $planCode],
        );
    }
}
