<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;

class TransactionController extends Controller
{
    public function index()
    {
        return TransactionResource::collection(Transaction::paginate(10));
    }
}
