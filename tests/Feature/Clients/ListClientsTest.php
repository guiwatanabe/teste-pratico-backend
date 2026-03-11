<?php

use App\Models\Client;

test('returns 401 for unauthenticated request', function () {
    $response = $this->getJson('/api/clients');

    $response->assertStatus(401);
});

test('returns client list for any authenticated user', function () {
    $user = createUser('USER');

    $response = $this->actingAs($user)->getJson('/api/clients');

    $response->assertStatus(200);
});

test('paginates results with 10 results per page', function () {
    $user = createUser('USER');
    createClients(15);

    $page1 = $this->actingAs($user)->getJson('/api/clients?page=1')->assertStatus(200);
    $page2 = $this->actingAs($user)->getJson('/api/clients?page=2')->assertStatus(200);

    expect(count($page1->json('data')))->toBe(10);
    expect(count($page2->json('data')))->toBe(5);
    expect($page1->json('meta.total'))->toBe(15);
    expect($page1->json('meta.per_page'))->toBe(10);
    expect($page1->json('meta.last_page'))->toBe(2);
});
