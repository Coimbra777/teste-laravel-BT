<?php

namespace App\Http\Controllers;

use App\Http\Requests\GatewayPriorityRequest;
use App\Models\Gateway;

class GatewayController extends Controller
{
    public function index()
    {
        return response()->json(Gateway::ordered()->get());
    }

    public function toggle(Gateway $gateway)
    {
        $gateway->update(['is_active' => !$gateway->is_active]);

        return response()->json($gateway);
    }

    public function updatePriority(GatewayPriorityRequest $request, Gateway $gateway)
    {
        $gateway->update(['priority' => $request->priority]);

        return response()->json($gateway);
    }
}
