<?php

namespace App\Listeners;

use App\Events\QuoteCreated;
use Illuminate\Support\Facades\Queue;

class SendQuoteCreatedToAnalytics
{
    public function handle(QuoteCreated $event): void
    {
        $queueName = env('ANALYTICS_QUEUE', 'analytics');

        Queue::connection('redis')->pushRaw(
            json_encode([
                'event' => 'quote.created',
                'payload' => [
                    'quote_id' => $event->quote->id,
                    'user_id' => $event->quote->user_id,
                    'product_id' => $event->quote->product_id,
                    'qty' => $event->quote->qty,
                    'created_at' => $event->quote->created_at?->toIso8601String(),
                ],
            ]),
            $queueName
        );
    }
}
