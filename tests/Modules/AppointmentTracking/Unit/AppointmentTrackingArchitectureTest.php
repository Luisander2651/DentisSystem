<?php

declare(strict_types=1);

/*
 * Spec 020 (P3, A18): every file of the Domain of AppointmentTracking that this spec creates or
 * changes knows neither the Infrastructure of any module, nor facades, nor Eloquent.
 */

const TRACKING_ARCHITECTURE_FORBIDDEN = '/App\\\\Modules\\\\[^\\\\;]+\\\\Infrastructure|Illuminate\\\\Support\\\\Facades|Illuminate\\\\Database\\\\Eloquent/';

/**
 * The lines of a file of the Domain of AppointmentTracking that name a forbidden namespace, in a
 * `use` or written in full in the code.
 *
 * @return list<string>
 */
function trackingDomainViolations(string $file): array
{
    $path = dirname(__DIR__, 4).'/app/Modules/AppointmentTracking/Domain/'.$file;

    expect(is_file($path))->toBeTrue("No existe app/Modules/AppointmentTracking/Domain/{$file}.");

    $violations = [];

    foreach (file($path, FILE_IGNORE_NEW_LINES) as $number => $line) {
        if (preg_match(TRACKING_ARCHITECTURE_FORBIDDEN, $line) === 1) {
            $violations[] = ($number + 1).': '.trim($line);
        }
    }

    return $violations;
}

it('keeps the files of the Domain this spec changes free of infrastructure', function (string $file) {
    expect(trackingDomainViolations($file))->toBe([]);
})->with([
    'Repositories/AppointmentCompletionRepositoryInterface.php',
    'Service/AppointmentCompletionService.php',
]);

it('keeps the new files of the Domain free of infrastructure', function (string $file) {
    expect(trackingDomainViolations($file))->toBe([]);
})->with([
    'Repositories/PendingTrackingRepositoryInterface.php',
    'Exceptions/TrackingRegistrationException.php',
])->skip('020: pendiente de T036');
