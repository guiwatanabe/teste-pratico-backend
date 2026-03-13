<?php

namespace App\Services\Gateways;

use App\Models\GatewayLog;

abstract class AbstractGatewayDriver implements GatewayInterface
{
    public function __construct()
    {
        $this->loadConfiguration();
    }

    abstract public static function driverName(): string;

    abstract protected function loadConfiguration(): void;

    protected function log(string $action, string $method, string $url, array $requestHeaders = [], ?string $requestBody = null, array $responseHeaders = [], ?string $responseBody = null, ?int $responseStatusCode = null): void
    {
        GatewayLog::create([
            'gateway_driver' => static::driverName(),
            'action' => $action,
            'request_method' => $method,
            'request_url' => $url,
            'request_headers' => json_encode($requestHeaders),
            'request_body' => $requestBody,
            'response_status_code' => $responseStatusCode,
            'response_headers' => json_encode($responseHeaders),
            'response_body' => $responseBody,
        ]);
    }

    protected function sanitizeFields(array $data, array $fieldsToMask): array
    {
        foreach ($fieldsToMask as $field) {
            if (isset($data[$field])) {
                if (strlen($data[$field]) == 16) {
                    $data[$field] = '************ '.substr($data[$field], -4);
                } else {
                    $data[$field] = str_repeat('*', strlen($data[$field]));
                }
            }
        }

        return $data;
    }
}
