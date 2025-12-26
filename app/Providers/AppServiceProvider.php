<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
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
