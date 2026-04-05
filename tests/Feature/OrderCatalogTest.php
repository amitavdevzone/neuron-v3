<?php

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;

test('order total_price equals sum of line quantities times unit price', function () {
    $productA = Product::factory()->create(['price' => 10.00]);
    $productB = Product::factory()->create(['price' => 25.50]);

    $order = Order::query()->create(['total_price' => 0]);

    OrderItem::query()->create([
        'order_id' => $order->id,
        'product_id' => $productA->id,
        'quantity' => 2,
        'price' => $productA->price,
    ]);

    OrderItem::query()->create([
        'order_id' => $order->id,
        'product_id' => $productB->id,
        'quantity' => 1,
        'price' => $productB->price,
    ]);

    $expected = bcadd(
        bcmul((string) $productA->price, '2', 2),
        bcmul((string) $productB->price, '1', 2),
        2,
    );

    $order->update(['total_price' => $expected]);

    $order->refresh()->load('items.product');

    expect($order->items)->toHaveCount(2);
    expect((string) $order->total_price)->toBe($expected);
});

test('order has many items and items belong to order and product', function () {
    $product = Product::factory()->create();
    $order = Order::factory()->create();

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'price' => $product->price,
    ]);

    expect($order->items()->count())->toBe(1);
    expect($order->items->first()?->product?->is($product))->toBeTrue();
});
