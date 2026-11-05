<?php

declare(strict_types=1);

/*
 * Spec 020 (P3, A18): every file of the Domain of Appointments that this spec creates or changes
 * knows neither the Infrastructure of any module, nor facades, nor Eloquent. TreatmentsService
 * stays out on purpose: it depends on the final EloquentTreatmentRepository (known P3 debt, see
 * UnexpectedErrorTest) and this spec does not touch it.
 */

const APPOINTMENTS_ARCHITECTURE_FORBIDDEN = '/App\\\\Modules\\\\[^\\\\;]+\\\\Infrastructure|Illuminate\\\\Support\\\\Facades|Illuminate\\\\Database\\\\Eloquent/';

/*
 * Spec 024 (P3, D1): the port that draws QR codes and the use case that uses it live in the
 * Application layer and know no QR library either; only the adapter in Infrastructure does.
 */
const APPOINTMENTS_APPLICATION_FORBIDDEN = '/App\\\\Modules\\\\[^\\\\;]+\\\\Infrastructure|Illuminate\\\\Support\\\\Facades|Illuminate\\\\Database\\\\Eloquent|chillerlan/';

/**
 * The lines of a file of a layer of Appointments that name a forbidden namespace, in a `use` or
 * written in full in the code.
 *
 * @return list<string>
 */
function appointmentsLayerViolations(string $layer, string $file, string $forbidden): array
{
    $path = dirname(__DIR__, 4)."/app/Modules/Appointments/{$layer}/".$file;

    expect(is_file($path))->toBeTrue("No existe app/Modules/Appointments/{$layer}/{$file}.");

    $violations = [];

    foreach (file($path, FILE_IGNORE_NEW_LINES) as $number => $line) {
        if (preg_match($forbidden, $line) === 1) {
            $violations[] = ($number + 1).': '.trim($line);
        }
    }

    return $violations;
}

/**
 * @return list<string>
 */
function appointmentsDomainViolations(string $file): array
{
    return appointmentsLayerViolations('Domain', $file, APPOINTMENTS_ARCHITECTURE_FORBIDDEN);
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

it('keeps the port and the use case of the QR code free of infrastructure and of the QR library', function (string $file) {
    expect(appointmentsLayerViolations('Aplication', $file, APPOINTMENTS_APPLICATION_FORBIDDEN))->toBe([]);
})->with([
    'Support/QrCodeGeneratorInterface.php',
    'UseCases/GetAppointmentQrUseCase.php',
])->skip('024: pendiente de T021');
