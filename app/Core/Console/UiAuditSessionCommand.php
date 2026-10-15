<?php

declare(strict_types=1);

namespace App\Core\Console;

use App\Modules\Patients\Infrastructure\Persistence\Eloquent\Models\PatientModel;
use App\Modules\Users\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Database\Seeders\UiAuditSeeder;
use Illuminate\Console\Command;

/**
 * Spec 016 (TM5): prints a session token of the account UiAuditSeeder created for a role,
 * so `npm run test:ui` can browse as that role without a password in the repository.
 * It never looks an account up by anything the caller sends: the role only picks one of the
 * seeded addresses. Registered in routes/console.php for `local` and `testing` only.
 */
final class UiAuditSessionCommand extends Command
{
    public const TOKEN_NAME = 'ui-audit';

    protected $signature = 'ui:audit-session {rol : administrador, asistente, doctor o paciente}';

    protected $description = 'Emite una sesión de un usuario sembrado por UiAuditSeeder para las pruebas de interfaz';

    public function handle(): int
    {
        if (! app()->environment(UiAuditSeeder::ALLOWED_ENVIRONMENTS)) {
            $this->error('Este comando solo se ejecuta en los entornos local y testing.');

            return self::FAILURE;
        }

        $account = $this->seededAccount((string) $this->argument('rol'));

        if ($account === null) {
            $this->error('No hay un usuario sembrado para ese rol. Roles: administrador, asistente, doctor, paciente. Ejecuta antes ui:audit-data.');

            return self::FAILURE;
        }

        $this->line($account->createToken(self::TOKEN_NAME)->plainTextToken);

        return self::SUCCESS;
    }

    private function seededAccount(string $role): UserModel|PatientModel|null
    {
        return match ($role) {
            'administrador', 'asistente', 'doctor' => UserModel::query()
                ->where('email', UiAuditSeeder::STAFF_EMAILS[$role])
                ->where('status', 'active')
                ->first(),
            'paciente' => PatientModel::query()->where('email', UiAuditSeeder::PATIENT_EMAIL)->first(),
            default => null,
        };
    }
}
