<?php

namespace App\Services\Gateways;

use Illuminate\Support\Facades\Http;

class Gateway2Driver implements GatewayInterface
{
    private $baseUrl;

    private $headerToken;

    private $headerSecret;

    public function __construct()
    {
        $this->loadConfiguration();
    }

    public function charge(array $payload): array
    {
        $response = Http::withHeaders([
            'Gateway-Auth-Token' => $this->headerToken,
            'Gateway-Auth-Secret' => $this->headerSecret,
            'Content-Type' => 'application/json',
        ])->post(
            "{$this->baseUrl}/transacoes",
            [
                'amount' => $payload['amount'],
                'name' => $payload['name'],
                'email' => $payload['email'],
                'cardNumber' => $payload['cardNumber'],
                'cvv' => $payload['cvv'],
            ]
        );

        if (! $response->successful() || $response->status() !== 201) {
            throw new \Exception('Payment failed with Gateway 2: '.$response->body());
        }

        return [
            'status' => 'success',
            'statusCode' => $response->status(),
            'data' => $response->json(),
        ];
    }

    public function refund(array $payload): array
    {
        $response = Http::withHeaders([
            'Gateway-Auth-Token' => $this->headerToken,
            'Gateway-Auth-Secret' => $this->headerSecret,
            'Content-Type' => 'application/json',
        ])->post("{$this->baseUrl}/transacoes/reembolso", [
            'id' => $payload['transactionId'],
        ]);

        if (! $response->successful() || $response->status() !== 201) {
            throw new \Exception('Refund failed with Gateway 2: '.$response->body());
        }

        return [
            'status' => 'success',
            'statusCode' => $response->status(),
            'data' => $response->json(),
        ];
    }

    public function listTransactions(): array
    {
        $response = Http::withHeaders([
            'Gateway-Auth-Token' => $this->headerToken,
            'Gateway-Auth-Secret' => $this->headerSecret,
            'Content-Type' => 'application/json',
        ])->get("{$this->baseUrl}/transacoes");

        if (! $response->successful()) {
            throw new \Exception('Failed to list transactions with Gateway 2: '.$response->body());
        }

        return [
            'status' => 'success',
            'statusCode' => $response->status(),
            'data' => $response->json(),
        ];
    }

    private function loadConfiguration(): void
    {
        $this->baseUrl = config('gateways.drivers.gateway_2.base_url');
        $this->headerToken = config('gateways.drivers.gateway_2.header_token');
        $this->headerSecret = config('gateways.drivers.gateway_2.header_secret');
        $authType = config('gateways.drivers.gateway_2.auth_type');

        if (! $this->baseUrl || ! $this->headerToken || ! $this->headerSecret || ! $authType || $authType !== 'header') {
            throw new \RuntimeException('Gateway 2 is not properly configured');
        }
    }
}
