<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecretAdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Ambil header 'x-admin-secret' yang dikirim oleh Vue Axios
        $secretKey = $request->header('x-admin-secret');

        // Pastikan nilainya cocok dengan SECRET_KEY kamu
        if ($secretKey !== 'admin123') {
            return response()->json([
                'message' => 'Akses ditolak! Secret Key tidak valid.'
            ], 403);
        }

        return $next($request);
    }
}