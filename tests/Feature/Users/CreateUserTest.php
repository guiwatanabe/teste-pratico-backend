<?php

test('allows ADMIN to create a user with any role', function () {
    $adminUser = \App\Models\User::factory()->create(['role' => 'ADMIN']);

    foreach (['ADMIN', 'MANAGER', 'FINANCE', 'USER'] as $i => $role) {
        $this->actingAs($adminUser)->postJson('/api/users', [
            'name' => "User $role",
            'email' => "user{$i}@example.com",
            'password' => 'password',
            'role' => $role,
        ])->assertStatus(201);
    }
});

test('allows MANAGER to create a user with role USER', function () {
    $manager = \App\Models\User::factory()->create(['role' => 'MANAGER']);

    $this->actingAs($manager)->postJson('/api/users', [
        'name' => 'Regular User',
        'email' => 'regular@example.com',
        'password' => 'password',
        'role' => 'USER',
    ])->assertStatus(201);
});

test('prevents MANAGER from creating a user with a privileged role', function () {
    $manager = \App\Models\User::factory()->create(['role' => 'MANAGER']);

    foreach (['ADMIN', 'MANAGER', 'FINANCE'] as $i => $role) {
        $this->actingAs($manager)->postJson('/api/users', [
            'name' => "$role User",
            'email' => "test{$i}@example.com",
            'password' => 'password',
            'role' => $role,
        ])->assertStatus(422);
    }
});

test('prevents FINANCE and USER from creating users', function () {
    $financeUser = \App\Models\User::factory()->create(['role' => 'FINANCE']);
    $regularUser = \App\Models\User::factory()->create(['role' => 'USER']);

    foreach ([$financeUser, $regularUser] as $user) {
        $this->actingAs($user)->postJson('/api/users', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'role' => 'USER',
        ])->assertStatus(403);
    }
});

test('returns 422 on invalid payload', function () {
    $adminUser = \App\Models\User::factory()->create(['role' => 'ADMIN']);

    $this->actingAs($adminUser)->postJson('/api/users', [
        'name' => '',
        'email' => 'not-an-email',
        'password' => 'short',
        'role' => 'INVALID_ROLE',
    ])->assertStatus(422);
});

test('returns 422 when email is already taken', function () {
    $adminUser = \App\Models\User::factory()->create(['role' => 'ADMIN']);
    $existingUser = \App\Models\User::factory()->create(['email' => 'test@example.com']);

    $this->actingAs($adminUser)->postJson('/api/users', [
        'name' => 'New User',
        'email' => $existingUser->email,
        'password' => 'password',
        'role' => 'USER',
    ])->assertStatus(422);
});

test('persists created user to database', function () {
    $adminUser = \App\Models\User::factory()->create(['role' => 'ADMIN']);

    $response = $this->actingAs($adminUser)->postJson('/api/users', [
        'name' => 'New User',
        'email' => 'newuser@example.com',
        'password' => 'password',
        'role' => 'USER',
    ])->assertStatus(201);

    $this->assertDatabaseHas('users', [
        'email' => 'newuser@example.com',
    ]);
});
