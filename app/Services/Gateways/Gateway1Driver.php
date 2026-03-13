<?php

namespace App\Services\Gateways;

use Illuminate\Support\Facades\Http;

class Gateway1Driver extends AbstractGatewayDriver
{
    private $baseUrl;

    private $authEmail;

    private $authToken;

    public static function driverName(): string
    {
        return 'gateway_1';
    }

    protected function loadConfiguration(): void
    {
        $driverName = static::driverName();
        $this->baseUrl = config("gateways.drivers.{$driverName}.base_url");
        $this->authEmail = config("gateways.drivers.{$driverName}.auth_email");
        $this->authToken = config("gateways.drivers.{$driverName}.auth_token");
        $authType = config("gateways.drivers.{$driverName}.auth_type");

        if (! $this->baseUrl || ! $this->authEmail || ! $this->authToken || ! $authType || $authType !== 'auth_token') {
            throw new \Exception('Gateway 1 is not properly configured');
        }
    }

    public function charge(array $payload): array
    {
        $token = $this->getAuthToken();
        $url = "{$this->baseUrl}/transactions";
        $payload = [
            'amount' => $payload['amount'],
            'name' => $payload['name'],
            'email' => $payload['email'],
            'cardNumber' => $payload['cardNumber'],
            'cvv' => $payload['cvv'],
        ];

        $response = Http::withToken($token)->post($url, $payload);

        $this->log(
            'charge',
            'POST',
            $url,
            ['Authorization' => 'Bearer *****'],
            json_encode($this->sanitizeFields($payload, ['cardNumber', 'cvv'])),
            $response->headers(),
            $response->body(),
            $response->status()
        );

        if ($response->status() !== 201) {
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
        $token = $this->getAuthToken();
        $url = "{$this->baseUrl}/transactions/{$payload['transactionId']}/charge_back";

        $response = Http::withToken($token)->post($url);

        $this->log(
            'refund',
            'POST',
            $url,
            ['Authorization' => 'Bearer *****'],
            null,
            $response->headers(),
            $response->body(),
            $response->status()
        );

        if ($response->status() !== 201) {
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
        $token = $this->getAuthToken();
        $url = "{$this->baseUrl}/transactions";

        $response = Http::withToken($token)->get($url);

        $this->log(
            'list_transactions',
            'GET',
            $url,
            ['Authorization' => 'Bearer *****'],
            null,
            $response->headers(),
            $response->body(),
            $response->status()
        );

        if (! $response->successful()) {
            throw new \Exception('Failed to retrieve transactions from Gateway 1: '.$response->body());
        }

        return [
            'status' => 'success',
            'statusCode' => $response->status(),
            'data' => $response->json(),
        ];
    }

    private function getAuthToken(): string
    {
        $url = "{$this->baseUrl}/login";
        $payload = [
            'email' => $this->authEmail,
            'token' => $this->authToken,
        ];
        $response = Http::post($url, $payload);

        $responseData = json_decode($response->body(), true);
        $responseBody = is_array($responseData)
            ? json_encode($this->sanitizeFields($responseData, ['token']))
            : $response->body();

        $this->log(
            'authenticate',
            'POST',
            $url,
            [],
            json_encode($this->sanitizeFields($payload, ['token'])),
            $response->headers(),
            $responseBody,
            $response->status()
        );

        if ($response->successful()) {
            return $response->json('token');
        }

        throw new \Exception('Failed to retrieve authentication token from Gateway 1');
    }
}
