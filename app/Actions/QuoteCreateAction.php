<?php

namespace App\Actions;

use App\Events\QuoteCreated;
use App\Models\Quote;

class QuoteCreateAction
{
    public const int DEFAULT_PRODUCT_ID = 1;

    public function execute(int $userId, int $qty): Quote
    {
        $quote = Quote::query()->create([
            'user_id' => $userId,
            'product_id' => self::DEFAULT_PRODUCT_ID,
            'qty' => $qty,
        ]);

        QuoteCreated::dispatch($quote);

        return $quote;
    }
}
