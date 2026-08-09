<?php

namespace Modules\NabdBridge\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\NabdBridge\Models\NabdApiToken;
use Symfony\Component\HttpFoundation\Response;

class NabdTokenMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $plain = $this->resolveToken($request);

        if (empty($plain)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Missing API token. Provide it via Authorization: Bearer <token> or X-Nabd-Token header.',
            ], 401);
        }

        $hashed = hash('sha256', $plain);

        $token = NabdApiToken::where('token', $hashed)->first();

        if (! $token) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid API token.',
            ], 401);
        }

        if ($token->isExpired()) {
            return response()->json([
                'status' => 'error',
                'message' => 'API token has expired.',
            ], 401);
        }

        $token->update(['last_used_at' => now()]);

        $request->attributes->set('nabd_token', $token);

        return $next($request);
    }

    private function resolveToken(Request $request): ?string
    {
        // Support X-Nabd-Token header
        if ($request->hasHeader('X-Nabd-Token')) {
            return $request->header('X-Nabd-Token');
        }

        // Support Authorization: Bearer <token>
        $authorization = $request->header('Authorization', '');

        if (str_starts_with($authorization, 'Bearer ')) {
            return substr($authorization, 7);
        }

        // Support ?token= query param as fallback
        return $request->query('token');
    }
}
