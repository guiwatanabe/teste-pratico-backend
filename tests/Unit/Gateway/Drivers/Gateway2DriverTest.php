<?php

use App\Services\Gateways\Gateway2Driver;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config([
        'gateways.drivers.gateway_2.base_url' => 'http://gateway2.test',
        'gateways.drivers.gateway_2.auth_type' => 'header',
        'gateways.drivers.gateway_2.header_token' => 'token_12345',
        'gateways.drivers.gateway_2.header_secret' => 'abcdef123456',
    ]);
});

$payload = [
    'amount' => 1000,
    'name' => 'Test Client',
    'email' => 'test.client@example.com',
    'cardNumber' => '1111222233334444',
    'cvv' => '123',
];

// --------------------
// constructor
test('throws when gateway 2 config is missing', function () {
    config(['gateways.drivers.gateway_2.base_url' => '']);

    expect(fn () => new Gateway2Driver)->toThrow(\Exception::class, 'Gateway 2 is not properly configured');
});

test('throws when gateway 2 auth_type is wrong', function () {
    config(['gateways.drivers.gateway_2.auth_type' => 'auth_token']);

    expect(fn () => new Gateway2Driver)->toThrow(\Exception::class, 'Gateway 2 is not properly configured');
});

// --------------------
// charge
test('charge returns success data on 201', function () use ($payload) {
    Http::fake([
        'http://gateway2.test/transacoes' => Http::response(['id' => 'ext-456', 'status' => 'paid'], 201),
    ]);

    $driver = new Gateway2Driver;
    $result = $driver->charge($payload);

    expect($result['status'])->toBe('success')
        ->and($result['statusCode'])->toBe(201)
        ->and($result['data']['id'])->toBe('ext-456');
});

test('charge throws on non-201 response', function () use ($payload) {
    Http::fake([
        'http://gateway2.test/transacoes' => Http::response(['error' => 'card declined'], 422),
    ]);

    $driver = new Gateway2Driver;

    expect(fn () => $driver->charge($payload))
        ->toThrow(\Exception::class, 'Payment failed with Gateway 2');
});

test('charge throws on 200 response (not 201)', function () use ($payload) {
    Http::fake([
        'http://gateway2.test/transacoes' => Http::response(['id' => 'ext-456'], 200),
    ]);

    $driver = new Gateway2Driver;

    expect(fn () => $driver->charge($payload))
        ->toThrow(\Exception::class, 'Payment failed with Gateway 2');
});

// --------------------
// refund
test('refund returns success data on 201', function () {
    Http::fake([
        'http://gateway2.test/transacoes/reembolso' => Http::response(['status' => 'refunded'], 201),
    ]);

    $driver = new Gateway2Driver;
    $result = $driver->refund(['transactionId' => 'ext-456']);

    expect($result['status'])->toBe('success')
        ->and($result['statusCode'])->toBe(201);
});

test('refund throws on non-201 response', function () {
    Http::fake([
        'http://gateway2.test/transacoes/reembolso' => Http::response([], 500),
    ]);

    $driver = new Gateway2Driver;

    expect(fn () => $driver->refund(['transactionId' => 'ext-456']))
        ->toThrow(\Exception::class, 'Refund failed with Gateway 2');
});

// --------------------
// listTransactions
test('listTransactions returns success data', function () {
    Http::fake([
        'http://gateway2.test/transacoes' => Http::response([['id' => 'ext-1']], 200),
    ]);

    $driver = new Gateway2Driver;
    $result = $driver->listTransactions();

    expect($result['status'])->toBe('success')
        ->and($result['data'])->toHaveCount(1);
});

test('listTransactions throws on non-2xx response', function () {
    Http::fake([
        'http://gateway2.test/transacoes' => Http::response([], 503),
    ]);

    $driver = new Gateway2Driver;

    expect(fn () => $driver->listTransactions())
        ->toThrow(\Exception::class, 'Failed to list transactions with Gateway 2');
});
