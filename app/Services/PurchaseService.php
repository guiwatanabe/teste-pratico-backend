<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Gateway;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionProduct;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PurchaseService
{
    public function __construct(private PaymentService $paymentService) {}

    /**
     * Build a Collection of TransactionProduct instances from
     * the requested items, name and price at purchase time.
     *
     * @param  Collection<int, Product>  $products
     * @param  array<array{id: int, quantity: int}>  $requestedItems
     * @return Collection<int, TransactionProduct>
     */
    public function calculateTotal(Collection $products, array $requestedItems): Collection
    {
        $collectItems = new Collection;

        foreach ($requestedItems as $item) {
            $product = $products->firstWhere('id', $item['id']);

            if (! $product) {
                throw new \InvalidArgumentException("Product ID {$item['id']} not found.");
            }

            $unitPrice = $product->price_cents;
            $totalPrice = $unitPrice * $item['quantity'];

            $collectItems->push(new TransactionProduct([
                'product_id' => $product->id,
                'product_name' => $product->name,
                'quantity' => $item['quantity'],
                'unit_price' => $unitPrice,
                'total_price' => $totalPrice,
            ]));
        }

        return $collectItems;
    }

    public function process(array $data): Transaction
    {
        $productIds = array_column($data['products'], 'id');
        $products = Product::whereIn('id', $productIds)->get();

        $lineItems = $this->calculateTotal($products, $data['products']);
        $totalAmount = $lineItems->sum('total_price');

        $gateways = Gateway::all();
        $result = $this->paymentService->attempt([
            'amount' => $totalAmount,
            'name' => $data['buyer']['name'],
            'email' => $data['buyer']['email'],
            'cardNumber' => $data['card']['number'],
            'cvv' => $data['card']['cvv'],
        ], $gateways);

        return DB::transaction(function () use ($result, $totalAmount, $lineItems, $data) {
            $client = Client::updateOrCreate(
                ['email' => $data['buyer']['email']],
                ['name' => $data['buyer']['name']]
            );

            $transaction = Transaction::create([
                'client_id' => $client->id,
                'gateway_id' => $result['gateway']->id,
                'external_id' => $result['result']['data']['id'],
                'status' => 'completed',
                'amount' => $totalAmount,
                'card_last_numbers' => substr($data['card']['number'], -4),
            ]);

            $transaction->products()->createMany($lineItems->toArray());

            foreach ($data['products'] as $item) {
                Product::where('id', $item['id'])->lockForUpdate()->first();
                Product::where('id', $item['id'])->decrement('amount', $item['quantity']);
            }

            return $transaction;
        });
    }
}
