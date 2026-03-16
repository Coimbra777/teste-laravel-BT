<?php

namespace App\Http\Controllers;

use App\Models\Client;

class ClientController extends Controller
{
    public function index()
    {
        return response()->json(Client::all());
    }

    public function show(Client $client)
    {
        $client->load('transactions.products', 'transactions.gateway');

        return response()->json($client);
    }
}
