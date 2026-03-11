<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RefundTransactionRequest;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use App\Services\PaymentService;
use App\Services\RefundService;

class TransactionController extends Controller
{
    public function __construct(private PaymentService $paymentService, private RefundService $refundService) {}

    public function index()
    {
        return TransactionResource::collection(Transaction::paginate(10));
    }

    public function show(Transaction $transaction)
    {
        return new TransactionResource($transaction->load('products'));
    }

    public function refund(RefundTransactionRequest $request, Transaction $transaction)
    {
        if ($transaction->status === 'refunded') {
            return response()->json(['message' => 'Transaction already refunded.'], 409);
        }

        $transaction->load('gateway');

        try {
            $this->refundService->attemptRefund($transaction);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 502);
        }

        $transaction->update(['status' => 'refunded']);

        return new TransactionResource($transaction);
    }
}
