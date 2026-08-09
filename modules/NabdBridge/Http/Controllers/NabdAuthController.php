<?php

namespace Modules\NabdBridge\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\NabdBridge\Models\NabdApiToken;

/**
 * Manages Nabd API tokens. Protected by auth:sanctum so only
 * authenticated NexoPOS admins can create or revoke tokens.
 */
class NabdAuthController extends Controller
{
    /**
     * List all tokens (token hash is never exposed).
     */
    public function index(): JsonResponse
    {
        $tokens = NabdApiToken::orderByDesc('created_at')
            ->get(['id', 'name', 'last_used_at', 'expires_at', 'created_at', 'updated_at']);

        return response()->json([
            'status' => 'success',
            'data' => $tokens,
        ]);
    }

    /**
     * Create a new token and return the plain-text value ONCE.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ]);

        ['plain' => $plain, 'hashed' => $hashed] = NabdApiToken::generateToken();

        $token = NabdApiToken::create([
            'name' => $request->string('name'),
            'token' => $hashed,
            'plain_token' => null,  // not stored
            'expires_at' => $request->input('expires_at'),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Token created. Copy the plain_token now — it will not be shown again.',
            'data' => [
                'id' => $token->id,
                'name' => $token->name,
                'plain_token' => $plain,
                'expires_at' => $token->expires_at,
                'created_at' => $token->created_at,
            ],
        ], 201);
    }

    /**
     * Revoke (delete) a token.
     */
    public function destroy(int $id): JsonResponse
    {
        $token = NabdApiToken::findOrFail($id);
        $token->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Token revoked successfully.',
        ]);
    }
}
