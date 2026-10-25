<?php

declare(strict_types=1);

use App\Modules\Appointments\Domain\Entities\AppointmentEntity;
use App\Modules\Appointments\Domain\Exceptions\ValueObjects\AppointmentPageException;
use App\Modules\Appointments\Domain\ValueObjects\AppointmentPage;
use Illuminate\Support\Str;
use Tests\Support\UsesEris;

uses(UsesEris::class);

/*
 * Spec 020 (CA4, CA7, TM8): for any number of appointments and any total, a page is created only
 * when it carries at most 10 and the total is at least the ones it carries.
 */

/**
 * @return list<AppointmentEntity>
 */
function pagePropertyAppointments(int $count): array
{
    return array_map(fn (): AppointmentEntity => AppointmentEntity::fromPrimitives(
        id: (string) Str::uuid(),
        date: '2026-10-21',
        time: '10:00',
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
    ), array_fill(0, $count, null));
}

it('keeps the total at least the appointments it carries, and at most 10 of them (property)', function () {
    $this->forAll(Eris\Generator\choose(0, 10), Eris\Generator\choose(0, 60))->then(function (int $count, int $more) {
        $page = AppointmentPage::create(pagePropertyAppointments($count), $count + $more);

        expect(count($page->items))->toBe($count)
            ->and(count($page->items))->toBeLessThanOrEqual(10)
            ->and($page->total)->toBe($count + $more)
            ->and($page->total)->toBeGreaterThanOrEqual(count($page->items));
    });
})->skip('020: pendiente de T031');

it('refuses any page over the limit or with a total under its appointments (property)', function () {
    $this->forAll(Eris\Generator\choose(0, 20), Eris\Generator\choose(-5, 30))->then(function (int $count, int $total) {
        $valid = $count <= 10 && $total >= $count;
        $refused = false;

        try {
            AppointmentPage::create(pagePropertyAppointments($count), $total);
        } catch (AppointmentPageException) {
            $refused = true;
        }

        expect($refused)->toBe(! $valid);
    });
})->skip('020: pendiente de T031');
