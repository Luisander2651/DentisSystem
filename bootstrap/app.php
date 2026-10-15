<?php

use App\Core\Authorization\Exceptions\AuthorizationException;
use App\Core\Http\UnexpectedErrorResponse;
use App\Core\Middlewares\EnsureActiveStaff;
use App\Core\Middlewares\InjectSanctumTokenFromCookie;
use App\Core\Middlewares\OnlyAdmin;
use App\Core\Middlewares\SecurityHeaders;
use App\Core\Middlewares\TrustCloudflareProxies;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Exceptions\RegisterErrorViewPaths;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\ViewErrorBag;
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
        then: function (): void {
            // Spec 016: any other web address answers our "No encontrada" page. The route stays
            // out of the `web` group, so a visitor opens no session; it only reads the token
            // cookie to know where the way back leads. It never takes an `api/*` address.
            Route::fallback(fn () => abort(404))
                ->middleware('sanctum.cookie')
                ->where('fallbackPlaceholder', '^(?!api(/|$)).*');
        },
    )
    ->withEvents(
        discover: $eventPaths
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Spec 015: real client IP behind Cloudflare, and security headers on every response.
        $middleware->replace(TrustProxies::class, TrustCloudflareProxies::class);
        $middleware->append(SecurityHeaders::class);

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
        // patient data. For the API, every unexpected error is logged here in its sanitized form
        // instead - also one reported manually or after the response - and the render below only
        // builds the answer, so each error is logged exactly once.
        $exceptions->report(function (Throwable $e) use ($isUnexpected): ?bool {
            if ($isUnexpected($e) && app()->bound('request') && request()->is('api/*')) {
                UnexpectedErrorResponse::log($e, request()->route()?->getActionName() ?? 'unknown');

                return false;
            }

            return null;
        });

        $exceptions->render(function (AuthorizationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['error' => $e->getMessage()], 403);
            }
        });

        // Spec 016: the web screens answer 403, 404, 405 and 419 with our own pages, or with a
        // fixed Spanish body when JSON is asked for. The pages are rendered here, and not by
        // the default handler, so the response keeps its view.
        $exceptions->render(function (HttpExceptionInterface $e, Request $request): Response|JsonResponse|null {
            $messages = [
                403 => 'No tienes permiso para ver esta pantalla.',
                404 => 'No encontramos esta página.',
                405 => 'Esta acción no está disponible.',
                419 => 'La página caducó. Vuelve a cargarla e inténtalo de nuevo.',
            ];

            if ($request->is('api/*') || ! isset($messages[$e->getStatusCode()])) {
                return null;
            }

            if ($request->expectsJson()) {
                return response()->json(['message' => $messages[$e->getStatusCode()]], $e->getStatusCode(), $e->getHeaders());
            }

            // A 405 is raised before any route middleware runs: the token is read from its cookie
            // here too, so whoever has a session gets the way back to their start.
            (new InjectSanctumTokenFromCookie)->handle($request, fn (): Response => new Response);
            (new RegisterErrorViewPaths)();

            return rescue(fn (): Response => response()->view('errors::'.$e->getStatusCode(), ['errors' => new ViewErrorBag], $e->getStatusCode(), $e->getHeaders()), null, false);
        });

        $exceptions->render(function (Throwable $e, Request $request) use ($isUnexpected): ?JsonResponse {
            if ($request->is('api/*') && $isUnexpected($e)) {
                return UnexpectedErrorResponse::response();
            }

            return null;
        });
    })->create();
