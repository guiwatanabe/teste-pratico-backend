<?php

test('returns 401 for unauthenticated request', function () {
    $gateway = \App\Models\Gateway::factory()->create();

    $response = $this->patchJson("/api/gateways/{$gateway->id}", ['is_active' => true]);
    $response->assertStatus(401);
});

test('allows ADMIN to activate and deactivate a gateway', function () {
    $admin = createUser('ADMIN');
    $gateway = \App\Models\Gateway::factory()->create(['is_active' => false]);

    $response = $this->actingAs($admin)->patchJson("/api/gateways/{$gateway->id}", ['is_active' => true]);
    $response->assertStatus(200);
    expect($gateway->fresh()->is_active)->toBeTrue();

    $response = $this->actingAs($admin)->patchJson("/api/gateways/{$gateway->id}", ['is_active' => false]);
    $response->assertStatus(200);
    expect($gateway->fresh()->is_active)->toBeFalse();
});

test('prevents non-ADMIN from managing gateways', function () {
    $user = createUser('USER');
    $gateway = \App\Models\Gateway::factory()->create(['is_active' => false]);

    $response = $this->actingAs($user)->patchJson("/api/gateways/{$gateway->id}", ['is_active' => true]);
    $response->assertStatus(403);
    expect($gateway->fresh()->is_active)->toBeFalse();
});

test('returns 404 for a non-existent gateway', function () {
    $admin = createUser('ADMIN');

    $response = $this->actingAs($admin)->patchJson('/api/gateways/999', ['is_active' => true]);
    $response->assertStatus(404);
});
