<?php

namespace App\Services;

use App\Models\Transaction;

class RefundService
{
    /**
     * @param  array<string, GatewayInterface>  $drivers
     */
    public function __construct(private array $drivers) {}

    /**
     * Attempts a refund at the available gateways, throwing an exception
     * if the refund fails or if the transaction has no associated gateway.
     */
    public function attemptRefund(Transaction $transaction): array
    {
        $gateway = $transaction->gateway;

        $driver = $this->drivers[$gateway->driver] ?? null;

        if (! $driver) {
            throw new \RuntimeException("Gateway driver '{$gateway->driver}' not found.");
        }

        try {
            $result = $driver->refund([
                'transactionId' => $transaction->external_id,
            ]);

            return ['gateway' => $gateway, 'result' => $result];
        } catch (\Throwable $e) {
            throw new \RuntimeException('Refund failed: '.$e->getMessage());
        }
    }
}
