<?php

test('returns 401 on unauthenticated request', function () {
    $user = createUser();

    $response = $this->getJson("/api/users/{$user->id}");

    $response->assertStatus(401);
});

test('returns 404 for non-existent user', function () {
    $user = createUser();

    $response = $this->actingAs($user)->getJson('/api/users/999');

    $response->assertStatus(404);
});

test('returns user details for authenticated user', function () {
    $user = createUser();
    $targetUser = createUser();

    $response = $this->actingAs($user)->getJson("/api/users/{$targetUser->id}");

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'email',
                'role',
            ],
        ])
        ->assertJson([
            'data' => [
                'id' => $targetUser->id,
                'name' => $targetUser->name,
                'email' => $targetUser->email,
                'role' => $targetUser->role,
            ],
        ]);
});
