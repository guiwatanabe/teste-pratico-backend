<?php

test('returns 401 for unauthenticated request', function () {
    $response = $this->postJson('/api/products', [
        'name' => 'Test Product',
        'stock' => 10,
        'price_cents' => 1000,
    ]);

    $response->assertStatus(401);
});

test('allows ADMIN, MANAGER, and FINANCE to create a product', function () {
    $adminUser = createUser('ADMIN');
    $managerUser = createUser('MANAGER');
    $financeUser = createUser('FINANCE');

    $responseAdmin = $this->actingAs($adminUser)->postJson('/api/products', [
        'name' => 'Test Product',
        'stock' => 10,
        'price_cents' => 1000,
    ]);

    $responseManager = $this->actingAs($managerUser)->postJson('/api/products', [
        'name' => 'Test Product',
        'stock' => 10,
        'price_cents' => 1000,
    ]);

    $responseFinance = $this->actingAs($financeUser)->postJson('/api/products', [
        'name' => 'Test Product',
        'stock' => 10,
        'price_cents' => 1000,
    ]);

    $responseAdmin->assertStatus(201);
    $responseManager->assertStatus(201);
    $responseFinance->assertStatus(201);
});

test('prevents USER from creating products', function () {
    $user = createUser('USER');

    $response = $this->actingAs($user)->postJson('/api/products', [
        'name' => 'Test Product',
        'stock' => 10,
        'price_cents' => 1000,
    ]);

    $response->assertStatus(403);
});

test('returns 422 on invalid payload', function () {
    $adminUser = createUser('ADMIN');

    $response = $this->actingAs($adminUser)->postJson('/api/products', [
        'name' => '',
        'stock' => -5,
        'price_cents' => -1000,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name', 'stock', 'price_cents']);
});
