<?php

use App\Exceptions\BusinessRuleException;
use App\Exceptions\ConflictException;
use App\Http\Middleware\EnsureRole;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => EnsureRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $isApiRequest = fn (Request $request): bool => $request->is('api/*') || $request->expectsJson();

        $errorJson = fn (string $message, int $status, array $errors = []) => response()->json([
            'success' => false,
            'message' => $message,
            'errors' => (object) $errors,
        ], $status);

        $exceptions->render(function (AuthenticationException $e, Request $request) use ($isApiRequest, $errorJson) {
            if (! $isApiRequest($request)) {
                return null;
            }

            $message = $e->getMessage();
            if ($message === '' || $message === 'Unauthenticated.') {
                $message = 'Autentikasi diperlukan. Silakan login terlebih dahulu.';
            }

            return $errorJson($message, 401);
        });

        $exceptions->render(function (AccessDeniedHttpException $e, Request $request) use ($isApiRequest, $errorJson) {
            if (! $isApiRequest($request)) {
                return null;
            }

            return $errorJson(
                $e->getMessage() ?: 'Anda tidak memiliki hak akses untuk fitur ini.',
                403
            );
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) use ($isApiRequest, $errorJson) {
            if (! $isApiRequest($request)) {
                return null;
            }

            $message = $e->getPrevious() instanceof ModelNotFoundException
                ? 'Data tidak ditemukan.'
                : 'Endpoint tidak ditemukan.';

            return $errorJson($message, 404);
        });

        $exceptions->render(function (ConflictException $e, Request $request) use ($isApiRequest, $errorJson) {
            if (! $isApiRequest($request)) {
                return null;
            }

            return $errorJson($e->getMessage(), 409, $e->errors());
        });

        $exceptions->render(function (BusinessRuleException $e, Request $request) use ($isApiRequest, $errorJson) {
            if (! $isApiRequest($request)) {
                return null;
            }

            return $errorJson($e->getMessage(), 422, $e->errors());
        });

        $exceptions->render(function (ValidationException $e, Request $request) use ($isApiRequest, $errorJson) {
            if (! $isApiRequest($request)) {
                return null;
            }

            return $errorJson('Data yang dikirim tidak valid.', 422, $e->errors());
        });

        $exceptions->render(function (ThrottleRequestsException $e, Request $request) use ($isApiRequest, $errorJson) {
            if (! $isApiRequest($request)) {
                return null;
            }

            return $errorJson('Terlalu banyak permintaan. Silakan coba lagi beberapa saat lagi.', 429);
        });

        $exceptions->render(function (HttpExceptionInterface $e, Request $request) use ($isApiRequest, $errorJson) {
            if (! $isApiRequest($request)) {
                return null;
            }

            return $errorJson(
                $e->getMessage() ?: 'Permintaan tidak dapat diproses.',
                $e->getStatusCode()
            );
        });

        $exceptions->render(function (Throwable $e, Request $request) use ($isApiRequest, $errorJson) {
            if (! $isApiRequest($request)) {
                return null;
            }

            // Jangan bocorkan detail error internal saat APP_DEBUG=false (produksi).
            if (! config('app.debug')) {
                return $errorJson('Terjadi kesalahan pada server.', 500);
            }

            return null;
        });
    })->create();
