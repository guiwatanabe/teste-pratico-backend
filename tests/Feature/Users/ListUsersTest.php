<?php

function createUserWithRole($role)
{
    return \App\Models\User::factory()->create([
        'role' => $role,
    ]);
}

test('allows ADMIN and MANAGER to list users', function () {
    $adminUser = createUserWithRole('ADMIN');
    $managerUser = createUserWithRole('MANAGER');

    $this->actingAs($adminUser)->getJson('/api/users')->assertStatus(200);
    $this->actingAs($managerUser)->getJson('/api/users')->assertStatus(200);
});

test('returns correct structure for user', function () {
    $adminUser = createUserWithRole('ADMIN');

    $response = $this->actingAs($adminUser)->getJson('/api/users')->assertStatus(200);
    $response->assertJsonStructure([
        'data' => [
            '*' => ['id', 'name', 'email', 'role'],
        ],
        'meta' => ['total', 'per_page', 'current_page', 'last_page'],
        'links',
    ]);
});

test('prevents FINANCE and USER from listing users', function () {
    $financeUser = createUserWithRole('FINANCE');
    $regularUser = createUserWithRole('USER');

    $this->actingAs($financeUser)->getJson('/api/users')->assertStatus(403);
    $this->actingAs($regularUser)->getJson('/api/users')->assertStatus(403);
});

test('returns 401 for unauthenticated requests', function () {
    $this->getJson('/api/users')->assertStatus(401);
});

test('does not include soft-deleted users in the list', function () {
    $adminUser = createUserWithRole('ADMIN');
    $deletedUser = \App\Models\User::factory()->create([
        'role' => 'USER',
        'deleted_at' => now(),
    ]);

    $response = $this->actingAs($adminUser)->getJson('/api/users')->assertStatus(200);
    $response->assertJsonMissing(['id' => $deletedUser->id]);
});

test('does not expose sensitive information in the response', function () {
    $adminUser = createUserWithRole('ADMIN');

    $response = $this->actingAs($adminUser)->getJson('/api/users')->assertStatus(200);
    $response->assertJsonMissing(['password']);
    $response->assertJsonMissing(['remember_token']);
});

test('paginates results with 10 users per page', function () {
    $adminUser = createUserWithRole('ADMIN');
    \App\Models\User::factory()->count(14)->create();
    // 15 users total (admin + 14)

    $page1 = $this->actingAs($adminUser)->getJson('/api/users?page=1')->assertStatus(200);
    $page2 = $this->actingAs($adminUser)->getJson('/api/users?page=2')->assertStatus(200);

    expect(count($page1->json('data')))->toBe(10);
    expect(count($page2->json('data')))->toBe(5);
    expect($page1->json('meta.total'))->toBe(15);
    expect($page1->json('meta.per_page'))->toBe(10);
    expect($page1->json('meta.last_page'))->toBe(2);
});
