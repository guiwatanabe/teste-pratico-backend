<?php

use App\Models\Gateway;
use App\Services\PaymentService;
use App\Services\Gateways\GatewayInterface;
use Illuminate\Database\Eloquent\Factories\Sequence;

beforeEach(function () {
    $this->shouldFail = [
        'gateway_1' => false,
        'gateway_2' => false,
    ];

    $shouldFail = &$this->shouldFail;

    $chargeCalls = ['gateway_1' => 0, 'gateway_2' => 0];
    $this->chargeCalls = &$chargeCalls;

    $this->paymentService = new PaymentService([
        'gateway_1' => new class($shouldFail, $chargeCalls) implements GatewayInterface
        {
            private array $shouldFail;

            private array $chargeCalls;

            public function __construct(array &$shouldFail, array &$chargeCalls)
            {
                $this->shouldFail = &$shouldFail;
                $this->chargeCalls = &$chargeCalls;
            }

            public function charge(array $payload): array
            {
                $this->chargeCalls['gateway_1']++;
                if ($this->shouldFail['gateway_1'] ?? false) {
                    throw new \RuntimeException('Gateway 1 failed');
                }

                return ['status' => 'success'];
            }

            public function refund(array $payload): array
            {
                if ($this->shouldFail['gateway_1'] ?? false) {
                    throw new \RuntimeException('Gateway 1 failed');
                }

                return ['status' => 'success'];
            }

            public function listTransactions(): array
            {
                return [];
            }
        },
        'gateway_2' => new class($shouldFail, $chargeCalls) implements GatewayInterface
        {
            private array $shouldFail;

            private array $chargeCalls;

            public function __construct(array &$shouldFail, array &$chargeCalls)
            {
                $this->shouldFail = &$shouldFail;
                $this->chargeCalls = &$chargeCalls;
            }

            public function charge(array $payload): array
            {
                $this->chargeCalls['gateway_2']++;
                if ($this->shouldFail['gateway_2'] ?? false) {
                    throw new \RuntimeException('Gateway 2 failed');
                }

                return ['status' => 'success'];
            }

            public function refund(array $payload): array
            {
                if ($this->shouldFail['gateway_2'] ?? false) {
                    throw new \RuntimeException('Gateway 2 failed');
                }

                return ['status' => 'success'];
            }

            public function listTransactions(): array
            {
                return [];
            }
        },
    ]);
});

test('uses the first prioritized gateway driver', function () {
    $gateways = Gateway::factory()->count(2)->state(new Sequence(
        ['driver' => 'gateway_2', 'priority' => 1, 'is_active' => true],
        ['driver' => 'gateway_1', 'priority' => 0, 'is_active' => true]
    ))->make();

    $result = $this->paymentService->attempt(['amount' => 1000], $gateways);

    expect($result['gateway']->driver)->toBe('gateway_1');
    expect($this->chargeCalls['gateway_1'])->toBe(1);
    expect($this->chargeCalls['gateway_2'])->toBe(0);
});

test('skips inactive gateways and continues to the next', function () {
    $gateways = Gateway::factory()->count(2)->state(new Sequence(
        ['driver' => 'gateway_1', 'priority' => 0, 'is_active' => false],
        ['driver' => 'gateway_2', 'priority' => 1, 'is_active' => true]
    ))->make();

    $result = $this->paymentService->attempt(['amount' => 1000], $gateways);

    expect($result['gateway']->driver)->toBe('gateway_2');
});

test('tries the next gateway when the current one throws an exception', function () {
    $this->shouldFail['gateway_1'] = true;

    $gateways = Gateway::factory()->count(2)->state(new Sequence(
        ['driver' => 'gateway_1', 'priority' => 0, 'is_active' => true],
        ['driver' => 'gateway_2', 'priority' => 1, 'is_active' => true]
    ))->make();

    $result = $this->paymentService->attempt(['amount' => 1000], $gateways);

    expect($result['gateway']->driver)->toBe('gateway_2');
});

test('stops and returns success as soon as one gateway succeeds', function () {
    $this->shouldFail['gateway_2'] = true;

    $gateways = Gateway::factory()->count(2)->state(new Sequence(
        ['driver' => 'gateway_1', 'priority' => 0, 'is_active' => true],
        ['driver' => 'gateway_2', 'priority' => 1, 'is_active' => true]
    ))->make();

    $result = $this->paymentService->attempt(['amount' => 1000], $gateways);

    expect($result['result']['status'])->toBe('success');
    expect($result['gateway']->driver)->toBe('gateway_1');
    expect($this->chargeCalls['gateway_2'])->toBe(0);
});

test('throws after exhausting all gateways', function () {
    $this->shouldFail['gateway_1'] = true;
    $this->shouldFail['gateway_2'] = true;

    $gateways = Gateway::factory()->count(2)->state(new Sequence(
        ['driver' => 'gateway_1', 'priority' => 0, 'is_active' => true],
        ['driver' => 'gateway_2', 'priority' => 1, 'is_active' => true]
    ))->make();

    $this->paymentService->attempt(['amount' => 1000], $gateways);
})->throws(\RuntimeException::class, 'All gateways failed: Gateway 2 failed');

test('handles an empty active gateway list gracefully', function () {
    $gateways = Gateway::factory()->count(2)->state(new Sequence(
        ['driver' => 'gateway_1', 'priority' => 0, 'is_active' => false],
        ['driver' => 'gateway_2', 'priority' => 1, 'is_active' => false]
    ))->make();

    $this->paymentService->attempt(['amount' => 1000], $gateways);
})->throws(\RuntimeException::class, 'No active gateways available.');

test('throws when all active gateways have no registered driver', function () {
    $gateways = Gateway::factory()->count(2)->state(new Sequence(
        ['driver' => 'unknown_1', 'priority' => 0, 'is_active' => true],
        ['driver' => 'unknown_2', 'priority' => 1, 'is_active' => true]
    ))->make();

    $this->paymentService->attempt(['amount' => 1000], $gateways);
})->throws(\RuntimeException::class, 'All gateways failed');
