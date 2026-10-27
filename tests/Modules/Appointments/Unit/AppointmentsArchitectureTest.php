<?php

declare(strict_types=1);

/*
 * Spec 020 (P3, A18): every file of the Domain of Appointments that this spec creates or changes
 * knows neither the Infrastructure of any module, nor facades, nor Eloquent. TreatmentsService
 * stays out on purpose: it depends on the final EloquentTreatmentRepository (known P3 debt, see
 * UnexpectedErrorTest) and this spec does not touch it.
 */

const APPOINTMENTS_ARCHITECTURE_FORBIDDEN = '/App\\\\Modules\\\\[^\\\\;]+\\\\Infrastructure|Illuminate\\\\Support\\\\Facades|Illuminate\\\\Database\\\\Eloquent/';

/**
 * The lines of a file of the Domain of Appointments that name a forbidden namespace, in a `use`
 * or written in full in the code.
 *
 * @return list<string>
 */
function appointmentsDomainViolations(string $file): array
{
    $path = dirname(__DIR__, 4).'/app/Modules/Appointments/Domain/'.$file;

    expect(is_file($path))->toBeTrue("No existe app/Modules/Appointments/Domain/{$file}.");

    $violations = [];

    foreach (file($path, FILE_IGNORE_NEW_LINES) as $number => $line) {
        if (preg_match(APPOINTMENTS_ARCHITECTURE_FORBIDDEN, $line) === 1) {
            $violations[] = ($number + 1).': '.trim($line);
        }
    }

    return $violations;
}

it('keeps the files of the Domain this spec changes free of infrastructure', function (string $file) {
    expect(appointmentsDomainViolations($file))->toBe([]);
})->with([
    'Repositories/AppointmentsRepositoryInterface.php',
    'Service/AppointmentsService.php',
]);

it('keeps the agenda of today free of infrastructure', function () {
    expect(appointmentsDomainViolations('ValueObjects/TodayAgenda.php'))->toBe([]);
});

it('keeps the page of appointments free of infrastructure', function () {
    expect(appointmentsDomainViolations('ValueObjects/AppointmentPage.php'))->toBe([]);
});
