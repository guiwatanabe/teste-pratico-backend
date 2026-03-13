<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GatewayLog extends Model
{
    const UPDATED_AT = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'gateway_driver',
        'action',
        'request_method',
        'request_url',
        'request_headers',
        'request_body',
        'response_status_code',
        'response_headers',
        'response_body',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'request_headers' => 'array',
            'request_body' => 'array',
            'response_headers' => 'array',
            'response_body' => 'array',
        ];
    }
}
