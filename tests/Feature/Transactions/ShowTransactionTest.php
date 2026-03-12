<?php

test('returns 401 for unauthenticated request', function () {
    $response = $this->getJson('/api/transactions/1');

    $response->assertStatus(401);
});

test('returns transaction detail with related products and quantities', function () {
    $user = createUser('USER');
    $transaction = createTransactions()->first();

    $response = $this->actingAs($user)->getJson("/api/transactions/{$transaction->id}");

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'id',
                'status',
                'amount',
                'card_last_numbers',
                'products' => [
                    '*' => ['id', 'name', 'quantity'],
                ],
            ],
        ]);
});

test('returns 404 for a non-existent transaction', function () {
    $user = createUser('USER');

    $response = $this->actingAs($user)->getJson('/api/transactions/9999');

    $response->assertStatus(404);
});
