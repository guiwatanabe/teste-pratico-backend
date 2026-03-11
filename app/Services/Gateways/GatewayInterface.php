<?php

namespace App\Services\Gateways;

interface GatewayInterface
{
    public function charge(array $payload): array;

    public function refund(array $payload): array;

    public function listTransactions(): array;
}
