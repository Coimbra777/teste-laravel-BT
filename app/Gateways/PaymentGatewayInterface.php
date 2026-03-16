<?php

namespace App\Gateways;

interface PaymentGatewayInterface
{
    /**
     * @return array{success: bool, external_id: string|null, message: string}
     */
    public function pay(array $data): array;

    /**
     * @return array{success: bool, message: string}
     */
    public function refund(string $externalId): array;
}
