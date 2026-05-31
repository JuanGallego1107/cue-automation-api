<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // ─── CORS ────────────────────────────────────────────────────────────
        $middleware->api(prepend: [
            \Illuminate\Http\Middleware\HandleCors::class,
        ]);

        // ─── Audit logging for all mutating API calls ─────────────────────────
        $middleware->api(append: [
            \App\Http\Middleware\AuditMiddleware::class,
        ]);

        $middleware->alias([
            'permission' => \App\Http\Middleware\EnsureUserHasPermission::class,
            'role'       => \App\Http\Middleware\EnsureUserHasRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                // Let Laravel validation exception handle its standard 422 JSON formatting,
                // but we can intercept it if we want to customize. Let's keep 422 standard.
                if ($e instanceof \Illuminate\Validation\ValidationException) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Los datos proporcionados no son válidos.',
                        'errors' => $e->errors(),
                    ], 422);
                }

                // Resource not found (e.g. ModelNotFoundException)
                if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'El recurso solicitado no fue encontrado.',
                    ], 404);
                }

                // Authentication Exception
                if ($e instanceof \Illuminate\Auth\AuthenticationException) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'No autenticado.',
                    ], 401);
                }

                // Access Denied / Authorization Exception
                if ($e instanceof \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException ||
                    $e instanceof \Illuminate\Auth\AccessDeniedException) {
                    return response()->json([
                        'status' => 'error',
                        'message' => $e->getMessage() ?: 'No tiene permisos para realizar esta acción.',
                    ], 403);
                }

                // Route / HTTP Method Not Found
                if ($e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'La ruta especificada no existe.',
                    ], 404);
                }

                // Generic HTTP Exception
                if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface) {
                    return response()->json([
                        'status' => 'error',
                        'message' => $e->getMessage(),
                    ], $e->getStatusCode());
                }

                // Generic unexpected server/DB error (500)
                return response()->json([
                    'status' => 'error',
                    'message' => config('app.debug') ? $e->getMessage() : 'Ocurrió un error inesperado en el servidor.',
                    'exception' => config('app.debug') ? get_class($e) : null,
                    'trace' => config('app.debug') ? array_slice($e->getTrace(), 0, 5) : null,
                ], 500);
            }
        });
    })->create();
