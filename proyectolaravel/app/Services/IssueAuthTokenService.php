<?php

namespace App\Services;

use App\Http\Resources\UserResource;
use App\Models\User;

class IssueAuthTokenService
{
    public function execute(User $user, int $status = 200)
    {
        $user->loadMissing(['roles.permissions', 'permissions']);

        $token = $user->createToken(
            'p2-api-token',
            [
                'tickets.read',
                'tickets.create',
                'tickets.update',
                'tickets.delete',
                'tickets.assign',
                'tickets.close',
                'comments.create',
            ]
        )->plainTextToken;

        return response()->json([
            'user' => new UserResource($user),
            'token' => $token,
        ], $status);
    }
}
