<?php

namespace App\Gateways;

use Illuminate\Support\Facades\Http;

class GatewayTwoService implements PaymentGatewayInterface
{
    private string $baseUrl;
    private string $authToken;
    private string $authSecret;

    public function __construct()
    {
        $this->baseUrl = config('services.gateway_two.url', 'http://gateways:3002');
        $this->authToken = config('services.gateway_two.token', 'tk_f2198cc671b5289fa856');
        $this->authSecret = config('services.gateway_two.secret', '3d15e8ed6131446ea7e3456728b1211f');
    }

    public function pay(array $data): array
    {
        try {
            $response = Http::withHeaders($this->headers())
                ->post("{$this->baseUrl}/transacoes", [
                    'valor' => $data['amount'],
                    'nome' => $data['name'],
                    'email' => $data['email'],
                    'numeroCartao' => $data['card_number'],
                    'cvv' => $data['cvv'],
                ]);

            if ($response->successful()) {
                $body = $response->json();
                return [
                    'success' => true,
                    'external_id' => (string) $body['id'],
                    'message' => 'Payment processed successfully.',
                ];
            }

            return [
                'success' => false,
                'external_id' => null,
                'message' => $response->json('message', 'Gateway 2 payment failed.'),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'external_id' => null,
                'message' => 'Gateway 2 unavailable: ' . $e->getMessage(),
            ];
        }
    }

    public function refund(string $externalId): array
    {
        try {
            $response = Http::withHeaders($this->headers())
                ->post("{$this->baseUrl}/transacoes/reembolso", [
                    'id' => $externalId,
                ]);

            if ($response->successful()) {
                return ['success' => true, 'message' => 'Refund processed successfully.'];
            }

            return ['success' => false, 'message' => $response->json('message', 'Refund failed.')];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Gateway 2 unavailable: ' . $e->getMessage()];
        }
    }

    private function headers(): array
    {
        return [
            'Gateway-Auth-Token' => $this->authToken,
            'Gateway-Auth-Secret' => $this->authSecret,
        ];
    }
}
