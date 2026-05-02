<?php

namespace App\Listeners;

use App\Events\UserRegistered;
use Illuminate\Support\Facades\Queue;

class SendUserRegisteredToAnalytics
{
    public function handle(UserRegistered $event): void
    {
        $queueName = env('ANALYTICS_QUEUE', 'analytics');

        Queue::connection('redis')->pushRaw(
            json_encode([
                'event' => 'user.registered',
                'payload' => [
                    'user_id' => $event->user->id,
                    'email' => $event->user->email,
                    'name' => $event->user->name,
                    'registered_at' => now()->toIso8601String(),
                ],
            ]),
            $queueName
        );
    }
}
