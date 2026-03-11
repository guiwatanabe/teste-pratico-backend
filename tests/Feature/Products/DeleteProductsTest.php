<?php

function createProducts(int $count = 1)
{
    return \App\Models\Product::factory()->count($count)->create();
}

function createUser($role = 'ADMIN')
{
    return \App\Models\User::factory()->create([
        'role' => $role,
    ]);
}

test('returns 401 for unauthenticated request', function () {
    $response = $this->deleteJson('/api/products/1');

    $response->assertStatus(401);
});

test('allows ADMIN, MANAGER, and FINANCE to delete a product', function () {
    $products = createProducts(3);
    $adminUser = createUser('ADMIN');
    $managerUser = createUser('MANAGER');
    $financeUser = createUser('FINANCE');

    $responseAdmin = $this->actingAs($adminUser)->deleteJson("/api/products/{$products[0]->id}");
    $responseAdmin->assertStatus(204);

    $responseManager = $this->actingAs($managerUser)->deleteJson("/api/products/{$products[1]->id}");
    $responseManager->assertStatus(204);

    $responseFinance = $this->actingAs($financeUser)->deleteJson("/api/products/{$products[2]->id}");
    $responseFinance->assertStatus(204);
});

test('prevents USER from deleting products', function () {
    $user = createUser('USER');
    $product = createProducts(1)->first();

    $response = $this->actingAs($user)->deleteJson("/api/products/{$product->id}");

    $response->assertStatus(403);
});

test('soft-deletes the product', function () {
    $adminUser = createUser('ADMIN');
    $product = createProducts(1)->first();

    $response = $this->actingAs($adminUser)->deleteJson("/api/products/{$product->id}");
    $response->assertStatus(204);

    $this->assertSoftDeleted('products', ['id' => $product->id]);
});

test('returns 404 for a non-existent product', function () {
    $adminUser = createUser('ADMIN');

    $response = $this->actingAs($adminUser)->deleteJson('/api/products/9999');

    $response->assertStatus(404);
});
