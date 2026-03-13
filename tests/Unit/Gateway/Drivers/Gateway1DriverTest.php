<?php

use App\Services\Gateways\Gateway1Driver;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config([
        'gateways.drivers.gateway_1.base_url' => 'http://gateway1.test',
        'gateways.drivers.gateway_1.auth_type' => 'auth_token',
        'gateways.drivers.gateway_1.auth_email' => 'test@gateway1.test',
        'gateways.drivers.gateway_1.auth_token' => 'ABCDEF123456',
    ]);
});

function gateway1WithAuth(): Gateway1Driver
{
    Http::fake([
        'http://gateway1.test/login' => Http::response(['token' => 'fake-bearer'], 200),
    ]);

    return new Gateway1Driver;
}

$payload = [
    'amount' => 1000,
    'name' => 'Test Client',
    'email' => 'test.client@example.com',
    'cardNumber' => '1111222233334444',
    'cvv' => '123',
];

// --------------------
// constructor
test('throws when gateway 1 config is missing', function () {
    config(['gateways.drivers.gateway_1.base_url' => '']);

    expect(fn () => new Gateway1Driver)->toThrow(\Exception::class, 'Gateway 1 is not properly configured');
});

test('throws when gateway 1 auth_type is wrong', function () {
    config(['gateways.drivers.gateway_1.auth_type' => 'header']);

    expect(fn () => new Gateway1Driver)->toThrow(\Exception::class, 'Gateway 1 is not properly configured');
});

// --------------------
// getAuthToken
test('throws when gateway 1 login returns non-JSON body', function () {
    Http::fake([
        'http://gateway1.test/login' => Http::response('Service Unavailable', 503),
        'http://gateway1.test/transactions' => Http::response(['id' => '1'], 201),
    ]);

    $driver = new Gateway1Driver;

    expect(fn () => $driver->charge([
        'amount' => 1000, 'name' => 'Test Client', 'email' => 'test.client@example.com', 'cardNumber' => '1111222233334444', 'cvv' => '123',
    ]))->toThrow(\Exception::class, 'Failed to retrieve authentication token from Gateway 1');
});

test('throws when gateway 1 login returns non-2xx', function () {
    Http::fake([
        'http://gateway1.test/login' => Http::response([], 401),
    ]);

    expect(fn () => new Gateway1Driver)->not->toThrow(\Exception::class);

    $driver = new Gateway1Driver;

    Http::fake([
        'http://gateway1.test/login' => Http::response([], 401),
        'http://gateway1.test/transactions' => Http::response(['id' => '1'], 201),
    ]);

    expect(fn () => $driver->charge([
        'amount' => 1000, 'name' => 'Test Client', 'email' => 'test.client@example.com', 'cardNumber' => '1111222233334444', 'cvv' => '123',
    ]))->toThrow(\Exception::class, 'Failed to retrieve authentication token from Gateway 1');
});

// --------------------
// charge
test('charge returns success data on 201', function () use ($payload) {
    Http::fake([
        'http://gateway1.test/login' => Http::response(['token' => 'fake-bearer'], 200),
        'http://gateway1.test/transactions' => Http::response(['id' => 'ext-123', 'status' => 'paid'], 201),
    ]);

    $driver = new Gateway1Driver;
    $result = $driver->charge($payload);

    expect($result['status'])->toBe('success')
        ->and($result['statusCode'])->toBe(201)
        ->and($result['data']['id'])->toBe('ext-123');
});

test('charge throws on non-201 response', function () use ($payload) {
    Http::fake([
        'http://gateway1.test/login' => Http::response(['token' => 'fake-bearer'], 200),
        'http://gateway1.test/transactions' => Http::response(['error' => 'card declined'], 422),
    ]);

    $driver = new Gateway1Driver;

    expect(fn () => $driver->charge($payload))
        ->toThrow(\Exception::class, 'Payment failed with Gateway 1');
});

test('charge throws on 200 response (not 201)', function () use ($payload) {
    Http::fake([
        'http://gateway1.test/login' => Http::response(['token' => 'fake-bearer'], 200),
        'http://gateway1.test/transactions' => Http::response(['id' => 'ext-123'], 200),
    ]);

    $driver = new Gateway1Driver;

    expect(fn () => $driver->charge($payload))
        ->toThrow(\Exception::class, 'Payment failed with Gateway 1');
});

// --------------------
// refund
test('refund returns success data on 201', function () {
    Http::fake([
        'http://gateway1.test/login' => Http::response(['token' => 'fake-bearer'], 200),
        'http://gateway1.test/transactions/ext-123/charge_back' => Http::response(['status' => 'refunded'], 201),
    ]);

    $driver = new Gateway1Driver;
    $result = $driver->refund(['transactionId' => 'ext-123']);

    expect($result['status'])->toBe('success')
        ->and($result['statusCode'])->toBe(201);
});

test('refund throws on non-201 response', function () {
    Http::fake([
        'http://gateway1.test/login' => Http::response(['token' => 'fake-bearer'], 200),
        'http://gateway1.test/transactions/ext-123/charge_back' => Http::response([], 500),
    ]);

    $driver = new Gateway1Driver;

    expect(fn () => $driver->refund(['transactionId' => 'ext-123']))
        ->toThrow(\Exception::class, 'Refund failed with Gateway 1');
});

// --------------------
// listTransactions
test('listTransactions returns success data', function () {
    Http::fake([
        'http://gateway1.test/login' => Http::response(['token' => 'fake-bearer'], 200),
        'http://gateway1.test/transactions' => Http::response([['id' => 'ext-1']], 200),
    ]);

    $driver = new Gateway1Driver;
    $result = $driver->listTransactions();

    expect($result['status'])->toBe('success')
        ->and($result['data'])->toHaveCount(1);
});

test('listTransactions throws on non-2xx response', function () {
    Http::fake([
        'http://gateway1.test/login' => Http::response(['token' => 'fake-bearer'], 200),
        'http://gateway1.test/transactions' => Http::response([], 503),
    ]);

    $driver = new Gateway1Driver;

    expect(fn () => $driver->listTransactions())
        ->toThrow(\Exception::class, 'Failed to retrieve transactions from Gateway 1');
});
