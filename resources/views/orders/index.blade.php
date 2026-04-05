<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Orders — {{ config('app.name') }}</title>
    </head>
    <body style="font-family: system-ui, sans-serif; margin: 2rem;">
        <h1>Orders</h1>
        <p style="color: #666;">Each row loads line-item count with a separate query (N+1).</p>
        <table border="1" cellpadding="8" cellspacing="0" style="border-collapse: collapse;">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Total</th>
                    <th>Items</th>
                    <th>Created</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($orders as $order)
                    <tr>
                        <td>{{ $order->id }}</td>
                        <td>{{ $order->total_price }}</td>
                        <td>{{ $order->items()->count() }}</td>
                        <td>{{ $order->created_at?->toDateTimeString() }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">No orders yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </body>
</html>
