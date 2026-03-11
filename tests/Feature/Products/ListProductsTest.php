<?php

function createUser($role = 'ADMIN')
{
    return \App\Models\User::factory()->create([
        'role' => $role,
    ]);
}

function createProducts($count = 3)
{
    return \App\Models\Product::factory()->count($count)->create();
}

test('returns product list for any authenticated user', function () {
    $user = createUser();
    $products = createProducts();

    $response = $this->actingAs($user)->getJson('/api/products');

    $response->assertStatus(200)
        ->assertJsonCount(3, 'data')
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'amount', 'price', 'created_at', 'updated_at'],
            ],
            'meta' => ['total', 'per_page', 'current_page', 'last_page'],
            'links',
        ]);
});

test('returns 401 for unauthenticated request', function () {
    $response = $this->getJson('/api/products');

    $response->assertStatus(401);
});

test('does not include soft-deleted products in the list', function () {
    $user = createUser();
    $products = createProducts(5);
    $products[0]->delete();

    $response = $this->actingAs($user)->getJson('/api/products');

    $response->assertStatus(200)
        ->assertJsonCount(4, 'data');
});

test('paginates results with 10 results per page', function () {
    $adminUser = createUser();
    createProducts(15);

    $page1 = $this->actingAs($adminUser)->getJson('/api/products?page=1')->assertStatus(200);
    $page2 = $this->actingAs($adminUser)->getJson('/api/products?page=2')->assertStatus(200);

    expect(count($page1->json('data')))->toBe(10);
    expect(count($page2->json('data')))->toBe(5);
    expect($page1->json('meta.total'))->toBe(15);
    expect($page1->json('meta.per_page'))->toBe(10);
    expect($page1->json('meta.last_page'))->toBe(2);
});
