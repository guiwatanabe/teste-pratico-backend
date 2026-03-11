<?php

use App\Models\Product;
use App\Services\PurchaseService;

test('calculates total as unit_price multiplied by quantity for a single product', function () {
    $service = new PurchaseService;

    $product = new Product(['name' => 'Test Product', 'amount' => 1000]);
    $product->id = 1;

    $products = collect([$product]);

    $requestedItems = [
        ['id' => 1, 'quantity' => 2], // 2000
    ];

    $lineItems = $service->calculateTotal($products, $requestedItems);

    expect($lineItems)->toHaveCount(1);
    expect($lineItems->first()->total_price)->toBe(2000);
});

test('sums correctly across multiple products with different quantities', function () {
    $service = new PurchaseService;

    $product1 = new Product(['name' => 'Product 1', 'amount' => 500]);
    $product1->id = 1;

    $product2 = new Product(['name' => 'Product 2', 'amount' => 2000]);
    $product2->id = 2;

    $products = collect([$product1, $product2]);

    $requestedItems = [
        ['id' => 1, 'quantity' => 3], // 1500
        ['id' => 2, 'quantity' => 1], // 2000
    ];

    $lineItems = $service->calculateTotal($products, $requestedItems);

    expect($lineItems)->toHaveCount(2);
    expect($lineItems->sum('total_price'))->toBe(3500);
});

test('returns the amount in cents', function () {
    $service = new PurchaseService;

    $product = new Product(['name' => 'Test Product', 'amount' => 1234]);
    $product->id = 1;

    $products = collect([$product]);

    $requestedItems = [
        ['id' => 1, 'quantity' => 1], // 1234
    ];

    $lineItems = $service->calculateTotal($products, $requestedItems);

    expect($lineItems->first()->total_price)->toBe(1234);
});

test('handles large quantities without floating point errors', function () {
    $service = new PurchaseService;

    $product = new Product(['name' => 'Bulk Product', 'amount' => 999]);
    $product->id = 1;

    $products = collect([$product]);

    $requestedItems = [
        ['id' => 1, 'quantity' => 1000], // 999 * 1000 = 999000
    ];

    $lineItems = $service->calculateTotal($products, $requestedItems);

    expect($lineItems->first()->total_price)->toBe(999000);
});

test('throws if a requested product id does not exist', function () {
    $service = new PurchaseService;

    $product = new Product(['name' => 'Existing Product', 'amount' => 500]);
    $product->id = 1;

    $products = collect([$product]);

    $requestedItems = [
        ['id' => 2, 'quantity' => 1],
    ];

    $service->calculateTotal($products, $requestedItems);
})->throws(\InvalidArgumentException::class, 'Product ID 2 not found.');
