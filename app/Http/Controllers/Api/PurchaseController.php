<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePurchaseRequest;

class PurchaseController extends Controller
{
    public function __invoke(StorePurchaseRequest $request)
    {
        return response()->json(['message' => 'Purchase endpoint.'], 200);
    }
}
