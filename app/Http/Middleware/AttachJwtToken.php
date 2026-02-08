<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AttachJwtToken
{
    public function handle(Request $request, Closure $next): Response
    {
        // Jika request punya cookie bernama 'token' TAPI tidak punya Header Authorization
        if ($request->hasCookie('token') && !$request->headers->has('Authorization')) {
            // Ambil token dari cookie
            $token = $request->cookie('token');

            // Suntikkan ke Header Authorization seolah-olah dikirim manual oleh user
            $request->headers->set('Authorization', 'Bearer ' . $token);
        }

        return $next($request);
    }
}
