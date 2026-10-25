<?php

declare(strict_types=1);

use App\Modules\Appointments\Domain\Entities\AppointmentEntity;
use App\Modules\Appointments\Domain\Exceptions\ValueObjects\AppointmentPageException;
use App\Modules\Appointments\Domain\ValueObjects\AppointmentPage;
use Illuminate\Support\Str;

/*
 * Spec 020 (CA4, CA7, TM8): a page of appointments carries at most 10 of them and the total of
 * all that match, which is never less than the ones it carries.
 */

/**
 * @return list<AppointmentEntity>
 */
function pageAppointments(int $count): array
{
    return array_map(fn (int $index): AppointmentEntity => AppointmentEntity::fromPrimitives(
        id: (string) Str::uuid(),
        date: '2026-10-21',
        time: sprintf('%02d:00', 8 + $index),
        whatsappReminder: false,
        status: 'asignada',
        treatmentId: '1',
        userId: (string) Str::uuid(),
        patientId: (string) Str::uuid(),
        treatmentName: null,
        userFirstName: null,
        userLastName: null,
        patientName: null,
        treatmentTime: null,
        createdAt: '2026-10-01 09:00:00',
        updatedAt: '2026-10-01 09:00:00',
    ), $count === 0 ? [] : range(0, $count - 1));
}

it('carries up to 10 appointments and the total of all of them', function () {
    $items = pageAppointments(10);

    $page = AppointmentPage::create($items, 15);

    expect($page->items)->toBe($items)
        ->and($page->total)->toBe(15);
})->skip('020: pendiente de T031');

it('carries an empty page', function () {
    $page = AppointmentPage::create([], 0);

    expect($page->items)->toBe([])
        ->and($page->total)->toBe(0);
})->skip('020: pendiente de T031');

it('refuses more appointments than the limit', function () {
    expect(fn () => AppointmentPage::create(pageAppointments(11), 11))->toThrow(AppointmentPageException::class);
})->skip('020: pendiente de T031');

it('refuses a total smaller than the appointments it carries', function (int $count, int $total) {
    expect(fn () => AppointmentPage::create(pageAppointments($count), $total))->toThrow(AppointmentPageException::class);
})->with([
    'menos que las citas' => [3, 2],
    'cero con citas' => [1, 0],
    'negativo' => [0, -1],
])->skip('020: pendiente de T031');
