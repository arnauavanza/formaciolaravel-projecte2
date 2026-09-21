<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $validated = $request->validated();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $user->assignRole('customer');

        return $this->tokenResponse($user, 201);
    }

    public function login(LoginRequest $request)
    {
        $validated = $request->validated();

        $user = User::query()
            ->where('email', $validated['email'])
            ->first();

        if (
            $user === null
            || ! Hash::check($validated['password'], $user->password)
        ) {
            return response()->json([
                'message' => 'Invalid credentials.',
            ], 401);
        }

        return $this->tokenResponse($user);
    }

    public function me(Request $request)
    {
        return new UserResource(
            $request->user()->loadMissing(['roles.permissions', 'permissions'])
        );
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->noContent();
    }

    private function tokenResponse(User $user, int $status = 200)
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
