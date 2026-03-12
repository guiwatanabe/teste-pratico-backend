<?php

use App\Models\Gateway;
use Illuminate\Support\Facades\Http;

test('returns 401 for unauthenticated request', function () {
    $gateway = Gateway::factory()->create();

    $response = $this->patchJson("/api/gateways/{$gateway->id}", ['priority' => 1]);
    $response->assertStatus(401);
});

test('allows ADMIN to change a gateway priority', function () {
    $admin = createUser('ADMIN');
    $gateway = Gateway::factory()->create(['priority' => 5]);

    $response = $this->actingAs($admin)->patchJson("/api/gateways/{$gateway->id}", ['priority' => 1]);

    $response->assertStatus(200)
        ->assertJsonFragment(['priority' => 1]);

    expect($gateway->fresh()->priority)->toBe(1);
});

test('prevents non-ADMIN from changing priority', function () {
    $gateway = Gateway::factory()->create(['priority' => 5]);

    foreach (['MANAGER', 'FINANCE', 'USER'] as $role) {
        $user = createUser($role);

        $response = $this->actingAs($user)->patchJson("/api/gateways/{$gateway->id}", ['priority' => 1]);

        $response->assertStatus(403);
        expect($gateway->fresh()->priority)->toBe(5);
    }
});

test('returns 404 for a non-existent gateway', function () {
    $admin = createUser('ADMIN');

    $response = $this->actingAs($admin)->patchJson('/api/gateways/999', ['priority' => 1]);

    $response->assertStatus(404);
});

test('returns 422 when priority is not a positive integer', function () {
    $admin = createUser('ADMIN');
    $gateway = Gateway::factory()->create(['priority' => 5]);

    foreach ([0, -1, 'abc', null] as $invalid) {
        $response = $this->actingAs($admin)->patchJson("/api/gateways/{$gateway->id}", ['priority' => $invalid]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['priority']);
    }
});

test('a lower priority number is tried first on purchase (integration check)', function () {
    $admin = createUser('ADMIN');

    $gateway1 = Gateway::factory()->create([
        'name' => 'Gateway One',
        'driver' => 'gateway_1',
        'is_active' => true,
        'priority' => 5,
    ]);

    $gateway2 = Gateway::factory()->create([
        'name' => 'Gateway Two',
        'driver' => 'gateway_2',
        'is_active' => true,
        'priority' => 1,
    ]);

    $product = createProducts()->first();

    Http::fake([
        '*/login' => Http::response(['token' => 'fake-token'], 200),
        '*/transactions' => Http::response(['id' => 'ext-gw1'], 201),
        '*/transacoes' => Http::response(['id' => 'ext-gw2'], 201),
    ]);

    $response = $this->postJson('/api/purchase', [
        'products' => [['id' => $product->id, 'quantity' => 1]],
        'buyer' => ['name' => 'Test Client', 'email' => 'first@example.com'],
        'card' => ['number' => '4444444444441234', 'expiry' => '12/26', 'cvv' => '123'],
    ]);

    $response->assertStatus(201)->assertJsonFragment(['gateway' => 'Gateway Two']);

    $this->actingAs($admin)->patchJson("/api/gateways/{$gateway2->id}", ['priority' => 10]);

    Http::fake([
        '*/login' => Http::response(['token' => 'fake-token'], 200),
        '*/transactions' => Http::response(['id' => 'ext-gw1'], 201),
        '*/transacoes' => Http::response(['id' => 'ext-gw2'], 201),
    ]);

    $response = $this->postJson('/api/purchase', [
        'products' => [['id' => $product->id, 'quantity' => 1]],
        'buyer' => ['name' => 'Test Client', 'email' => 'second@example.com'],
        'card' => ['number' => '4444444444441234', 'expiry' => '12/26', 'cvv' => '123'],
    ]);

    $response->assertStatus(201)->assertJsonFragment(['gateway' => 'Gateway One']);
});
