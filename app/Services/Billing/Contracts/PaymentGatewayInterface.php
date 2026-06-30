<?php

namespace App\Services\Billing\Contracts;

use App\Models\GoiDichVu;
use App\Models\GiaoDichThanhToan;
use App\Models\NguoiDung;

interface PaymentGatewayInterface
{
    public function createTopUpPayment(NguoiDung $user, int $amount): array;

    public function createSubscriptionPayment(NguoiDung $user, GoiDichVu $plan): array;

    public function queryPayment(GiaoDichThanhToan $payment): array;

    public function handleIpn(array $payload): ?GiaoDichThanhToan;

    public function recordReturnPayload(string $orderId, array $payload): ?GiaoDichThanhToan;

    public function autoCompleteFromReturnForLocal(string $orderId, array $payload): ?GiaoDichThanhToan;

    public function verifyNotificationSignature(array $payload): bool;
}
