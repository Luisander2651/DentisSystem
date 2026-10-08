<?php

declare(strict_types=1);

use App\Modules\Appointments\Infrastructure\Persistence\Eloquent\Models\AppointmentModel;
use App\Modules\Appointments\Infrastructure\Persistence\Eloquent\Models\TreatmentModel;
use App\Modules\AppointmentTracking\Infrastructure\Persistence\Eloquent\Models\AppointmentTrackingModel;
use App\Modules\ContentManagement\Modules\Certificaciones\Infrastructure\Persistence\Eloquent\Models\CertificationModel;
use App\Modules\ContentManagement\Modules\Galeria\Infrastructure\Persistence\Eloquent\Models\GalleryImageModel;
use App\Modules\ContentManagement\Modules\Promociones\Infrastructure\Persistence\Eloquent\Models\PromotionModel;
use App\Modules\ContentManagement\Modules\Testimonios\Infrastructure\Persistence\Eloquent\Models\TestimonialModel;
use App\Modules\Patients\Infrastructure\Persistence\Eloquent\Models\AddressesModel;
use App\Modules\Patients\Infrastructure\Persistence\Eloquent\Models\ContactInfoModel;
use App\Modules\Patients\Infrastructure\Persistence\Eloquent\Models\MedicalDataModel;
use App\Modules\Patients\Infrastructure\Persistence\Eloquent\Models\PatientModel;
use App\Modules\Users\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Database\Seeders\UiAuditSeeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\Modules\Core\Integration\CoreIntegrationTestCase;

uses(CoreIntegrationTestCase::class);

/*
 * Spec 016, TM5: the data `npm run test:ui` browses is seeded by UiAuditSeeder. It only runs
 * in `local` and `testing`, seeding twice changes nothing, and `ui:audit-data --clean` removes
 * exactly what the seeder marked (A36, A56, A65).
 */

beforeEach(function () {
    Storage::fake('public');
});

/**
 * @return array<string, int>
 */
function uiAuditRowCounts(): array
{
    return [
        'users' => UserModel::query()->count(),
        'patients' => PatientModel::query()->count(),
        'contact_info' => ContactInfoModel::query()->count(),
        'addresses' => AddressesModel::query()->count(),
        'medical_data' => MedicalDataModel::query()->count(),
        'treatments' => TreatmentModel::query()->count(),
        'appointments' => AppointmentModel::query()->count(),
        'appointment_tracking' => AppointmentTrackingModel::query()->count(),
        'galery_images' => GalleryImageModel::query()->count(),
        'promotions' => PromotionModel::query()->count(),
        'certifications' => CertificationModel::query()->count(),
        'testimonials' => TestimonialModel::query()->count(),
        'files' => count(Storage::disk('public')->allFiles()),
    ];
}

it('seeds one user per role, an inactive staff member, two patients, appointments and content', function () {
    (new UiAuditSeeder)->run();

    $staff = UserModel::query()->with('role')->where('email', 'like', '%@'.UiAuditSeeder::EMAIL_DOMAIN)->get();

    expect($staff->where('status', 'active')->pluck('role.name')->sort()->values()->all())
        ->toBe(['Administrador', 'Asistente', 'Doctor'])
        ->and($staff->where('status', 'inactive'))->toHaveCount(1);

    $fullRecord = PatientModel::query()->where('email', UiAuditSeeder::PATIENT_EMAIL)->firstOrFail();
    $emptyRecord = PatientModel::query()->where('email', UiAuditSeeder::EMPTY_PATIENT_EMAIL)->firstOrFail();

    expect($fullRecord->contactInfo()->count())->toBe(1)
        ->and($fullRecord->addresses()->count())->toBe(1)
        ->and($fullRecord->medicalData()->count())->toBe(1)
        ->and($emptyRecord->contactInfo()->count())->toBe(0)
        ->and($emptyRecord->addresses()->count())->toBe(0)
        ->and($emptyRecord->medicalData()->count())->toBe(0);

    $appointments = AppointmentModel::query()->where('patient_id', $fullRecord->id)->get();

    expect($appointments->pluck('status')->unique()->sort()->values()->all())
        ->toBe(['asignada', 'cancelada', 'completada', 'reprogramada'])
        ->and($appointments->every(fn (AppointmentModel $appointment): bool => str_starts_with((string) $appointment->date, UiAuditSeeder::APPOINTMENTS_MONTH)))
        ->toBeTrue()
        ->and(AppointmentTrackingModel::query()->whereIn('appointment_id', $appointments->pluck('id'))->count())->toBe(1);

    expect(TreatmentModel::query()->whereIn('name', UiAuditSeeder::TREATMENT_NAMES)->count())->toBe(3)
        ->and(GalleryImageModel::query()->count())->toBeGreaterThan(0)
        ->and(PromotionModel::query()->count())->toBeGreaterThan(0)
        ->and(CertificationModel::query()->count())->toBeGreaterThan(0)
        ->and(TestimonialModel::query()->count())->toBeGreaterThan(0);
});

it('stores neutral images of its own under its reserved directory', function () {
    (new UiAuditSeeder)->run();

    $files = Storage::disk('public')->allFiles();

    expect($files)->not->toBeEmpty();

    foreach ($files as $file) {
        expect($file)->toStartWith(UiAuditSeeder::IMAGE_DIRECTORY.'/')
            ->and(Storage::disk('public')->size($file))->toBeGreaterThan(0);
    }

    foreach (GalleryImageModel::query()->pluck('url') as $url) {
        expect($url)->toStartWith('/storage/'.UiAuditSeeder::IMAGE_DIRECTORY.'/');
        Storage::disk('public')->assertExists(Str::after($url, '/storage/'));
    }
});

it('refuses to run outside local and testing and writes nothing (abuse)', function (string $environment) {
    $this->app['env'] = $environment;

    expect(fn () => (new UiAuditSeeder)->run())->toThrow(RuntimeException::class);

    expect(array_sum(uiAuditRowCounts()))->toBe(0);
})->with(['production', 'staging', 'prod']);

it('refuses to clean outside local and testing (abuse)', function () {
    (new UiAuditSeeder)->run();
    $before = uiAuditRowCounts();

    $this->app['env'] = 'production';

    expect(fn () => (new UiAuditSeeder)->clean())->toThrow(RuntimeException::class)
        ->and(uiAuditRowCounts())->toBe($before);
});

it('does not duplicate anything when it runs twice', function () {
    (new UiAuditSeeder)->run();
    $first = uiAuditRowCounts();

    (new UiAuditSeeder)->run();

    expect(uiAuditRowCounts())->toBe($first);
});

it('seeds through the ui:audit-data command', function () {
    $this->artisan('ui:audit-data')->assertSuccessful();

    expect(UserModel::query()->where('email', 'like', '%@'.UiAuditSeeder::EMAIL_DOMAIN)->count())->toBe(4);
});

it('fails the ui:audit-data command outside local and testing without writing (abuse)', function () {
    $this->app['env'] = 'production';

    $this->artisan('ui:audit-data')->assertFailed();

    expect(array_sum(uiAuditRowCounts()))->toBe(0);
});

it('removes with --clean only what the seeder marked', function () {
    $this->artisan('ui:audit-data')->assertSuccessful();

    $seededAdmin = UserModel::query()->where('email', UiAuditSeeder::STAFF_EMAILS['administrador'])->firstOrFail();
    $seededAdmin->createToken('ui-audit');

    // Records of someone else, as close to the seeded ones as they can get.
    $foreignUser = $this->createUserWithRole('Administrador', ['email' => 'administrador@'.UiAuditSeeder::EMAIL_DOMAIN.'.mx']);
    $foreignToken = $foreignUser->createToken('foreign');
    $foreignPatient = $this->createPatient(['email' => str_replace('@', '.', UiAuditSeeder::PATIENT_EMAIL).'@example.com']);
    ContactInfoModel::query()->create(['patient_id' => $foreignPatient->id, 'phone_number' => '+52 744 000 0000']);
    $foreignTreatment = TreatmentModel::query()->create([
        'name' => UiAuditSeeder::TREATMENT_NAMES[0].' 2', 'description' => 'Ajeno', 'time' => 30,
    ]);
    $foreignAppointment = AppointmentModel::query()->create([
        'id' => (string) Str::uuid(),
        'date' => UiAuditSeeder::APPOINTMENTS_MONTH.'-15',
        'time' => '10:00:00',
        'status' => 'asignada',
        'treatment_id' => $foreignTreatment->id,
        'user_id' => $foreignUser->id,
        'patient_id' => $foreignPatient->id,
    ]);
    $foreignImage = GalleryImageModel::query()->create([
        'url' => '/storage/galeria/'.UiAuditSeeder::IMAGE_DIRECTORY.'-foto.png', 'description' => 'Ajena', 'status' => 'visible',
    ]);
    $foreignPromotion = PromotionModel::query()->create([
        'name' => PromotionModel::query()->firstOrFail()->name.' (copia)',
        'description' => 'Ajena',
        'status' => 'visible',
        'discount_percentage' => '10',
        'start_date' => '2026-07-01',
        'end_date' => '2026-07-31',
    ]);
    $foreignCertification = CertificationModel::query()->create([
        'name' => 'Ajena', 'status' => 'visible', 'date' => '2026-07-01',
        'image_url' => '/storage/certificaciones/'.UiAuditSeeder::IMAGE_DIRECTORY.'.png',
    ]);
    $foreignTestimonial = TestimonialModel::query()->create([
        'author' => TestimonialModel::query()->firstOrFail()->author.' (copia)', 'description' => 'Ajeno', 'status' => 'visible',
    ]);
    Storage::disk('public')->put('galeria/'.UiAuditSeeder::IMAGE_DIRECTORY.'-foto.png', 'foreign');

    $this->artisan('ui:audit-data', ['--clean' => true])->assertSuccessful();

    expect(uiAuditRowCounts())->toBe([
        'users' => 1,
        'patients' => 1,
        'contact_info' => 1,
        'addresses' => 0,
        'medical_data' => 0,
        'treatments' => 1,
        'appointments' => 1,
        'appointment_tracking' => 0,
        'galery_images' => 1,
        'promotions' => 1,
        'certifications' => 1,
        'testimonials' => 1,
        'files' => 1,
    ]);

    expect($foreignUser->fresh())->not->toBeNull()
        ->and($foreignPatient->fresh())->not->toBeNull()
        ->and($foreignTreatment->fresh())->not->toBeNull()
        ->and($foreignAppointment->fresh())->not->toBeNull()
        ->and($foreignImage->fresh())->not->toBeNull()
        ->and($foreignPromotion->fresh())->not->toBeNull()
        ->and($foreignCertification->fresh())->not->toBeNull()
        ->and($foreignTestimonial->fresh())->not->toBeNull()
        ->and(PersonalAccessToken::query()->pluck('id')->all())->toBe([$foreignToken->accessToken->id]);
});

it('keeps a seeded user and treatment that an appointment of someone else still uses', function () {
    (new UiAuditSeeder)->run();

    $seededDoctor = UserModel::query()->where('email', UiAuditSeeder::STAFF_EMAILS['doctor'])->firstOrFail();
    $seededTreatment = TreatmentModel::query()->where('name', UiAuditSeeder::TREATMENT_NAMES[0])->firstOrFail();
    $foreignPatient = $this->createPatient();
    $foreignAppointment = AppointmentModel::query()->create([
        'id' => (string) Str::uuid(),
        'date' => '2026-08-03',
        'time' => '09:00:00',
        'status' => 'asignada',
        'treatment_id' => $seededTreatment->id,
        'user_id' => $seededDoctor->id,
        'patient_id' => $foreignPatient->id,
    ]);

    (new UiAuditSeeder)->clean();

    expect($foreignAppointment->fresh())->not->toBeNull()
        ->and($seededDoctor->fresh())->not->toBeNull()
        ->and($seededTreatment->fresh())->not->toBeNull()
        ->and(UserModel::query()->where('email', UiAuditSeeder::STAFF_EMAILS['administrador'])->exists())->toBeFalse()
        ->and(TreatmentModel::query()->where('name', UiAuditSeeder::TREATMENT_NAMES[1])->exists())->toBeFalse();
});
