<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuditMiddleware
{
    /**
     * Handle an incoming request.
     *
     * Records every mutating (POST/PUT/PATCH/DELETE) API request from authenticated
     * users into the audit_logs table. GET requests and auth routes are skipped.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only log mutating methods
        if (!in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            return $response;
        }

        // Skip unauthenticated requests and auth routes (login/logout)
        if (!auth()->check()) {
            return $response;
        }

        $routeName = $request->route()?->getName() ?? '';

        // Skip login/logout routes
        if (str_contains($routeName, 'login') || str_contains($routeName, 'logout')) {
            return $response;
        }

        // Derive entity type from the first path segment after /api/
        $pathSegments = array_filter(
            explode('/', trim($request->path(), '/')),
            fn($s) => $s !== 'api'
        );
        $entityType = ucfirst(strtolower(array_values($pathSegments)[0] ?? 'unknown'));
        $entityType = str_replace('-', '', ucwords($entityType, '-'));

        AuditLog::create([
            'user_id'     => auth()->id(),
            'action'      => $request->method() . ':' . ($routeName ?: $request->path()),
            'entity_type' => $entityType,
            'entity_id'   => null, // Populated per-action in controllers when more precise
            'old_values'  => null,
            'new_values'  => null,
            'ip_address'  => $request->ip(),
            'user_agent'  => $request->userAgent(),
        ]);

        return $response;
    }
}
