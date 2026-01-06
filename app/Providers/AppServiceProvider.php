<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use App\Modules\Users\Domain\Repositories\UserRepositoryInterface;
use App\Modules\Users\Infrastructure\Persistence\Eloquent\EloquentUserRepository;

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

    // 2. Le decimos a Laravel que cargue todos los archivos de migración encontrados
    $this->loadMigrationsFrom($paths);
    
    }
}
