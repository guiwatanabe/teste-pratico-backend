<?php

test('has many transactions', function () {
    $gateway = \App\Models\Gateway::factory()->create();
    expect($gateway->transactions())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
});

test('belongs to a transaction', function () {
    $tp = \App\Models\TransactionProduct::factory()->make();
    expect($tp->transaction())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class);
    expect($tp->product())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class);
});
