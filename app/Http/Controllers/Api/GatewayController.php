<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateGatewayRequest;
use App\Http\Resources\GatewayResource;
use App\Models\Gateway;

class GatewayController extends Controller
{
    public function update(UpdateGatewayRequest $request, Gateway $gateway)
    {
        $validated = $request->validated();

        $gateway->update($validated);

        return new GatewayResource($gateway);
    }
}
