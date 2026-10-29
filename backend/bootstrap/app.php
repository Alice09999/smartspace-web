<?php

use App\Http\Middleware\Peran;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Alias middleware kustom
        $middleware->alias([
            'peran' => Peran::class,
        ]);

        // Rate limiter yang dipakai: 'api' dan 'auth' (lihat AppServiceProvider)
    })
    ->withExceptions(function (Exceptions $exceptions) {

        // ── Semua respons error API dalam format {"galat": "..."} ──

        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                $pesan = collect($e->errors())->flatten()->first()
                    ?? 'Data yang dikirim tidak valid.';

                return response()->json(['galat' => $pesan], 400);
            }
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                return response()->json(['galat' => 'Anda belum masuk atau token tidak valid.'], 401);
            }
        });

        $exceptions->render(function (AccessDeniedHttpException $e, Request $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                return response()->json(['galat' => $e->getMessage() ?: 'Anda tidak memiliki akses untuk tindakan ini.'], 403);
            }
        });

        $exceptions->render(function (ModelNotFoundException $e, Request $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                return response()->json(['galat' => 'Data tidak ditemukan.'], 404);
            }
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                return response()->json(['galat' => 'Endpoint tidak ditemukan.'], 404);
            }
        });

        $exceptions->render(function (TooManyRequestsHttpException $e, Request $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                return response()->json(['galat' => 'Terlalu banyak permintaan. Coba lagi nanti.'], 429);
            }
        });

        // Catch-all untuk exception lain di route API
        $exceptions->render(function (\Throwable $e, Request $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                // Jangan bocorkan detail internal di production
                $pesan = config('app.debug')
                    ? $e->getMessage()
                    : 'Terjadi kesalahan pada server.';

                $code = method_exists($e, 'getStatusCode')
                    ? $e->getStatusCode()
                    : 500;

                if ($code < 400 || $code > 599) {
                    $code = 500;
                }

                return response()->json(['galat' => $pesan], $code);
            }
        });
    })->create();
