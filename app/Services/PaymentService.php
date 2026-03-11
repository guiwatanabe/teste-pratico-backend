<?php

namespace App\Services;

use App\Models\Gateway;
use Illuminate\Support\Collection;

class PaymentService
{
    /**
     * @param  array<string, GatewayInterface>  $drivers
     */
    public function __construct(private array $drivers) {}

    /**
     * Attempt to process the payment through the gateways in
     * order of priority, returning the first success result or
     * throwing an exception if all gateways fail.
     *
     * @param  Collection<int, Gateway>  $gateways
     */
    public function attempt(array $payload, Collection $gateways): array
    {
        $active = $gateways
            ->where('is_active', true)
            ->sortBy('priority')
            ->values();

        if ($active->isEmpty()) {
            throw new \RuntimeException('No active gateways available.');
        }

        $lastException = null;

        foreach ($active as $gateway) {
            $driver = $this->drivers[$gateway->driver] ?? null;

            if (! $driver) {
                $lastException = new \RuntimeException("Gateway driver '{$gateway->driver}' not found.");

                continue;
            }

            try {
                $result = $driver->charge($payload);

                return ['gateway' => $gateway, 'result' => $result];
            } catch (\Throwable $e) {
                $lastException = $e;
            }
        }

        throw new \RuntimeException('All gateways failed: ' . $lastException?->getMessage());
    }
}
