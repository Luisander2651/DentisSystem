<?php

use App\Core\Authorization\Exceptions\AuthorizationException;
use App\Core\Http\UnexpectedErrorResponse;
use App\Core\Middlewares\EnsureActiveStaff;
use App\Core\Middlewares\InjectSanctumTokenFromCookie;
use App\Core\Middlewares\OnlyAdmin;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

$eventPaths = array_merge(
    glob(realpath(__DIR__.'/../app/Modules/*/Infrastructure/Listeners')) ?: [],
    glob(realpath(__DIR__.'/../app/Modules/ContentManagement/Modules/*/Infrastructure/Listeners')) ?: []
);

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        api: __DIR__.'/../routes/api.php',
        health: '/up',
    )
    ->withEvents(
        discover: $eventPaths
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();
        $middleware->encryptCookies(except: ['auth_token']);
        $middleware->web(prepend: [InjectSanctumTokenFromCookie::class]);
        $middleware->api(prepend: [InjectSanctumTokenFromCookie::class]);

        $middleware->alias([
            'sanctum.cookie' => InjectSanctumTokenFromCookie::class,
            'only.admin' => OnlyAdmin::class,
            'staff' => EnsureActiveStaff::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Spec 014: safety net for the API, for whatever no controller caught.
        $isUnexpected = fn (Throwable $e): bool => ! $e instanceof HttpExceptionInterface
            && ! $e instanceof HttpResponseException
            && ! $e instanceof ValidationException
            && ! $e instanceof AuthenticationException
            && ! $e instanceof AuthorizationException;

        $exceptions->shouldRenderJsonWhen(fn (Request $request): bool => $request->is('api/*') || $request->expectsJson());

        // The default report writes the exception message and the full trace, which may carry
        // patient data; for the API, UnexpectedErrorResponse is the only log of the error.
        $exceptions->report(function (Throwable $e) use ($isUnexpected) {
            if ($isUnexpected($e) && app()->bound('request') && request()->is('api/*')) {
                return false;
            }
        });

        $exceptions->render(function (AuthorizationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['error' => $e->getMessage()], 403);
            }
        });

        $exceptions->render(function (Throwable $e, Request $request) use ($isUnexpected) {
            if ($request->is('api/*') && $isUnexpected($e)) {
                return UnexpectedErrorResponse::from($e, $request->route()?->getActionName() ?? 'unknown');
            }
        });
    })->create();
