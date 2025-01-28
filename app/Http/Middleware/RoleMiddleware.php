<?php

namespace App\Http\Middleware;

use Symfony\Component\HttpFoundation\Response;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, $role)
    {
        // Verifica si el usuario está autenticado y tiene el rol especificado
        if (!Auth::check() || Auth::user()->role !== $role) {
            return response()->json(['message' => 'No tienes permiso para acceder a esta sección'], 403);
        }

        return $next($request);
    }
}
