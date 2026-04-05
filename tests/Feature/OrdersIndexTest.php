<?php

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

test('orders index returns 200 and lists orders with item counts', function () {
    $product = Product::factory()->create();
    $order = Order::factory()->create(['total_price' => 10.00]);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
    ]);

    $response = $this->get(route('orders.index'));

    $response->assertOk();
    $response->assertSee((string) $order->id, escape: false);
    $response->assertSee('Items', escape: false);
});

test('orders index issues one count query per order for line items', function () {
    $product = Product::factory()->create();

    foreach (range(1, 3) as $_) {
        $order = Order::factory()->create();
        OrderItem::factory()->count(2)->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
        ]);
    }

    $queries = [];

    DB::listen(function ($query) use (&$queries): void {
        $queries[] = $query->sql;
    });

    $this->get(route('orders.index'))->assertOk();

    $orderItemCountQueries = collect($queries)->filter(function (string $sql): bool {
        $lower = strtolower($sql);

        return str_contains($lower, 'order_items') && str_contains($lower, 'count');
    })->count();

    expect($orderItemCountQueries)->toBe(3);
});
