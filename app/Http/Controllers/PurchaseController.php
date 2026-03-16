<?php

namespace App\Http\Controllers;

use App\Http\Requests\PurchaseRequest;
use App\Services\PaymentService;

class PurchaseController extends Controller
{
    public function __construct(private PaymentService $paymentService)
    {
    }

    public function store(PurchaseRequest $request)
    {
        $result = $this->paymentService->purchase($request->validated());

        if (!$result['success']) {
            return response()->json($result, 422);
        }

        return response()->json($result, 201);
    }
}
