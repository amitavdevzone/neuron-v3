<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Seeder;

class OrderCatalogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = Product::factory()
            ->count(20)
            ->create();

        for ($i = 0; $i < 100; $i++) {
            $order = Order::query()->create([
                'total_price' => 0,
            ]);

            $itemCount = random_int(3, 10);
            $total = '0.00';

            for ($j = 0; $j < $itemCount; $j++) {
                /** @var Product $product */
                $product = $products->random();
                $quantity = random_int(1, 5);
                $unitPrice = $product->price;
                $lineTotal = bcmul((string) $unitPrice, (string) $quantity, 2);
                $total = bcadd($total, $lineTotal, 2);

                OrderItem::query()->create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'price' => $unitPrice,
                ]);
            }

            $order->update([
                'total_price' => $total,
            ]);
        }
    }
}
