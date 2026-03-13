<?php

function createProduct()
{
    return \App\Models\Product::factory()->create();
}

test('returns 401 for unauthenticated request', function () {
    $response = $this->patchJson('/api/products/1', [
        'name' => 'Updated Product',
        'stock' => 20,
        'price_cents' => 2000,
    ]);

    $response->assertStatus(401);
});

test('allows ADMIN, MANAGER, and FINANCE to update a product', function () {
    $product = createProduct();
    $adminUser = createUser('ADMIN');
    $managerUser = createUser('MANAGER');
    $financeUser = createUser('FINANCE');

    $responseAdmin = $this->actingAs($adminUser)->patchJson("/api/products/{$product->id}", [
        'name' => 'Updated Product',
        'stock' => 20,
        'price_cents' => 2000,
    ]);

    $responseAdmin->assertStatus(200);

    $responseManager = $this->actingAs($managerUser)->patchJson("/api/products/{$product->id}", [
        'name' => 'Updated Product 1',
        'stock' => 20,
        'price_cents' => 2000,
    ]);

    $responseManager->assertStatus(200);

    $responseFinance = $this->actingAs($financeUser)->patchJson("/api/products/{$product->id}", [
        'name' => 'Updated Product 2',
        'stock' => 20,
        'price_cents' => 2000,
    ]);

    $responseFinance->assertStatus(200);
});

test('prevents USER from updating products', function () {
    $user = createUser('USER');
    $product = createProduct();

    $response = $this->actingAs($user)->patchJson("/api/products/{$product->id}", [
        'name' => 'Updated Product',
        'stock' => 20,
        'price_cents' => 2000,
    ]);

    $response->assertStatus(403);
});

test('returns 404 for non-existent or soft-deleted product', function () {
    $adminUser = createUser('ADMIN');

    $response = $this->actingAs($adminUser)->patchJson('/api/products/9999', [
        'name' => 'Updated Product',
        'stock' => 20,
        'price_cents' => 2000,
    ]);

    $response->assertStatus(404);
});

test('returns 422 on invalid payload', function () {
    $adminUser = createUser('ADMIN');
    $product = createProduct();

    $response = $this->actingAs($adminUser)->patchJson("/api/products/{$product->id}", [
        'name' => '',
        'stock' => -5,
        'price_cents' => -1000,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name', 'stock', 'price_cents']);
});
