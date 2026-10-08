<?php

use App\Core\Console\UiAuditDataCommand;
use Database\Seeders\UiAuditSeeder;
use Illuminate\Console\Application as ConsoleApplication;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * Spec 016 (TM5): the commands behind `npm run test:ui` do not exist outside local and
 * testing. They live in app/Core/Console, which Laravel does not discover on its own.
 */
if (app()->environment(UiAuditSeeder::ALLOWED_ENVIRONMENTS)) {
    ConsoleApplication::starting(function (ConsoleApplication $artisan): void {
        $artisan->resolveCommands([
            UiAuditDataCommand::class,
        ]);
    });
}
