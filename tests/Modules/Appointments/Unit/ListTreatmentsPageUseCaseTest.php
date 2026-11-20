<?php

declare(strict_types=1);

use App\Core\Authorization\AuthorizationServiceInterface;
use App\Core\Authorization\Exceptions\AuthorizationException;
use App\Core\Domain\Pagination\Page;
use App\Core\Domain\Pagination\PageRequest;
use App\Modules\Appointments\Aplication\DTOs\ListTreatmentsPageDTO;
use App\Modules\Appointments\Aplication\UseCases\ListTreatmentsPageUseCase;
use App\Modules\Appointments\Domain\Entities\TreatmentEntity;
use App\Modules\Appointments\Domain\Repositories\TreatmentsRepositoryInterface;
use App\Modules\Appointments\Domain\Service\TreatmentsService;
use App\Modules\Appointments\Domain\ValueObjects\TreatmentId;
use App\Modules\Appointments\Domain\ValueObjects\TreatmentName;
use Tests\TestCase;

uses(TestCase::class);

/*
 * Spec 028 (CA5, CA14; P5): the treatments list is read one page at a time with the stats of the
 * whole catalogue, and only by someone allowed to see treatments: without the permission nothing
 * is read nor counted.
 */

final class TreatmentsPageCallLog
{
    /** @var list<array{0: string, 1?: mixed}> */
    public array $calls = [];
}

function treatmentsPageAuthorization(bool $allowed): AuthorizationServiceInterface
{
    return new class($allowed) implements AuthorizationServiceInterface
    {
        public function __construct(private bool $allowed) {}

        public function assertCan(string $permission): void
        {
            if (! $this->allowed || $permission !== 'treatments.view') {
                throw AuthorizationException::forbidden($permission);
            }
        }

        public function can(string $permission): bool
        {
            return $this->allowed && $permission === 'treatments.view';
        }

        public function currentActorId(): string
        {
            throw AuthorizationException::unauthenticated();
        }

        public function currentPatientId(): string
        {
            throw AuthorizationException::unauthenticated();
        }
    };
}

function treatmentsPageRepository(TreatmentsPageCallLog $log): TreatmentsRepositoryInterface
{
    return new class($log) implements TreatmentsRepositoryInterface
    {
        public function __construct(private TreatmentsPageCallLog $log) {}

        public function findPage(PageRequest $request, ?string $search): Page
        {
            $this->log->calls[] = ['findPage', [$request->page, $request->size, $search]];

            return Page::create([], 30, $request);
        }

        public function stats(): array
        {
            $this->log->calls[] = ['stats'];

            return ['total' => 30, 'with_duration' => 28, 'average_minutes' => 45];
        }

        public function save(TreatmentEntity $treatment): void
        {
            $this->log->calls[] = ['save'];
        }

        public function findById(TreatmentId $id): ?TreatmentEntity
        {
            $this->log->calls[] = ['findById'];

            return null;
        }

        public function findAllByIdByName(?TreatmentId $id, ?TreatmentName $treatmentName): array
        {
            $this->log->calls[] = ['findAllByIdByName'];

            return [];
        }

        public function delete(TreatmentId $id): void
        {
            $this->log->calls[] = ['delete'];
        }
    };
}

function treatmentsPageUseCase(TreatmentsPageCallLog $log, bool $allowed): ListTreatmentsPageUseCase
{
    return new ListTreatmentsPageUseCase(
        new TreatmentsService(treatmentsPageRepository($log)),
        treatmentsPageAuthorization($allowed),
    );
}

it('neither reads nor counts the treatments without the permission', function () {
    $log = new TreatmentsPageCallLog;

    expect(fn () => treatmentsPageUseCase($log, false)->execute(ListTreatmentsPageDTO::create(2, 12, 'limpieza')))
        ->toThrow(AuthorizationException::class);

    expect($log->calls)->toBe([]);
});

it('gives the page asked for with the stats of the whole catalogue', function () {
    $log = new TreatmentsPageCallLog;

    $result = treatmentsPageUseCase($log, true)->execute(ListTreatmentsPageDTO::create(2, 12, 'limpieza'));

    expect($result['page']->page)->toBe(2)
        ->and($result['page']->total)->toBe(30)
        ->and($result['counts'])->toBe(['total' => 30, 'with_duration' => 28, 'average_minutes' => 45])
        ->and($log->calls)->toBe([
            ['findPage', [2, 12, 'limpieza']],
            ['stats'],
        ]);
});

it('asks for the first page of twelve with no search when none is given', function () {
    $log = new TreatmentsPageCallLog;

    treatmentsPageUseCase($log, true)->execute(ListTreatmentsPageDTO::create(null, null, '  '));

    expect($log->calls[0])->toBe(['findPage', [1, 12, null]]);
});

it('never asks for more than fifty treatments at once', function () {
    $log = new TreatmentsPageCallLog;

    treatmentsPageUseCase($log, true)->execute(ListTreatmentsPageDTO::create(1, 10000, null));

    expect($log->calls[0])->toBe(['findPage', [1, 50, null]]);
});
