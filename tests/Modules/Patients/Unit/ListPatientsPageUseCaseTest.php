<?php

declare(strict_types=1);

use App\Core\Authorization\AuthorizationServiceInterface;
use App\Core\Authorization\Exceptions\AuthorizationException;
use App\Core\Domain\Pagination\Page;
use App\Core\Domain\Pagination\PageRequest;
use App\Modules\Patients\Aplication\DTOs\ListPatientsPageDTO;
use App\Modules\Patients\Aplication\UseCases\ListPatientsPageUseCase;
use App\Modules\Patients\Domain\Entities\Patient;
use App\Modules\Patients\Domain\Repositories\PatientsRepositoryInterface;
use App\Modules\Patients\Domain\Service\PatientService;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientEmail;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientId;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientStatus;
use Tests\TestCase;

uses(TestCase::class);

/*
 * Spec 028 (CA5, CA14; P5): the patients list is read one page at a time, with the counts of the
 * whole list, and only by someone allowed to see patients: without the permission nothing is
 * read nor counted.
 */

final class PatientsPageCallLog
{
    /** @var list<array{0: string, 1?: mixed}> */
    public array $calls = [];
}

function patientsPageAuthorization(bool $allowed): AuthorizationServiceInterface
{
    return new class($allowed) implements AuthorizationServiceInterface
    {
        public function __construct(private bool $allowed) {}

        public function assertCan(string $permission): void
        {
            if (! $this->allowed || $permission !== 'patients.view') {
                throw AuthorizationException::forbidden($permission);
            }
        }

        public function can(string $permission): bool
        {
            return $this->allowed && $permission === 'patients.view';
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

function patientsPageRepository(PatientsPageCallLog $log): PatientsRepositoryInterface
{
    return new class($log) implements PatientsRepositoryInterface
    {
        public function __construct(private PatientsPageCallLog $log) {}

        public function findPage(PageRequest $request, ?PatientStatus $status, ?string $search): Page
        {
            $this->log->calls[] = ['findPage', [$request->page, $request->size, $status?->value, $search]];

            return Page::create([], 30, $request);
        }

        public function countByStatus(): array
        {
            $this->log->calls[] = ['countByStatus'];

            return ['total' => 30, 'active' => 28, 'inactive' => 2];
        }

        public function findByPatientId(PatientId $patientId): ?Patient
        {
            $this->log->calls[] = ['findByPatientId'];

            return null;
        }

        public function save(Patient $patient): void
        {
            $this->log->calls[] = ['save'];
        }

        public function delete(PatientId $patientId): void
        {
            $this->log->calls[] = ['delete'];
        }

        public function findByRoleAndStatus(?PatientStatus $status): array
        {
            $this->log->calls[] = ['findByRoleAndStatus'];

            return [];
        }

        public function findByEmailExcludingId(PatientEmail $email, ?PatientId $excludedId): ?Patient
        {
            $this->log->calls[] = ['findByEmailExcludingId'];

            return null;
        }
    };
}

function patientsPageUseCase(PatientsPageCallLog $log, bool $allowed): ListPatientsPageUseCase
{
    return new ListPatientsPageUseCase(
        patientsPageAuthorization($allowed),
        new PatientService(patientsPageRepository($log)),
    );
}

it('neither reads nor counts the patients without the permission', function () {
    $log = new PatientsPageCallLog;

    expect(fn () => patientsPageUseCase($log, false)->execute(ListPatientsPageDTO::create(2, 12, 'active', 'ana')))
        ->toThrow(AuthorizationException::class);

    expect($log->calls)->toBe([]);
});

it('gives the page asked for with the counts of the whole list', function () {
    $log = new PatientsPageCallLog;

    $result = patientsPageUseCase($log, true)->execute(ListPatientsPageDTO::create(2, 12, 'inactive', 'ana'));

    expect($result['page']->page)->toBe(2)
        ->and($result['page']->total)->toBe(30)
        ->and($result['counts'])->toBe(['total' => 30, 'active' => 28, 'inactive' => 2])
        ->and($log->calls)->toBe([
            ['findPage', [2, 12, 'inactive', 'ana']],
            ['countByStatus'],
        ]);
});

it('asks for the first page of twelve with no status nor search when none is given', function () {
    $log = new PatientsPageCallLog;

    patientsPageUseCase($log, true)->execute(ListPatientsPageDTO::create(null, null, '', '  '));

    expect($log->calls[0])->toBe(['findPage', [1, 12, null, null]]);
});

it('never asks for more than fifty patients at once', function () {
    $log = new PatientsPageCallLog;

    patientsPageUseCase($log, true)->execute(ListPatientsPageDTO::create(1, 10000, null, null));

    expect($log->calls[0])->toBe(['findPage', [1, 50, null, null]]);
});
