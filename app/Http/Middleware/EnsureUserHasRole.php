<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     *
     * Accepts one or more comma-separated role names. The user must have
     * at least one of the listed roles.
     *
     * Usage in routes:  ->middleware('role:admin')
     *                   ->middleware('role:admin,coordinator')
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status'  => 'error',
                'message' => 'No autenticado.',
            ], 401);
        }

        if (!$user->is_active) {
            return response()->json([
                'status'  => 'error',
                'message' => 'La cuenta de usuario está inactiva.',
            ], 403);
        }

        // Load the role relation if not already loaded
        $userRole = $user->role?->name;

        if (!$userRole || !in_array($userRole, $roles)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'No tiene permisos para acceder a este recurso.',
            ], 403);
        }

        return $next($request);
    }
}
