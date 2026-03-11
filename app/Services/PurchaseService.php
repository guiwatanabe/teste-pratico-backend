<?php

namespace App\Services;

use App\Models\Product;
use App\Models\TransactionProduct;
use Illuminate\Support\Collection;

class PurchaseService
{
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

            $unitPrice = $product->amount;
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
}
