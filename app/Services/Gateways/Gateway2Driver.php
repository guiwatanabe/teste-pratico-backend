<?php

namespace App\Services\Gateways;

use Illuminate\Support\Facades\Http;

class Gateway2Driver extends AbstractGatewayDriver
{
    private $baseUrl;

    private $headerToken;

    private $headerSecret;

    public static function driverName(): string
    {
        return 'gateway_2';
    }

    protected function loadConfiguration(): void
    {
        $driverName = static::driverName();
        $this->baseUrl = config("gateways.drivers.{$driverName}.base_url");
        $this->headerToken = config("gateways.drivers.{$driverName}.header_token");
        $this->headerSecret = config("gateways.drivers.{$driverName}.header_secret");
        $authType = config("gateways.drivers.{$driverName}.auth_type");

        if (! $this->baseUrl || ! $this->headerToken || ! $this->headerSecret || ! $authType || $authType !== 'header') {
            throw new \Exception('Gateway 2 is not properly configured');
        }
    }

    public function charge(array $payload): array
    {
        $url = "{$this->baseUrl}/transacoes";
        $headers = $this->getHeaders();
        $payload = [
            'valor' => $payload['amount'],
            'nome' => $payload['name'],
            'email' => $payload['email'],
            'numeroCartao' => $payload['cardNumber'],
            'cvv' => $payload['cvv'],
        ];

        $response = Http::withHeaders($headers)->post($url, $payload);

        $this->log(
            'charge',
            'POST',
            $url,
            $this->sanitizeFields($headers, ['Gateway-Auth-Token', 'Gateway-Auth-Secret']),
            json_encode($this->sanitizeFields($payload, ['numeroCartao', 'cvv'])),
            $response->headers(),
            $response->body(),
            $response->status()
        );

        if ($response->status() !== 201) {
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
        $url = "{$this->baseUrl}/transacoes/reembolso";
        $headers = $this->getHeaders();
        $payload = [
            'id' => $payload['transactionId'],
        ];

        $response = Http::withHeaders($headers)->post($url, $payload);

        $this->log(
            'refund',
            'POST',
            $url,
            $this->sanitizeFields($headers, ['Gateway-Auth-Token', 'Gateway-Auth-Secret']),
            json_encode($payload),
            $response->headers(),
            $response->body(),
            $response->status()
        );

        if ($response->status() !== 201) {
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
        $url = "{$this->baseUrl}/transacoes";
        $headers = $this->getHeaders();

        $response = Http::withHeaders($headers)->get($url);

        $this->log(
            'list_transactions',
            'GET',
            $url,
            $this->sanitizeFields($headers, ['Gateway-Auth-Token', 'Gateway-Auth-Secret']),
            null,
            $response->headers(),
            $response->body(),
            $response->status()
        );

        if (! $response->successful()) {
            throw new \Exception('Failed to list transactions with Gateway 2: '.$response->body());
        }

        return [
            'status' => 'success',
            'statusCode' => $response->status(),
            'data' => $response->json(),
        ];
    }

    private function getHeaders(): array
    {
        return [
            'Gateway-Auth-Token' => $this->headerToken,
            'Gateway-Auth-Secret' => $this->headerSecret,
            'Content-Type' => 'application/json',
        ];
    }
}
