<?php

use Illuminate\Support\Facades\Http;

function fakeGatewayRefund(): void
{
    Http::fake([
        '*/login' => Http::response(['token' => 'fake-token'], 200),
        '*/transactions/*/charge_back' => Http::response(['id' => 'abc'], 201),
        '*/transacoes/reembolso' => Http::response(['id' => 'abc'], 201),
    ]);
}

test('returns 401 for unauthenticated request', function () {
    $transaction = createTransactions()->first();

    $response = $this->postJson("/api/transactions/{$transaction->id}/refund", [
        'reason' => 'Customer request.',
    ]);

    $response->assertStatus(401);
});

test('allows ADMIN and FINANCE to issue a refund', function () {
    fakeGatewayRefund();

    $admin = createUser('ADMIN');
    $finance = createUser('FINANCE');
    $transactions = createTransactions(2);
    $transaction = $transactions->first();

    $responseAdmin = $this->actingAs($admin)->postJson("/api/transactions/{$transaction->id}/refund", [
        'reason' => 'Customer request.',
    ]);

    $transaction = $transactions->last();

    $responseFinance = $this->actingAs($finance)->postJson("/api/transactions/{$transaction->id}/refund", [
        'reason' => 'Customer request.',
    ]);

    $responseAdmin->assertStatus(200);
    $responseFinance->assertStatus(200);
});

test('prevents MANAGER and USER from issuing a refund', function () {
    $manager = createUser('MANAGER');
    $user = createUser('USER');
    $transactions = createTransactions(2);
    $transaction = $transactions->first();

    $responseManager = $this->actingAs($manager)->postJson("/api/transactions/{$transaction->id}/refund", [
        'reason' => 'Customer request.',
    ]);

    $transaction = $transactions->last();

    $responseUser = $this->actingAs($user)->postJson("/api/transactions/{$transaction->id}/refund", [
        'reason' => 'Customer request.',
    ]);

    $responseManager->assertStatus(403);
    $responseUser->assertStatus(403);
});

test('returns 404 for a non-existent transaction', function () {
    $admin = createUser('ADMIN');

    $response = $this->actingAs($admin)->postJson('/api/transactions/999/refund', [
        'reason' => 'Customer request.',
    ]);

    $response->assertStatus(404);
});

test('returns 409 when transaction is already refunded', function () {
    $admin = createUser('ADMIN');
    $transaction = createTransactions()->first();

    $transaction->status = 'refunded';
    $transaction->save();

    $response = $this->actingAs($admin)->postJson("/api/transactions/{$transaction->id}/refund", [
        'reason' => 'Customer request.',
    ]);

    $response->assertStatus(409);
});

test('calls the correct gateway refund API based on which gateway processed the transaction', function () {
    $admin = createUser('ADMIN');
    $transaction = createTransactions()->first();

    $driverMock = Mockery::mock(\App\Services\Gateways\GatewayInterface::class);
    $driverMock->shouldReceive('refund')
        ->once()
        ->withArgs(function ($payload) use ($transaction) {
            return $payload['transactionId'] === $transaction->external_id;
        })
        ->andReturn(['success' => true]);

    app()->forgetInstance(\App\Services\RefundService::class);
    app()->singleton(\App\Services\RefundService::class, fn () => new \App\Services\RefundService([
        $transaction->gateway->driver => $driverMock,
    ])
    );

    $response = $this->actingAs($admin)->postJson("/api/transactions/{$transaction->id}/refund", [
        'reason' => 'Customer request.',
    ]);

    $response->assertStatus(200);
});

test('updates transaction status to refunded on success', function () {
    fakeGatewayRefund();

    $admin = createUser('ADMIN');
    $transaction = createTransactions()->first();

    $response = $this->actingAs($admin)->postJson("/api/transactions/{$transaction->id}/refund", [
        'reason' => 'Customer request.',
    ]);

    $response->assertStatus(200);
    expect($transaction->fresh()->status)->toBe('refunded');
});

test('does NOT update transaction status if the gateway refund API fails', function () {
    $admin = createUser('ADMIN');
    $transaction = createTransactions()->first();

    $driverMock = Mockery::mock(\App\Services\Gateways\GatewayInterface::class);
    $driverMock->shouldReceive('refund')
        ->once()
        ->withArgs(function ($payload) use ($transaction) {
            return $payload['transactionId'] === $transaction->external_id;
        })
        ->andThrow(new \RuntimeException('Refund failed'));

    app()->forgetInstance(\App\Services\RefundService::class);
    app()->singleton(\App\Services\RefundService::class, fn () => new \App\Services\RefundService([
        $transaction->gateway->driver => $driverMock,
    ])
    );

    $response = $this->actingAs($admin)->postJson("/api/transactions/{$transaction->id}/refund", [
        'reason' => 'Customer request.',
    ]);

    $response->assertStatus(502);
    expect($transaction->fresh()->status)->toBe('completed');
});
