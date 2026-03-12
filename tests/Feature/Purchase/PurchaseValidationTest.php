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

test('returns 422 when required card fields are missing or invalid', function () {
    $response = $this->postJson('/api/purchase', [
        'products' => [],
        'buyer' => [
            'name' => 'Test Client',
            'email' => 'test.client@example.com',
        ],
        'card' => [
            'number' => 'asdf',
        ],
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['card.number', 'card.expiry', 'card.cvv']);
});

test('returns 422 when card expiry is in the past', function () {
    $product = createProducts()->first();

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
        'card' => [
            'number' => '5569000000006063',
            'expiry' => '01/20',
            'cvv' => '010',
        ],
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['card.expiry']);
});

test('does not return card expiry error when expiry is in the future', function () {
    $product = createProducts()->first();

    $futureExpiry = now()->addYear()->format('m/y');

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
        'card' => [
            'number' => '5569000000006063',
            'expiry' => $futureExpiry,
            'cvv' => '010',
        ],
    ]);

    $response->assertJsonMissingValidationErrors(['card.expiry']);
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
