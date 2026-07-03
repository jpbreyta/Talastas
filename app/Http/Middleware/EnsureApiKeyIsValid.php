<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiKeyIsValid
{
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('x-api-key');

        if (!$apiKey) {
            return response()->json(['message' => 'API key required'], 401);
        }

        $key = ApiKey::where('key', $apiKey)->first();

        if (!$key) {
            return response()->json(['message' => 'Invalid API key'], 401);
        }

        if ($key->isExpired()) {
            return response()->json(['message' => 'API key expired'], 401);
        }

        $key->markAsUsed();

        return $next($request);
    }
}
