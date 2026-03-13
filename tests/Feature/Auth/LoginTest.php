<?php

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    Cache::flush();
    $this->password = 'test_password';
    $this->user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make($this->password),
        'name' => 'Test User',
        'role' => 'ADMIN',
    ]);
});

test('returns a token on valid credentials', function () {
    $response = $this->postJson('/api/auth/login', [
        'email' => $this->user->email,
        'password' => $this->password,
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure(['access_token', 'token_type']);
});

test('generates valid token and returns user data', function () {
    $response = $this->postJson('/api/auth/login', [
        'email' => $this->user->email,
        'password' => $this->password,
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure(['access_token', 'token_type']);

    $token = $response->json('access_token');

    $userResponse = $this->withHeaders([
        'Authorization' => "Bearer {$token}",
    ])->getJson('/api/auth/user');

    $userResponse->assertStatus(200)
        ->assertJson([
            'id' => $this->user->id,
            'name' => $this->user->name,
            'email' => $this->user->email,
            'role' => $this->user->role,
        ]);
});

test('returns 401 on wrong password', function () {
    $response = $this->postJson('/api/auth/login', [
        'email' => $this->user->email,
        'password' => 'wrong_password',
    ]);

    $response->assertStatus(401);
});

test('returns 401 on non-existent email', function () {
    $response = $this->postJson('/api/auth/login', [
        'email' => 'wrong@example.com',
        'password' => $this->password,
    ]);

    $response->assertStatus(401);
});

test('returns 422 when required fields are missing', function () {
    $response = $this->postJson('/api/auth/login', [
        'email' => $this->user->email,
    ]);

    $response->assertStatus(422);
});

test('returns 401 when accessing protected route without token', function () {
    $response = $this->getJson('/api/auth/user');

    $response->assertStatus(401);
});

test('returns 401 when accessing protected route with a malformed token', function () {
    $response = $this->withHeaders([
        'Authorization' => 'Bearer malformed_token',
    ])->getJson('/api/auth/user');

    $response->assertStatus(401);
});

test('rate limits after 5 requests per minute', function () {
    for ($i = 1; $i <= 5; $i++) {
        $response = $this->postJson('/api/auth/login', [
            'email' => $this->user->email,
            'password' => 'wrong_password',
        ]);

        $response->assertStatus(401);
        $response->assertHeader('X-Ratelimit-Remaining', 5 - $i);
    }

    $response = $this->postJson('/api/auth/login', [
        'email' => $this->user->email,
        'password' => 'wrong_password',
    ]);

    $response->assertStatus(429);
    $response->assertHeader('X-Ratelimit-Remaining', 0);
});
