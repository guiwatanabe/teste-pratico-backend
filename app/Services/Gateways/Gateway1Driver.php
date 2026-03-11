<?php

namespace App\Services\Gateways;

use Illuminate\Support\Facades\Http;

class Gateway1Driver implements GatewayInterface
{
    private $baseUrl;

    private $authEmail;

    private $authToken;

    private $token;

    public function __construct()
    {
        $this->loadConfiguration();
    }

    public function charge(array $payload): array
    {
        $this->token = $this->getAuthToken();

        $response = Http::withToken($this->token)->post(
            "{$this->baseUrl}/transactions",
            [
                'amount' => $payload['amount'],
                'name' => $payload['name'],
                'email' => $payload['email'],
                'cardNumber' => $payload['cardNumber'],
                'cvv' => $payload['cvv'],
            ]
        );

        if (! $response->successful() || $response->status() !== 201) {
            throw new \Exception('Payment failed with Gateway 1: '.$response->body());
        }

        return [
            'status' => 'success',
            'statusCode' => $response->status(),
            'data' => $response->json(),
        ];
    }

    public function refund(array $payload): array
    {
        $this->token = $this->getAuthToken();

        $response = Http::withToken($this->token)->post("{$this->baseUrl}/transactions/{$payload['transactionId']}/refund");

        if (! $response->successful() || $response->status() !== 201) {
            throw new \Exception('Refund failed with Gateway 1: '.$response->body());
        }

        return [
            'status' => 'success',
            'statusCode' => $response->status(),
            'data' => $response->json(),
        ];
    }

    public function listTransactions(): array
    {
        $this->token = $this->getAuthToken();

        $response = Http::withToken($this->token)->get("{$this->baseUrl}/transactions");

        if (! $response->successful()) {
            throw new \Exception('Failed to retrieve transactions from Gateway 1: '.$response->body());
        }

        return [
            'status' => 'success',
            'statusCode' => $response->status(),
            'data' => $response->json(),
        ];
    }

    private function loadConfiguration(): void
    {
        $this->baseUrl = config('gateways.drivers.gateway_1.base_url');
        $this->authEmail = config('gateways.drivers.gateway_1.auth_email');
        $this->authToken = config('gateways.drivers.gateway_1.auth_token');
        $authType = config('gateways.drivers.gateway_1.auth_type');

        if (! $this->baseUrl || ! $this->authEmail || ! $this->authToken || ! $authType || $authType !== 'auth_token') {
            throw new \Exception('Gateway 1 is not properly configured');
        }
    }

    private function getAuthToken(): string
    {
        $response = Http::post("{$this->baseUrl}/login", [
            'email' => $this->authEmail,
            'token' => $this->authToken,
        ]);

        if ($response->successful()) {
            return $response->json('token');
        }

        throw new \Exception('Failed to retrieve authentication token from Gateway 1');
    }
}
