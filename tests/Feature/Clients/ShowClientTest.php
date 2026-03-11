<?php

use App\Models\Client;
use App\Models\Product;

function createUser($role = 'ADMIN')
{
    return \App\Models\User::factory()->create([
        'role' => $role,
    ]);
}

function createClients($count = 1)
{
    return Client::factory()->count($count)->create();
}

function createTransactionForClient(Client $client, $count = 1)
{
    $gateway = \App\Models\Gateway::factory()->create();

    return \App\Models\Transaction::factory()->count($count)->create([
        'client_id' => $client->id,
        'gateway_id' => $gateway->id,
    ]);
}

test('returns 401 for unauthenticated request', function () {
    $response = $this->getJson('/api/clients/1');

    $response->assertStatus(401);
});

test('returns client detail with all transactions for any authenticated user', function () {
    $user = createUser('USER');
    $client = createClients()->first();
    $transactions = createTransactionForClient($client, 3);

    $response = $this->actingAs($user)->getJson("/api/clients/{$client->id}");

    $response->assertStatus(200)
        ->assertJsonPath('data.id', $client->id)
        ->assertJsonPath('data.name', $client->name)
        ->assertJsonPath('data.email', $client->email)
        ->assertJsonCount(3, 'data.transactions')
        ->assertJsonStructure([
            'data' => [
                'transactions' => [
                    '*' => ['id', 'status', 'amount', 'card_last_numbers'],
                ],
            ],
        ]);
});

test('returns an empty array for a client with no transactions', function () {
    $user = createUser('USER');
    $client = createClients()->first();

    $response = $this->actingAs($user)->getJson("/api/clients/{$client->id}");

    $response->assertStatus(200)
        ->assertJsonPath('data.transactions', []);
});

test('transaction entries include product details', function () {
    $user = createUser('USER');
    $client = createClients()->first();
    $transaction = createTransactionForClient($client)->first();
    $product = Product::factory()->create(['amount' => 5000]);

    $transaction->products()->create([
        'product_id' => $product->id,
        'product_name' => $product->name,
        'quantity' => 2,
        'unit_price' => 5000,
        'total_price' => 10000,
    ]);

    $response = $this->actingAs($user)->getJson("/api/clients/{$client->id}");

    $response->assertStatus(200)
        ->assertJsonPath('data.transactions.0.products.0.id', $product->id)
        ->assertJsonPath('data.transactions.0.products.0.name', $product->name)
        ->assertJsonPath('data.transactions.0.products.0.quantity', 2)
        ->assertJsonPath('data.transactions.0.products.0.unit_price', 5000)
        ->assertJsonPath('data.transactions.0.products.0.total_price', 10000);
});

test('returns 404 for a non-existent client', function () {
    $user = createUser();

    $response = $this->actingAs($user)->getJson('/api/clients/999');

    $response->assertStatus(404);
});
