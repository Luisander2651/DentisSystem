<?php

namespace App\Providers;

use App\Core\Authorization\AuthorizationServiceInterface;
use App\Core\Authorization\CurrentActorAuthorizationService;
use Illuminate\Support\ServiceProvider;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;

use App\Modules\Users\Domain\Repositories\UserRepositoryInterface;
use App\Modules\Users\Infrastructure\Persistence\Eloquent\EloquentUserRepository;

use App\Modules\Patients\Domain\Repositories\PatientsRepositoryInterface;
use App\Modules\Patients\Infrastructure\Persistence\Eloquent\EloquentPatientRepository;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            UserRepositoryInterface::class,
            EloquentUserRepository::class
        );

        $this->app->bind(
            AuthorizationServiceInterface::class,
            CurrentActorAuthorizationService::class,
        );

        $this->app->bind(
            PatientsRepositoryInterface::class,
            EloquentPatientRepository::class,
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
    // 1. Obtenemos todas las rutas de migraciones de los módulos
    $paths = array_merge(
        glob(base_path('app/Modules/*/Infrastructure/Persistence/Eloquent/Migrations')) ?: [],
        glob(base_path('app/Modules/ContentManagement/Modules/*/Infrastructure/Persistence/Eloquent/Migrations')) ?: []
    );

    RateLimiter::for('api', function (Request $request) {
    return $request->user()
        ? Limit::perMinute(100)->by($request->user()->id) // 100 por min por usuario
        : Limit::perMinute(10)->by($request->ip()); // 10 por min por IP
    });

    // 2. Le decimos a Laravel que cargue todos los archivos de migración encontrados
    $this->loadMigrationsFrom($paths);
    
    }
}
