<?php

namespace App\Gateways;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class GatewayOneService implements PaymentGatewayInterface
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.gateway_one.url', 'http://gateways:3001');
    }

    public function pay(array $data): array
    {
        try {
            $token = $this->authenticate();

            $response = Http::withToken($token)->post("{$this->baseUrl}/transactions", [
                'amount' => $data['amount'],
                'name' => $data['name'],
                'email' => $data['email'],
                'cardNumber' => $data['card_number'],
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
                'message' => $response->json('message', 'Gateway 1 payment failed.'),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'external_id' => null,
                'message' => 'Gateway 1 unavailable: ' . $e->getMessage(),
            ];
        }
    }

    public function refund(string $externalId): array
    {
        try {
            $token = $this->authenticate();

            $response = Http::withToken($token)
                ->post("{$this->baseUrl}/transactions/{$externalId}/charge_back");

            if ($response->successful()) {
                return ['success' => true, 'message' => 'Refund processed successfully.'];
            }

            return ['success' => false, 'message' => $response->json('message', 'Refund failed.')];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Gateway 1 unavailable: ' . $e->getMessage()];
        }
    }

    private function authenticate(): string
    {
        return Cache::remember('gateway_one_token', 3600, function () {
            $response = Http::post("{$this->baseUrl}/login", [
                'email' => 'dev@betalent.tech',
                'token' => 'FEC9BB078BF338F464F96B48089EB498',
            ]);

            if ($response->failed()) {
                throw new \RuntimeException('Failed to authenticate with Gateway 1.');
            }

            return $response->json('token');
        });
    }
}
