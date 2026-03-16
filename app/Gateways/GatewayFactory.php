<?php

namespace App\Gateways;

use App\Models\Gateway;

class GatewayFactory
{
    private array $map = [
        'gateway_one' => GatewayOneService::class,
        'gateway_two' => GatewayTwoService::class,
    ];

    public function make(Gateway $gateway): PaymentGatewayInterface
    {
        $class = $this->map[$gateway->name] ?? null;

        if (!$class) {
            throw new \InvalidArgumentException("Unknown gateway: {$gateway->name}");
        }

        return new $class();
    }
}
