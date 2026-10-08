<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Appointments\Infrastructure\Persistence\Eloquent\Models\AppointmentModel;
use App\Modules\Appointments\Infrastructure\Persistence\Eloquent\Models\TreatmentModel;
use App\Modules\AppointmentTracking\Infrastructure\Persistence\Eloquent\Models\AppointmentTrackingModel;
use App\Modules\AppointmentTracking\Infrastructure\Persistence\Eloquent\Models\AppointmentTrackingPrescriptionModel;
use App\Modules\ContentManagement\Modules\Certificaciones\Infrastructure\Persistence\Eloquent\Models\CertificationModel;
use App\Modules\ContentManagement\Modules\Galeria\Infrastructure\Persistence\Eloquent\Models\GalleryImageModel;
use App\Modules\ContentManagement\Modules\Promociones\Infrastructure\Persistence\Eloquent\Models\PromotionModel;
use App\Modules\ContentManagement\Modules\Testimonios\Infrastructure\Persistence\Eloquent\Models\TestimonialModel;
use App\Modules\Patients\Infrastructure\Persistence\Eloquent\Models\AddressesModel;
use App\Modules\Patients\Infrastructure\Persistence\Eloquent\Models\ContactInfoModel;
use App\Modules\Patients\Infrastructure\Persistence\Eloquent\Models\MedicalDataModel;
use App\Modules\Patients\Infrastructure\Persistence\Eloquent\Models\PatientModel;
use App\Modules\Users\Domain\ValueObjects\UserRoleId;
use App\Modules\Users\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;
use Ramsey\Uuid\Uuid;
use RuntimeException;

/**
 * Spec 016 (TM5): the data `npm run test:ui` browses.
 *
 * It lives next to whatever the local database already holds, so everything it creates is
 * marked without touching the schema, and clean() removes exactly that:
 * - users and patients: an email in the reserved domain EMAIL_DOMAIN;
 * - whatever hangs from them (contact, address, medical data, appointments, tracking,
 *   prescriptions, tokens): their foreign keys;
 * - treatments, promotions and testimonials: an exact reserved name;
 * - gallery images and certifications: an image under IMAGE_DIRECTORY, which the seeder
 *   draws itself.
 *
 * No account gets a known password: sessions come from `ui:audit-session`. It only runs in
 * the environments of ALLOWED_ENVIRONMENTS.
 */
final class UiAuditSeeder extends Seeder
{
    public const ALLOWED_ENVIRONMENTS = ['local', 'testing'];

    public const EMAIL_DOMAIN = 'ui-audit.dentissa.test';

    public const STAFF_EMAILS = [
        'administrador' => 'administrador@'.self::EMAIL_DOMAIN,
        'asistente' => 'asistente@'.self::EMAIL_DOMAIN,
        'doctor' => 'doctor@'.self::EMAIL_DOMAIN,
        'inactivo' => 'inactivo@'.self::EMAIL_DOMAIN,
    ];

    public const PATIENT_EMAIL = 'paciente@'.self::EMAIL_DOMAIN;

    public const EMPTY_PATIENT_EMAIL = 'paciente.vacio@'.self::EMAIL_DOMAIN;

    public const APPOINTMENTS_MONTH = '2026-07';

    public const TREATMENT_NAMES = [
        'Limpieza dental de prueba',
        'Resina de prueba',
        'Ortodoncia de prueba',
    ];

    public const PROMOTION_NAMES = [
        'Promoción de prueba: limpieza dental',
        'Promoción de prueba: blanqueamiento',
    ];

    public const TESTIMONIAL_AUTHORS = [
        'Paciente de prueba A',
        'Paciente de prueba B',
    ];

    public const IMAGE_DIRECTORY = 'ui-audit';

    private const IMAGE_DISK = 'public';

    public function run(): void
    {
        $this->assertAllowedEnvironment();

        (new RoleSeeder)->run();

        $staff = $this->seedStaff();
        $patient = $this->seedPatientWithFullRecord();
        $this->seedPatient(self::EMPTY_PATIENT_EMAIL, 'Pedro', 'Prueba');
        $treatments = $this->seedTreatments();
        $this->seedAppointments($patient, $staff['doctor'], $treatments);
        $this->seedContent();
    }

    /**
     * Removes only what run() marked. A seeded user or treatment that an appointment of
     * someone else still points to stays, because deleting it would cascade to that
     * appointment.
     */
    public function clean(): void
    {
        $this->assertAllowedEnvironment();

        $userIds = UserModel::query()->where('email', 'like', '%@'.self::EMAIL_DOMAIN)->pluck('id');
        $patientIds = PatientModel::query()->where('email', 'like', '%@'.self::EMAIL_DOMAIN)->pluck('id');

        PersonalAccessToken::query()
            ->where(fn ($query) => $query
                ->where('tokenable_type', (new UserModel)->getMorphClass())
                ->whereIn('tokenable_id', $userIds))
            ->orWhere(fn ($query) => $query
                ->where('tokenable_type', (new PatientModel)->getMorphClass())
                ->whereIn('tokenable_id', $patientIds))
            ->delete();

        PatientModel::query()->whereIn('id', $patientIds)->delete();

        UserModel::query()
            ->whereIn('id', $userIds)
            ->whereNotIn('id', AppointmentModel::query()->select('user_id'))
            ->delete();

        TreatmentModel::query()
            ->whereIn('name', self::TREATMENT_NAMES)
            ->whereNotIn('id', AppointmentModel::query()->select('treatment_id'))
            ->delete();

        GalleryImageModel::query()->where('url', 'like', $this->imageUrlPrefix().'%')->delete();
        CertificationModel::query()->where('image_url', 'like', $this->imageUrlPrefix().'%')->delete();
        PromotionModel::query()->whereIn('name', self::PROMOTION_NAMES)->delete();
        TestimonialModel::query()->whereIn('author', self::TESTIMONIAL_AUTHORS)->delete();

        Storage::disk(self::IMAGE_DISK)->deleteDirectory(self::IMAGE_DIRECTORY);
    }

    private function assertAllowedEnvironment(): void
    {
        if (! app()->environment(self::ALLOWED_ENVIRONMENTS)) {
            throw new RuntimeException('UiAuditSeeder solo se ejecuta en los entornos local y testing.');
        }
    }

    /**
     * @return array<string, UserModel>
     */
    private function seedStaff(): array
    {
        $members = [
            'administrador' => ['Alma', 'Prueba', 'Administrador', 'active'],
            'asistente' => ['Aurora', 'Prueba', 'Asistente', 'active'],
            'doctor' => ['Daniela', 'Prueba', 'Doctor', 'active'],
            'inactivo' => ['Ignacio', 'Prueba', 'Asistente', 'inactive'],
        ];

        $staff = [];

        foreach ($members as $key => [$firstName, $lastName, $role, $status]) {
            $user = UserModel::query()->firstOrNew(['email' => self::STAFF_EMAILS[$key]]);

            if (! $user->exists) {
                $user->id = (string) Str::uuid();
                $user->password = Hash::make(Str::random(40));
            }

            $user->fill([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'status' => $status,
                'role_id' => UserRoleId::fromString($role)->toDatabaseId(),
            ])->save();

            $staff[$key] = $user;
        }

        return $staff;
    }

    private function seedPatient(string $email, string $firstName, string $lastName): PatientModel
    {
        $patient = PatientModel::query()->firstOrNew(['email' => $email]);

        if (! $patient->exists) {
            $patient->id = (string) Str::uuid();
            $patient->password = Hash::make(Str::random(40));
        }

        $patient->fill([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'status' => 'active',
            'role' => 'patient',
        ])->save();

        return $patient;
    }

    private function seedPatientWithFullRecord(): PatientModel
    {
        $patient = $this->seedPatient(self::PATIENT_EMAIL, 'Paula', 'Prueba');

        ContactInfoModel::query()->updateOrCreate(['patient_id' => $patient->id], [
            'phone_number' => '+52 744 555 0100',
            'emergency_contact' => 'Pablo Prueba',
            'email' => 'contacto.paula@'.self::EMAIL_DOMAIN,
        ]);

        AddressesModel::query()->updateOrCreate(['patient_id' => $patient->id], [
            'street' => 'Avenida de Prueba 123',
            'city' => 'Acapulco',
            'state' => 'Guerrero',
            'postal_code' => '39300',
        ]);

        MedicalDataModel::query()->updateOrCreate(['patient_id' => $patient->id], [
            'blood_type' => 'O+',
            'allergies' => ['Penicilina'],
            'medications' => ['Ibuprofeno'],
            'last_dentist_visit' => ['2026-01-15'],
        ]);

        return $patient;
    }

    /**
     * @return list<TreatmentModel>
     */
    private function seedTreatments(): array
    {
        $details = [
            ['Limpieza y revisión general.', 30],
            ['Restauración de una pieza con resina.', 45],
            ['Revisión y ajuste de ortodoncia.', 60],
        ];

        $treatments = [];

        foreach (self::TREATMENT_NAMES as $index => $name) {
            $treatments[] = TreatmentModel::query()->updateOrCreate(['name' => $name], [
                'description' => $details[$index][0],
                'time' => $details[$index][1],
            ]);
        }

        return $treatments;
    }

    /**
     * Every status, on fixed days of APPOINTMENTS_MONTH; the completed one carries its
     * clinical follow-up and a prescription.
     *
     * @param  list<TreatmentModel>  $treatments
     */
    private function seedAppointments(PatientModel $patient, UserModel $doctor, array $treatments): void
    {
        $appointments = [
            ['06', '09:00:00', 'completada', 0],
            ['08', '10:30:00', 'cancelada', 1],
            ['14', '12:00:00', 'reprogramada', 2],
            ['22', '10:15:00', 'asignada', 0],
            ['22', '16:00:00', 'asignada', 1],
        ];

        foreach ($appointments as $index => [$day, $time, $status, $treatment]) {
            AppointmentModel::query()->updateOrCreate(['id' => $this->stableId('appointment', $index)], [
                'date' => self::APPOINTMENTS_MONTH.'-'.$day,
                'time' => $time,
                'whatsapp_reminder' => false,
                'status' => $status,
                'treatment_id' => $treatments[$treatment]->id,
                'user_id' => $doctor->id,
                'patient_id' => $patient->id,
            ]);
        }

        $tracking = AppointmentTrackingModel::query()->updateOrCreate(['id' => $this->stableId('tracking', 0)], [
            'reason' => 'Limpieza semestral.',
            'symptoms' => ['sensibilidad'],
            'diagnosis' => 'Acumulación leve de sarro.',
            'procedure_performed' => 'Limpieza con ultrasonido.',
            'observations' => 'Sin complicaciones.',
            'recommendations' => 'Usar hilo dental a diario.',
            'appointment_id' => $this->stableId('appointment', 0),
        ]);

        AppointmentTrackingPrescriptionModel::query()->updateOrCreate(['id' => $this->stableId('prescription', 0)], [
            'medication' => 'Ibuprofeno 400 mg',
            'dosage' => '1 tableta',
            'duration_days' => 3,
            'daily_frequency' => 2,
            'instructions' => 'Tomar después de los alimentos.',
            'appointment_tracking_id' => $tracking->id,
        ]);
    }

    private function seedContent(): void
    {
        $gallery = [
            ['Sala de espera', 'visible'],
            ['Consultorio principal', 'visible'],
            ['Área de esterilización', 'hidden'],
        ];

        foreach ($gallery as $index => [$description, $status]) {
            GalleryImageModel::query()->updateOrCreate(['url' => $this->storeImage('galeria-'.($index + 1))], [
                'description' => $description,
                'status' => $status,
            ]);
        }

        $certifications = [
            ['Certificación en odontología general', 'Colegio de prueba', 'visible', '2024-03-10'],
            ['Diplomado en ortodoncia', 'Instituto de prueba', 'oculto', '2025-06-20'],
        ];

        foreach ($certifications as $index => [$name, $description, $status, $date]) {
            CertificationModel::query()->updateOrCreate(['image_url' => $this->storeImage('certificacion-'.($index + 1))], [
                'name' => $name,
                'description' => $description,
                'status' => $status,
                'date' => $date,
            ]);
        }

        $promotions = [
            ['Limpieza dental con descuento durante julio.', 'visible', '20'],
            ['Blanqueamiento con precio especial.', 'oculto', '15'],
        ];

        foreach (self::PROMOTION_NAMES as $index => $name) {
            PromotionModel::query()->updateOrCreate(['name' => $name], [
                'description' => $promotions[$index][0],
                'status' => $promotions[$index][1],
                'discount_percentage' => $promotions[$index][2],
                'start_date' => self::APPOINTMENTS_MONTH.'-01',
                'end_date' => self::APPOINTMENTS_MONTH.'-31',
            ]);
        }

        $testimonials = [
            ['Excelente atención, muy recomendable.', 'visible'],
            ['El trato fue amable y puntual.', 'oculto'],
        ];

        foreach (self::TESTIMONIAL_AUTHORS as $index => $author) {
            TestimonialModel::query()->updateOrCreate(['author' => $author], [
                'description' => $testimonials[$index][0],
                'status' => $testimonials[$index][1],
            ]);
        }
    }

    /**
     * Draws a neutral placeholder and returns the address the app serves it from.
     */
    private function storeImage(string $name): string
    {
        $path = self::IMAGE_DIRECTORY.'/'.$name.'.svg';

        Storage::disk(self::IMAGE_DISK)->put($path, <<<'SVG'
            <svg xmlns="http://www.w3.org/2000/svg" width="1200" height="800" viewBox="0 0 1200 800">
                <rect width="1200" height="800" fill="#e2e8f0"/>
                <text x="600" y="420" text-anchor="middle" font-family="sans-serif" font-size="56" fill="#0b1120">Imagen de prueba</text>
            </svg>
            SVG);

        return '/storage/'.$path;
    }

    private function imageUrlPrefix(): string
    {
        return '/storage/'.self::IMAGE_DIRECTORY.'/';
    }

    private function stableId(string $kind, int $index): string
    {
        return Uuid::uuid5(Uuid::NAMESPACE_URL, 'https://'.self::EMAIL_DOMAIN.'/'.$kind.'/'.$index)->toString();
    }
}
