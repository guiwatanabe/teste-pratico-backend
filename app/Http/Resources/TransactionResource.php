<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'external_id' => $this->external_id,
            'status' => $this->status,
            'amount' => $this->amount,
            'card_last_numbers' => $this->card_last_numbers,
            'gateway' => $this->whenLoaded('gateway', fn () => $this->gateway->name),
            'products' => TransactionProductResource::collection($this->whenLoaded('products')),
            'buyer' => $this->whenLoaded('client', fn () => [
                'name' => $this->client->name,
                'email' => $this->client->email,
            ]),
        ];
    }
}
