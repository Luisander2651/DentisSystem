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


use App\Modules\Patients\Domain\Repositories\AddressesRepositoryInterface;
use App\Modules\Patients\Infrastructure\Persistence\Eloquent\EloquentAddressRepository;

use App\Modules\Patients\Domain\Repositories\ContactInfoRepositoryInterface;
use App\Modules\Patients\Infrastructure\Persistence\Eloquent\EloquentContactInfoRepository;


use App\Modules\Patients\Domain\Repositories\MedicalDataRepositoryInterface;
use App\Modules\Patients\Infrastructure\Persistence\Eloquent\EloquentMedicalDataRepository;

use App\Modules\Patients\Domain\Repositories\PatientRecordRepositoryInterface;
use App\Modules\Patients\Infrastructure\Persistence\Eloquent\PatientRecordRepository as EloquentPatientRecordRepository;

use App\Modules\Patients\Domain\Repositories\PatientsRepositoryInterface;
use App\Modules\Patients\Infrastructure\Persistence\Eloquent\EloquentPatientRepository;

use App\Modules\Appointments\Domain\Repositories\AppointmentsRepositoryInterface;
use App\Modules\Appointments\Infrastructure\Persistence\Eloquent\EloquentAppointmentRepository;

use App\Modules\ContentManagement\StorageProviderInterface;
use App\Modules\ContentManagement\StorageProvider;
    
use App\Modules\ContentManagement\Modules\Certificaciones\Domain\Repositories\CertificationRepositoryInterface;
use App\Modules\ContentManagement\Modules\Certificaciones\Infrastructure\Persistence\Eloquent\EloquentCertificationRepository;
use App\Modules\ContentManagement\Modules\Certificaciones\Aplication\UseCases\SaveCertificationUseCase;
use App\Modules\ContentManagement\Modules\Certificaciones\Aplication\UseCases\UpdateCertificationUseCase;
use App\Modules\ContentManagement\Modules\Certificaciones\Aplication\UseCases\DeleteCertificationUseCase;

use App\Modules\ContentManagement\Modules\Galeria\Domain\Repositories\GalleryImageRepositoryInterface;
use App\Modules\ContentManagement\Modules\Galeria\Infrastructure\Persistence\Eloquent\EloquentGalleryImageRepository;
use App\Modules\ContentManagement\Modules\Galeria\Aplication\UseCases\SaveGalleryImageUseCase;
use App\Modules\ContentManagement\Modules\Galeria\Aplication\UseCases\UpdateGalleryImageUseCase;
use App\Modules\ContentManagement\Modules\Galeria\Aplication\UseCases\DeleteGalleryImageUseCase;

use App\Modules\ContentManagement\Modules\Promociones\Domain\Repositories\PromotionRepositoryInterface;
use App\Modules\ContentManagement\Modules\Promociones\Infrastructure\Persistence\Eloquent\EloquentPromotionRepository;
use App\Modules\ContentManagement\Modules\Testimonios\Domain\Repositories\TestimonialRepositoryInterface;
use App\Modules\ContentManagement\Modules\Testimonios\Infrastructure\Persistence\Eloquent\EloquentTestimonialRepository;

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

        $this->app->bind(
            AddressesRepositoryInterface::class,
            EloquentAddressRepository::class,
        );

        $this->app->bind(
            ContactInfoRepositoryInterface::class,
            EloquentContactInfoRepository::class,
        );

        $this->app->bind(
            MedicalDataRepositoryInterface::class,
            EloquentMedicalDataRepository::class,
        );

        $this->app->bind(
            PatientRecordRepositoryInterface::class,
            EloquentPatientRecordRepository::class,
        );

        $this->app->bind(
            AppointmentsRepositoryInterface::class,
            EloquentAppointmentRepository::class,
        );

        $this->app->bind(
            CertificationRepositoryInterface::class,
            EloquentCertificationRepository::class,
        );

        $this->app->bind(
            GalleryImageRepositoryInterface::class,
            EloquentGalleryImageRepository::class,
        );

        $this->app->bind(
            PromotionRepositoryInterface::class,
            EloquentPromotionRepository::class,
        );

        $this->app->bind(
            TestimonialRepositoryInterface::class,
            EloquentTestimonialRepository::class,
        );

        // Contextual binding for the certifications module: when the SaveCertificationUseCase
        // requires StorageProviderInterface, provide a StorageProvider configured for this module.
        $this->app->when(SaveCertificationUseCase::class)
            ->needs(StorageProviderInterface::class)
            ->give(function ($app) {
                return StorageProvider::new('certificaciones', 'uploads/content');
            });

        $this->app->when(UpdateCertificationUseCase::class)
            ->needs(StorageProviderInterface::class)
            ->give(function ($app) {
                return StorageProvider::new('certificaciones', 'uploads/content');
            });

        $this->app->when(DeleteCertificationUseCase::class)
            ->needs(StorageProviderInterface::class)
            ->give(function ($app) {
                return StorageProvider::new('certificaciones', 'uploads/content');
            });

        $this->app->when(SaveGalleryImageUseCase::class)
            ->needs(StorageProviderInterface::class)
            ->give(function ($app) {
                return StorageProvider::new('galeria', 'uploads/content');
            });

        $this->app->when(UpdateGalleryImageUseCase::class)
            ->needs(StorageProviderInterface::class)
            ->give(function ($app) {
                return StorageProvider::new('galeria', 'uploads/content');
            });

        $this->app->when(DeleteGalleryImageUseCase::class)
            ->needs(StorageProviderInterface::class)
            ->give(function ($app) {
                return StorageProvider::new('galeria', 'uploads/content');
            });
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
