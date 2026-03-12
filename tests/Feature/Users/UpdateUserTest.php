<?php

test('allows ADMIN to update any user', function () {
    $adminUser = \App\Models\User::factory()->create(['role' => 'ADMIN']);
    $targetUser = \App\Models\User::factory()->create(['role' => 'USER']);

    $this->actingAs($adminUser)->patchJson("/api/users/{$targetUser->id}", [
        'name' => 'Updated Name',
    ])->assertStatus(200);
});

test('allows MANAGER to update a user with role USER', function () {
    $manager = \App\Models\User::factory()->create(['role' => 'MANAGER']);
    $targetUser = \App\Models\User::factory()->create(['role' => 'USER']);

    $this->actingAs($manager)->patchJson("/api/users/{$targetUser->id}", [
        'name' => 'Updated Name',
    ])->assertStatus(200);
});

test('prevents MANAGER from updating an ADMIN user', function () {
    $manager = \App\Models\User::factory()->create(['role' => 'MANAGER']);
    $adminUser = \App\Models\User::factory()->create(['role' => 'ADMIN']);

    $this->actingAs($manager)->patchJson("/api/users/{$adminUser->id}", [
        'name' => 'Updated Name',
    ])->assertStatus(403);
});

test('prevents MANAGER from updating MANAGER and FINANCE users', function () {
    $manager = \App\Models\User::factory()->create(['role' => 'MANAGER']);

    foreach (['MANAGER', 'FINANCE'] as $role) {
        $target = \App\Models\User::factory()->create(['role' => $role]);

        $this->actingAs($manager)->patchJson("/api/users/{$target->id}", [
            'name' => 'Updated Name',
        ])->assertStatus(403);
    }
});

test('prevents FINANCE and USER from updating users', function () {
    $financeUser = \App\Models\User::factory()->create(['role' => 'FINANCE']);
    $regularUser = \App\Models\User::factory()->create(['role' => 'USER']);
    $targetUser = \App\Models\User::factory()->create(['role' => 'USER']);

    foreach ([$financeUser, $regularUser] as $user) {
        $this->actingAs($user)->patchJson("/api/users/{$targetUser->id}", [
            'name' => 'Updated Name',
        ])->assertStatus(403);
    }
});

test('returns 404 for non-existent or soft-deleted user', function () {
    $adminUser = \App\Models\User::factory()->create(['role' => 'ADMIN']);
    $deletedUser = \App\Models\User::factory()->create([
        'role' => 'USER',
        'deleted_at' => now(),
    ]);

    $this->actingAs($adminUser)->patchJson('/api/users/999999', [
        'name' => 'Updated Name',
    ])->assertStatus(404);

    $this->actingAs($adminUser)->patchJson("/api/users/{$deletedUser->id}", [
        'name' => 'Updated Name',
    ])->assertStatus(404);
});

test('returns 422 on invalid payload', function () {
    $adminUser = \App\Models\User::factory()->create(['role' => 'ADMIN']);
    $targetUser = \App\Models\User::factory()->create(['role' => 'USER']);

    $this->actingAs($adminUser)->patchJson("/api/users/{$targetUser->id}", [
        'email' => 'not-an-email',
    ])->assertStatus(422);
});

test('prevents changing own role via update endpoint', function () {
    $adminUser = \App\Models\User::factory()->create(['role' => 'ADMIN']);

    $this->actingAs($adminUser)->patchJson("/api/users/{$adminUser->id}", [
        'role' => 'USER',
    ])->assertStatus(422);
});

test('returns updated fields in the response', function () {
    $adminUser = \App\Models\User::factory()->create(['role' => 'ADMIN']);
    $targetUser = \App\Models\User::factory()->create(['role' => 'USER']);

    $this->actingAs($adminUser)->patchJson("/api/users/{$targetUser->id}", [
        'name' => 'New Name',
    ])->assertStatus(200)->assertJsonFragment(['name' => 'New Name']);
});

test('persists the update to the database', function () {
    $adminUser = \App\Models\User::factory()->create(['role' => 'ADMIN']);
    $targetUser = \App\Models\User::factory()->create(['role' => 'USER']);

    $this->actingAs($adminUser)->patchJson("/api/users/{$targetUser->id}", [
        'name' => 'New Name',
    ])->assertStatus(200);

    $this->assertDatabaseHas('users', [
        'id' => $targetUser->id,
        'name' => 'New Name',
    ]);
});

test('hashes and updates password when provided', function () {
    $adminUser = \App\Models\User::factory()->create(['role' => 'ADMIN']);
    $targetUser = \App\Models\User::factory()->create(['role' => 'USER']);

    $this->actingAs($adminUser)->patchJson("/api/users/{$targetUser->id}", [
        'password' => 'new_password',
    ])->assertStatus(200);

    $this->assertTrue(
        \Illuminate\Support\Facades\Hash::check('new_password', $targetUser->fresh()->password)
    );
});
