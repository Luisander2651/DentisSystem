<?php

declare(strict_types=1);

namespace App\Core\Console;

use Database\Seeders\UiAuditSeeder;
use Illuminate\Console\Command;

/**
 * Spec 016 (TM5): seeds the data `npm run test:ui` browses and, with --clean, removes only
 * what the seeder marked. `db:seed` cannot do the second half, because it takes no options
 * of its own. Registered in routes/console.php for `local` and `testing` only.
 */
final class UiAuditDataCommand extends Command
{
    protected $signature = 'ui:audit-data {--clean : Retira solo lo que sembró UiAuditSeeder}';

    protected $description = 'Siembra los datos de las pruebas de interfaz, o los retira con --clean';

    public function handle(UiAuditSeeder $seeder): int
    {
        if (! app()->environment(UiAuditSeeder::ALLOWED_ENVIRONMENTS)) {
            $this->error('Este comando solo se ejecuta en los entornos local y testing.');

            return self::FAILURE;
        }

        if ($this->option('clean')) {
            $seeder->clean();
            $this->info('Datos de las pruebas de interfaz retirados.');

            return self::SUCCESS;
        }

        $seeder->run();
        $this->info('Datos de las pruebas de interfaz sembrados.');

        return self::SUCCESS;
    }
}
