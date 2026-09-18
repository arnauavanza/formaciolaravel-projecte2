<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogAuthenticatedRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() !== null) {
            Log::info('Authenticated request', [
                'user_id' => $request->user()->id,
                'method' => $request->method(),
                'path' => $request->path(),
            ]);
        }

        return $next($request);
    }
}
