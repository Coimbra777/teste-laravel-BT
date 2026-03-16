<?php

namespace App\Services;

use App\Gateways\GatewayFactory;
use App\Models\Client;
use App\Models\Gateway;
use App\Models\Product;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function __construct(private GatewayFactory $gatewayFactory)
    {
    }

    public function purchase(array $data): array
    {
        $gateways = Gateway::active()->ordered()->get();

        if ($gateways->isEmpty()) {
            return ['success' => false, 'message' => 'No active gateways available.'];
        }

        $client = Client::firstOrCreate(
            ['email' => $data['email']],
            ['name' => $data['name']]
        );

        $amount = $this->calculateAmount($data['products']);
        $cardLastNumbers = substr($data['card_number'], -4);

        $paymentData = [
            'amount' => $amount,
            'name' => $data['name'],
            'email' => $data['email'],
            'card_number' => $data['card_number'],
            'cvv' => $data['cvv'],
        ];

        $errors = [];

        foreach ($gateways as $gateway) {
            $service = $this->gatewayFactory->make($gateway);
            $result = $service->pay($paymentData);

            if ($result['success']) {
                $transaction = DB::transaction(function () use ($client, $gateway, $result, $amount, $cardLastNumbers, $data) {
                    $transaction = Transaction::create([
                        'client_id' => $client->id,
                        'gateway_id' => $gateway->id,
                        'external_id' => $result['external_id'],
                        'status' => 'approved',
                        'amount' => $amount,
                        'card_last_numbers' => $cardLastNumbers,
                    ]);

                    foreach ($data['products'] as $item) {
                        $transaction->products()->attach($item['product_id'], [
                            'quantity' => $item['quantity'],
                        ]);
                    }

                    return $transaction;
                });

                return [
                    'success' => true,
                    'transaction' => $transaction->load('products', 'client', 'gateway'),
                ];
            }

            $errors[] = "{$gateway->name}: {$result['message']}";
        }

        return [
            'success' => false,
            'message' => 'All gateways failed.',
            'errors' => $errors,
        ];
    }

    public function refund(Transaction $transaction): array
    {
        if ($transaction->status === 'refunded') {
            return ['success' => false, 'message' => 'Transaction already refunded.'];
        }

        $service = $this->gatewayFactory->make($transaction->gateway);
        $result = $service->refund($transaction->external_id);

        if ($result['success']) {
            $transaction->update(['status' => 'refunded']);

            return [
                'success' => true,
                'message' => 'Refund processed successfully.',
                'transaction' => $transaction->fresh(['products', 'client', 'gateway']),
            ];
        }

        return ['success' => false, 'message' => $result['message']];
    }

    private function calculateAmount(array $products): int
    {
        $total = 0;

        foreach ($products as $item) {
            $product = Product::findOrFail($item['product_id']);
            $total += $product->amount * $item['quantity'];
        }

        return $total;
    }
}
