<?php

namespace App\Services;

use App\Models\User;

class RegisterUserService
{
    public function execute(array $attributes): User
    {
        $user = User::create([
            'name' => $attributes['name'],
            'email' => $attributes['email'],
            'password' => $attributes['password'],
        ]);

        $user->assignRole('customer');

        return $user;
    }
}
