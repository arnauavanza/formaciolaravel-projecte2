<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthenticateUserService
{
    public function execute(array $credentials): ?User
    {
        $user = User::query()
            ->where('email', $credentials['email'])
            ->first();

        if (
            $user === null
            || ! Hash::check($credentials['password'], $user->password)
        ) {
            return null;
        }

        return $user;
    }
}
