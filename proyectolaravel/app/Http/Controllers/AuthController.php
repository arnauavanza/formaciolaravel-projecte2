<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthenticateUserService;
use App\Services\IssueAuthTokenService;
use App\Services\RegisterUserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function register(
        RegisterRequest $request,
        RegisterUserService $service,
        IssueAuthTokenService $tokens
    ) {
        $data = $tokens->execute(
            $service->execute($request->validated()),
        );
        Auth::guard('web')->login($data['user']);

        return response()->json([
            'user' => new UserResource($data['user']),
            'token' => $data['token'],
        ], 201);
    }

    public function login(
        LoginRequest $request,
        AuthenticateUserService $service,
        IssueAuthTokenService $tokens
    ) {
        $user = $service->execute($request->validated());

        if ($user === null) {
            return $this->invalidCredentialsResponse();
        }

        $data = $tokens->execute($user);
        Auth::guard('web')->login($user);

        return response()->json([
            'user' => new UserResource($data['user']),
            'token' => $data['token'],
        ]);
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
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->noContent();
    }

    private function invalidCredentialsResponse()
    {
        return response()->json([
            'message' => 'Invalid credentials.',
        ], 401);
    }
}
