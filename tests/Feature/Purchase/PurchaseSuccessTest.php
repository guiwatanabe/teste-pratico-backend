<?php

use Illuminate\Support\Facades\Http;

function testCard(): array
{
    return [
        'number' => '4444444444441234',
        'expiry' => '12/26',
        'cvv' => '123',
    ];
}

function fakeGateways(): void
{
    Http::fake([
        '*/login' => Http::response(['token' => 'fake-token'], 200),
        '*/transactions' => Http::response(['id' => 'ext-abc'], 201),
        '*/transacoes' => Http::response(['id' => 'ext-abc'], 201),
    ]);
}

test('stores only the last 4 digits of the card number', function () {
    fakeGateways();

    \App\Models\Gateway::factory()->create(['is_active' => true, 'priority' => 1]);
    $product = createProducts()->first();
    $response = $this->postJson('/api/purchase', [
        'products' => [
            ['id' => $product->id, 'quantity' => 1],
        ],
        'buyer' => [
            'name' => 'Test Client',
            'email' => 'test.client@example.com',
        ],
        'card' => testCard(),
    ]);

    $response->assertStatus(201)
        ->assertJsonFragment(['card_last_numbers' => '1234']);
});

test('creates a new client record when the buyer email is new', function () {
    fakeGateways();

    \App\Models\Gateway::factory()->create(['is_active' => true, 'priority' => 1]);
    $product = createProducts()->first();

    $response = $this->postJson('/api/purchase', [
        'products' => [['id' => $product->id, 'quantity' => 1]],
        'buyer' => [
            'name' => 'New Client',
            'email' => 'new.client@example.com',
        ],
        'card' => testCard(),
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('buyer.email', 'new.client@example.com');

    $this->assertDatabaseHas('clients', [
        'email' => 'new.client@example.com',
    ]);
});

test('reuses an existing client when the buyer email already exists', function () {
    fakeGateways();

    \App\Models\Gateway::factory()->create(['is_active' => true, 'priority' => 1]);
    $product = createProducts()->first();
    $existingClient = createClients()->first();

    $response = $this->postJson('/api/purchase', [
        'products' => [['id' => $product->id, 'quantity' => 1]],
        'buyer' => [
            'name' => $existingClient->name,
            'email' => $existingClient->email,
        ],
        'card' => testCard(),
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('buyer.email', $existingClient->email);

    $this->assertDatabaseCount('clients', 1);
});

test('links all purchased products with correct quantity and unit price in transaction_products', function () {
    fakeGateways();

    \App\Models\Gateway::factory()->create(['is_active' => true, 'priority' => 1]);
    $product1 = \App\Models\Product::factory()->create(['stock' => 10, 'price_cents' => 1500]);
    $product2 = \App\Models\Product::factory()->create(['stock' => 10, 'price_cents' => 2500]);
    $product3 = \App\Models\Product::factory()->create(['stock' => 10, 'price_cents' => 1000]);
    $product4 = \App\Models\Product::factory()->create(['stock' => 10, 'price_cents' => 2000]);

    $response = $this->postJson('/api/purchase', [
        'products' => [
            ['id' => $product1->id, 'quantity' => 2],
            ['id' => $product2->id, 'quantity' => 1],
            ['id' => $product3->id, 'quantity' => 3],
            ['id' => $product4->id, 'quantity' => 1],
        ],
        'buyer' => [
            'name' => 'Test Client',
            'email' => 'test.client@example.com',
        ],
        'card' => testCard(),
    ]);

    $response->assertStatus(201);
    $this->assertDatabaseHas('transaction_products', [
        'product_id' => $product1->id,
        'quantity' => 2,
        'unit_price' => 1500,
        'total_price' => 3000,
    ]);
    $this->assertDatabaseHas('transaction_products', [
        'product_id' => $product2->id,
        'quantity' => 1,
        'unit_price' => 2500,
        'total_price' => 2500,
    ]);
    $this->assertDatabaseHas('transaction_products', [
        'product_id' => $product3->id,
        'quantity' => 3,
        'unit_price' => 1000,
        'total_price' => 3000,
    ]);
    $this->assertDatabaseHas('transaction_products', [
        'product_id' => $product4->id,
        'quantity' => 1,
        'unit_price' => 2000,
        'total_price' => 2000,
    ]);
});

test('decrements product stock after a successful purchase', function () {
    fakeGateways();

    \App\Models\Gateway::factory()->create(['is_active' => true, 'priority' => 1]);
    $product = \App\Models\Product::factory()->create(['stock' => 10, 'price_cents' => 1000]);

    $this->postJson('/api/purchase', [
        'products' => [['id' => $product->id, 'quantity' => 3]],
        'buyer' => ['name' => 'Test Client', 'email' => 'test.client@example.com'],
        'card' => testCard(),
    ])->assertStatus(201);

    $this->assertDatabaseHas('products', [
        'id' => $product->id,
        'stock' => 7,
    ]);
});

test('calculates total correctly for multiple products with different quantities', function () {
    fakeGateways();

    \App\Models\Gateway::factory()->create(['is_active' => true, 'priority' => 1]);
    $product1 = \App\Models\Product::factory()->create(['stock' => 5, 'price_cents' => 1200]);
    $product2 = \App\Models\Product::factory()->create(['stock' => 5, 'price_cents' => 3000]);

    $response = $this->postJson('/api/purchase', [
        'products' => [
            ['id' => $product1->id, 'quantity' => 4],
            ['id' => $product2->id, 'quantity' => 2],
        ],
        'buyer' => [
            'name' => 'Test Client',
            'email' => 'test.client@example.com',
        ],
        'card' => testCard(),
    ]);

    $response->assertStatus(201);
    $response->assertJsonFragment(['amount' => 10800]);
});

test('response includes transaction id, status, amount, and gateway used', function () {
    fakeGateways();

    \App\Models\Gateway::factory()->create(['name' => 'Fake Gateway', 'is_active' => true, 'priority' => 1]);
    $product = createProducts()->first();

    $response = $this->postJson('/api/purchase', [
        'products' => [['id' => $product->id, 'quantity' => 1]],
        'buyer' => [
            'name' => 'Test Client',
            'email' => 'test.client@example.com',
        ],
        'card' => testCard(),
    ]);

    $response->assertStatus(201);
    $response->assertJsonFragment(['external_id' => 'ext-abc']);
    $response->assertJsonFragment(['status' => 'completed']);
    $response->assertJsonFragment(['amount' => $product->price_cents]);
    $response->assertJsonFragment(['gateway' => 'Fake Gateway']);
});
