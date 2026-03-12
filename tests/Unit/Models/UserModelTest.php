<?php

test('returns initials from name', function () {
    $user = \App\Models\User::factory()->make(['name' => 'Test User']);
    expect($user->initials())->toBe('TU');
});