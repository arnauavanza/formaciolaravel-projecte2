<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthenticateUserService;
use App\Services\IssueAuthTokenService;
use App\Services\RegisterUserService;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function register(
        RegisterRequest $request,
        RegisterUserService $service,
        IssueAuthTokenService $tokens
    )
    {
        return $tokens->execute(
            $service->execute($request->validated()),
            201
        );
    }

    public function login(
        LoginRequest $request,
        AuthenticateUserService $service,
        IssueAuthTokenService $tokens
    )
    {
        $user = $service->execute($request->validated());

        if ($user === null) {
            return $this->invalidCredentialsResponse();
        }

        return $tokens->execute($user);
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

    private function invalidCredentialsResponse()
    {
        return response()->json([
            'message' => 'Invalid credentials.',
        ], 401);
    }
}
