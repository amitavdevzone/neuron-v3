<?php

use App\Actions\QuoteCreateAction;
use App\Events\QuoteCreated;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Event;

test('a tutorial quote can be requested for a user', function () {
    $product = Product::factory()->create(['id' => QuoteCreateAction::DEFAULT_PRODUCT_ID]);
    $otherProduct = Product::factory()->create();
    $user = User::factory()->create();

    Event::fake();

    $response = $this->post(route('tutorial.quotes.store'), [
        'user_id' => $user->id,
        'product_id' => $otherProduct->id,
        'qty' => 4,
    ]);

    $response->assertRedirect(route('tutorial.quotes.index'));

    $this->assertDatabaseHas('quotes', [
        'user_id' => $user->id,
        'product_id' => $product->id,
        'qty' => 4,
    ]);

    Event::assertDispatched(QuoteCreated::class, function (QuoteCreated $event) use ($user, $product): bool {
        return $event->quote->user_id === $user->id
            && $event->quote->product_id === $product->id
            && $event->quote->qty === 4;
    });
});

test('tutorial quote request validates required fields', function (array $payload, string $field) {
    $this->from(route('tutorial.quotes.index'))
        ->post(route('tutorial.quotes.store'), $payload)
        ->assertRedirect(route('tutorial.quotes.index'))
        ->assertSessionHasErrors($field);
})->with([
    'missing user' => [['qty' => 1], 'user_id'],
    'unknown user' => [['user_id' => 999, 'qty' => 1], 'user_id'],
    'missing qty' => [['user_id' => 1], 'qty'],
    'zero qty' => [['user_id' => 1, 'qty' => 0], 'qty'],
]);
