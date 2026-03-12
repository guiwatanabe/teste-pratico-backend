<?php

use Illuminate\Support\Facades\Http;

test('processes the purchase through Gateway 1 first (lowest priority number)', function () {
    Http::fake([
        '*/login' => Http::response(['token' => 'fake-token'], 200),
        '*/transactions' => Http::response(['id' => 'ext-gw1'], 201),
        '*/transacoes' => Http::response(['id' => 'ext-gw2'], 201),
    ]);

    \App\Models\Gateway::factory()->create([
        'name' => 'Gateway One',
        'driver' => 'gateway_1',
        'is_active' => true,
        'priority' => 1,
    ]);

    \App\Models\Gateway::factory()->create([
        'name' => 'Gateway Two',
        'driver' => 'gateway_2',
        'is_active' => true,
        'priority' => 2,
    ]);

    $product = createProducts()->first();

    $response = $this->postJson('/api/purchase', [
        'products' => [['id' => $product->id, 'quantity' => 1]],
        'buyer' => ['name' => 'Test Client', 'email' => 'test@example.com'],
        'card' => ['number' => '4444444444441234', 'expiry' => '12/26', 'cvv' => '123'],
    ]);

    $response->assertStatus(201)
        ->assertJsonFragment(['gateway' => 'Gateway One']);
});

test('falls back to Gateway 2 when Gateway 1 fails (cvv=100)', function () {
    Http::fake([
        '*/login' => Http::response(['token' => 'fake-token'], 200),
        '*/transactions' => Http::response(['error' => 'contate a central do seu cartão'], 400),
        '*/transacoes' => Http::response(['id' => 'ext-gw2'], 201),
    ]);

    \App\Models\Gateway::factory()->create([
        'name' => 'Gateway One',
        'driver' => 'gateway_1',
        'is_active' => true,
        'priority' => 1,
    ]);

    \App\Models\Gateway::factory()->create([
        'name' => 'Gateway Two',
        'driver' => 'gateway_2',
        'is_active' => true,
        'priority' => 2,
    ]);

    $product = createProducts()->first();

    $response = $this->postJson('/api/purchase', [
        'products' => [['id' => $product->id, 'quantity' => 1]],
        'buyer' => ['name' => 'Test Client', 'email' => 'test@example.com'],
        'card' => ['number' => '4444444444441234', 'expiry' => '12/26', 'cvv' => '100'],
    ]);

    $response->assertStatus(201)
        ->assertJsonFragment(['gateway' => 'Gateway Two']);
});

test('skips deactivated gateways and uses the next active one', function () {
    Http::fake([
        '*/transacoes' => Http::response(['id' => 'ext-gw2'], 201),
    ]);

    \App\Models\Gateway::factory()->create([
        'name' => 'Gateway One',
        'driver' => 'gateway_1',
        'is_active' => false,
        'priority' => 1,
    ]);

    \App\Models\Gateway::factory()->create([
        'name' => 'Gateway Two',
        'driver' => 'gateway_2',
        'is_active' => true,
        'priority' => 2,
    ]);

    $product = createProducts()->first();

    $response = $this->postJson('/api/purchase', [
        'products' => [['id' => $product->id, 'quantity' => 1]],
        'buyer' => ['name' => 'Test Client', 'email' => 'test@example.com'],
        'card' => ['number' => '4444444444441234', 'expiry' => '12/26', 'cvv' => '123'],
    ]);

    $response->assertStatus(201)
        ->assertJsonFragment(['gateway' => 'Gateway Two']);

    Http::assertNotSent(fn ($request) => str_contains($request->url(), '/transactions'));
});

test('returns an error when both gateways fail (cvv=200)', function () {
    Http::fake([
        '*/login' => Http::response(['token' => 'fake-token'], 200),
        '*/transactions' => Http::response(['error' => 'contate a central do seu cartão'], 400),
        '*/transacoes' => Http::response([['errors' => [['message' => 'contate a central do seu cartão']], 'statusCode' => 400]], 400),
    ]);

    \App\Models\Gateway::factory()->create([
        'driver' => 'gateway_1',
        'is_active' => true,
        'priority' => 1,
    ]);

    \App\Models\Gateway::factory()->create([
        'driver' => 'gateway_2',
        'is_active' => true,
        'priority' => 2,
    ]);

    $product = createProducts()->first();

    $response = $this->postJson('/api/purchase', [
        'products' => [['id' => $product->id, 'quantity' => 1]],
        'buyer' => ['name' => 'Test Client', 'email' => 'test@example.com'],
        'card' => ['number' => '4444444444441234', 'expiry' => '12/26', 'cvv' => '200'],
    ]);

    $response->assertStatus(502)
        ->assertJsonPath('message', fn ($msg) => str_starts_with($msg, 'All gateways failed'));
});

test('does NOT save the transaction to the database when all gateways fail', function () {
    Http::fake([
        '*/login' => Http::response(['token' => 'fake-token'], 200),
        '*/transactions' => Http::response(['error' => 'contate a central do seu cartão'], 400),
        '*/transacoes' => Http::response([['errors' => [['message' => 'contate a central do seu cartão']], 'statusCode' => 400]], 400),
    ]);

    \App\Models\Gateway::factory()->create([
        'driver' => 'gateway_1',
        'is_active' => true,
        'priority' => 1,
    ]);

    \App\Models\Gateway::factory()->create([
        'driver' => 'gateway_2',
        'is_active' => true,
        'priority' => 2,
    ]);

    $product = createProducts()->first();

    $this->postJson('/api/purchase', [
        'products' => [['id' => $product->id, 'quantity' => 1]],
        'buyer' => ['name' => 'Test Client', 'email' => 'test@example.com'],
        'card' => ['number' => '4444444444441234', 'expiry' => '12/26', 'cvv' => '200'],
    ]);

    $this->assertDatabaseCount('transactions', 0);
});

test('returns an appropriate error when no gateways are active', function () {
    \App\Models\Gateway::factory()->create([
        'driver' => 'gateway_1',
        'is_active' => false,
        'priority' => 1,
    ]);

    $product = createProducts()->first();

    $response = $this->postJson('/api/purchase', [
        'products' => [['id' => $product->id, 'quantity' => 1]],
        'buyer' => ['name' => 'Test Client', 'email' => 'test@example.com'],
        'card' => ['number' => '4444444444441234', 'expiry' => '12/26', 'cvv' => '123'],
    ]);

    $response->assertStatus(502)
        ->assertJsonFragment(['message' => 'No active gateways available.']);
});

test('succeeds via Gateway 1 when only Gateway 2 would fail (cvv=300)', function () {
    Http::fake([
        '*/login' => Http::response(['token' => 'fake-token'], 200),
        '*/transactions' => Http::response(['id' => 'ext-gw1'], 201),
        '*/transacoes' => Http::response([['errors' => [['message' => 'contate a central do seu cartão']], 'statusCode' => 400]], 400),
    ]);

    \App\Models\Gateway::factory()->create([
        'name' => 'Gateway One',
        'driver' => 'gateway_1',
        'is_active' => true,
        'priority' => 1,
    ]);

    \App\Models\Gateway::factory()->create([
        'name' => 'Gateway Two',
        'driver' => 'gateway_2',
        'is_active' => true,
        'priority' => 2,
    ]);

    $product = createProducts()->first();

    $response = $this->postJson('/api/purchase', [
        'products' => [['id' => $product->id, 'quantity' => 1]],
        'buyer' => ['name' => 'Test Client', 'email' => 'test@example.com'],
        'card' => ['number' => '4444444444441234', 'expiry' => '12/26', 'cvv' => '300'],
    ]);

    $response->assertStatus(201)
        ->assertJsonFragment(['gateway' => 'Gateway One']);
});
