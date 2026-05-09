<?php

use App\Core\Middlewares\InjectSanctumTokenFromCookie;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Core\Middlewares\OnlyAdmin;

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
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
