<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-API-KEY');

        $expectedKey = config('services.sigecap.api_key');

        if (
            !is_string($apiKey) ||
            !is_string($expectedKey) ||
            $expectedKey === '' ||
            !hash_equals($expectedKey, $apiKey)
        ) {
            return response()->json([
                'success' => false,
                'message' => 'API Key inválida o no proporcionada.'
            ], 401);
        }

        return $next($request);
    }
}