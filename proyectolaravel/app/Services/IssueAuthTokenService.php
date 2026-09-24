<?php

namespace App\Services;

use App\Models\User;

class IssueAuthTokenService
{
    public function execute(User $user): array
    {
        $user->loadMissing([
            'roles.permissions',
            'permissions',
        ]);

        $abilities = $user->getAllPermissions()
            ->pluck('name')
            ->values()
            ->all();

        $token = $user->createToken(
            'p2-api-token',
            $abilities
        )->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }
}
