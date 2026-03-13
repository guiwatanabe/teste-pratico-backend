<?php

test('returns 401 on unauthenticated request', function () {
    $product = createProducts()->first();

    $response = $this->getJson("/api/products/{$product->id}");

    $response->assertStatus(401);
});

test('returns 404 for non-existent product', function () {
    $user = createUser();

    $response = $this->actingAs($user)->getJson('/api/products/999');

    $response->assertStatus(404);
});

test('returns product details for authenticated user', function () {
    $user = createUser();
    $product = createProducts()->first();

    $response = $this->actingAs($user)->getJson("/api/products/{$product->id}");

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'stock',
                'price',
                'created_at',
                'updated_at',
            ],
        ])
        ->assertJson([
            'data' => [
                'id' => $product->id,
                'name' => $product->name,
                'stock' => $product->stock,
                'price' => $product->price_cents,
                'created_at' => $product->created_at->toISOString(),
                'updated_at' => $product->updated_at->toISOString(),
            ],
        ]);
});
