<?php

test('returns 422 when required buyer fields are missing or invalid', function () {
    $product = createProducts()->first();

    $response = $this->postJson('/api/purchase', [
        'products' => [
            [
                'id' => $product->id,
                'quantity' => 1,
            ],
        ],
        'buyer' => [],
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['buyer.name', 'buyer.email']);
});

test('returns 422 when products array is missing or empty', function () {
    $response = $this->postJson('/api/purchase', [
        'buyer' => [
            'name' => 'Test Client',
            'email' => 'test.client@example.com',
        ],
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['products']);
});

test('returns 422 when a product_id does not exist or is soft-deleted', function () {
    $response = $this->postJson('/api/purchase', [
        'products' => [
            [
                'id' => 9999,
                'quantity' => 1,
            ],
        ],
        'buyer' => [
            'name' => 'Test Client',
            'email' => 'test.client@example.com',
        ],
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['products.0.id']);
});

test('returns 422 when quantity is zero, negative, or non-integer', function () {
    $product = createProducts()->first();

    $response = $this->postJson('/api/purchase', [
        'products' => [
            [
                'id' => $product->id,
                'quantity' => 0,
            ],
        ],
        'buyer' => [
            'name' => 'Test Client',
            'email' => 'test.client@example.com',
        ],
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['products.0.quantity']);
});

test('returns 422 when product has no stock available', function () {
    $product = createProducts()->first();
    $product->update(['amount' => 0]);

    $response = $this->postJson('/api/purchase', [
        'products' => [
            [
                'id' => $product->id,
                'quantity' => 1,
            ],
        ],
        'buyer' => [
            'name' => 'Test Client',
            'email' => 'test.client@example.com',
        ],
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['products.0.quantity']);
});
