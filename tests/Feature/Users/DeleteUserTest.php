<?php

test('allows ADMIN to delete user', function () {
    $adminUser = \App\Models\User::factory()->create(['role' => 'ADMIN']);
    $targetUser = \App\Models\User::factory()->create(['role' => 'USER']);

    $this->actingAs($adminUser)->deleteJson("/api/users/{$targetUser->id}")->assertStatus(204);
});

test('allows MANAGER to delete a user with role USER', function () {
    $manager = \App\Models\User::factory()->create(['role' => 'MANAGER']);
    $targetUser = \App\Models\User::factory()->create(['role' => 'USER']);

    $this->actingAs($manager)->deleteJson("/api/users/{$targetUser->id}")->assertStatus(204);
});

test('prevents MANAGER from deleting an ADMIN', function () {
    $manager = \App\Models\User::factory()->create(['role' => 'MANAGER']);
    $adminUser = \App\Models\User::factory()->create(['role' => 'ADMIN']);

    $this->actingAs($manager)->deleteJson("/api/users/{$adminUser->id}")->assertStatus(403);
});

test('prevents MANAGER from deleting MANAGER and FINANCE users', function () {
    $manager = \App\Models\User::factory()->create(['role' => 'MANAGER']);

    foreach (['MANAGER', 'FINANCE'] as $role) {
        $target = \App\Models\User::factory()->create(['role' => $role]);

        $this->actingAs($manager)->deleteJson("/api/users/{$target->id}")->assertStatus(403);
    }
});

test('prevents FINANCE and USER from deleting users', function () {
    $financeUser = \App\Models\User::factory()->create(['role' => 'FINANCE']);
    $regularUser = \App\Models\User::factory()->create(['role' => 'USER']);
    $targetUser = \App\Models\User::factory()->create(['role' => 'USER']);

    foreach ([$financeUser, $regularUser] as $user) {
        $this->actingAs($user)->deleteJson("/api/users/{$targetUser->id}")->assertStatus(403);
    }
});

test('soft-deletes the user (record still exists in DB)', function () {
    $adminUser = \App\Models\User::factory()->create(['role' => 'ADMIN']);
    $targetUser = \App\Models\User::factory()->create(['role' => 'USER']);

    $this->actingAs($adminUser)->deleteJson("/api/users/{$targetUser->id}")->assertStatus(204);

    $this->assertSoftDeleted('users', ['id' => $targetUser->id]);
});

test('prevents deleting own account', function () {
    $adminUser = \App\Models\User::factory()->create(['role' => 'ADMIN']);

    $this->actingAs($adminUser)->deleteJson("/api/users/{$adminUser->id}")->assertStatus(403);
});

test('returns 404 for a non-existent user', function () {
    $adminUser = \App\Models\User::factory()->create(['role' => 'ADMIN']);

    $this->actingAs($adminUser)->deleteJson("/api/users/999999")->assertStatus(404);
});

test('returns 404 for a soft-deleted user', function () {
    $adminUser = \App\Models\User::factory()->create(['role' => 'ADMIN']);
    $deletedUser = \App\Models\User::factory()->create([
        'role' => 'USER',
        'deleted_at' => now(),
    ]);

    $this->actingAs($adminUser)->deleteJson("/api/users/{$deletedUser->id}")->assertStatus(404);
});
