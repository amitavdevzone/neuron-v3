<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\View\View;

class OrderController extends Controller
{
    /**
     * Lists orders. Item counts are not eager-loaded; the Blade view calls
     * {@see Order::items()} per row, which triggers one COUNT query per order (classic N+1).
     */
    public function index(): View
    {
        $orders = Order::query()
            ->orderByDesc('id')
            ->get(['id', 'total_price', 'created_at']);

        return view('orders.index', [
            'orders' => $orders,
        ]);
    }
}
