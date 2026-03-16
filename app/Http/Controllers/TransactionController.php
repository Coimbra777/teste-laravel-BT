<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Services\PaymentService;

class TransactionController extends Controller
{
    public function __construct(private PaymentService $paymentService)
    {
    }

    public function index()
    {
        $transactions = Transaction::with('client', 'gateway', 'products')->get();

        return response()->json($transactions);
    }

    public function show(Transaction $transaction)
    {
        $transaction->load('client', 'gateway', 'products');

        return response()->json($transaction);
    }

    public function refund(Transaction $transaction)
    {
        $result = $this->paymentService->refund($transaction);

        if (!$result['success']) {
            return response()->json($result, 422);
        }

        return response()->json($result);
    }
}
