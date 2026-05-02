<?php

namespace App\Http\Controllers;

use App\Actions\UserCreateAction;
use App\Http\Requests\UserRegisterRequest;
use Illuminate\Http\JsonResponse;

class UserRegistrationController extends Controller
{
    public function store(UserRegisterRequest $request, UserCreateAction $action): JsonResponse
    {
        $user = $action->execute(
            $request->validated('name'),
            $request->validated('email'),
        );

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ], 201);
    }
}
