<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Symfony\Component\HttpFoundation\Response;

class DecryptToken
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $authHeader = $request->header('Authorization');

        if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
            $encryptedToken = substr($authHeader, 7);

            try {
                // Decrypt the token
                $decryptedToken = Crypt::decryptString($encryptedToken);

                // Replace the Authorization header with decrypted token
                $request->headers->set('Authorization', 'Bearer '.$decryptedToken);

            } catch (\Exception $e) {
                // If decryption fails, return unauthorized
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or corrupted token',
                    'data' => null,
                ], 401);
            }
        }

        return $next($request);
    }
}
