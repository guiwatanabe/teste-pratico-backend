<?php

use App\Models\Gateway;
use App\Models\Transaction;
use App\Models\User;

function createUser($role = 'ADMIN')
{
    return User::factory()->create([
        'role' => $role,
    ]);
}

function createTransactions($count = 1)
{
    $gateway = Gateway::factory()->create();

    return Transaction::factory()->count($count)->create([
        'gateway_id' => $gateway->id,
    ]);
}

test('returns 401 for unauthenticated request', function () {
    $response = $this->getJson('/api/transactions');

    $response->assertStatus(401);
});

test('returns transaction list for any authenticated user', function () {
    createUser('USER');
    createTransactions(3);

    $response = $this->actingAs(createUser())->getJson('/api/transactions');

    $response->assertStatus(200)
        ->assertJsonCount(3, 'data')
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'status', 'amount', 'card_last_numbers'],
            ],
            'meta' => ['total', 'per_page', 'current_page', 'last_page'],
            'links',
        ]);
});
