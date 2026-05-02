<?php

namespace App\Actions;

use App\Events\UserRegistered;
use App\Models\User;

class UserCreateAction
{
    public function execute(string $name, string $email): User
    {
        $user = User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => $email,
        ]);

        UserRegistered::dispatch($user);

        return $user;
    }
}
