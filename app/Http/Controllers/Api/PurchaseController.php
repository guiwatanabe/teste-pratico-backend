<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePurchaseRequest;
use App\Http\Resources\TransactionResource;
use App\Services\PurchaseService;

class PurchaseController extends Controller
{
    public function __construct(private PurchaseService $purchaseService) {}

    public function __invoke(StorePurchaseRequest $request)
    {
        try {
            $transaction = $this->purchaseService->process($request->validated());
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 502);
        }

        return response()->json(new TransactionResource($transaction->load('products', 'client', 'gateway')), 201);
    }
}
