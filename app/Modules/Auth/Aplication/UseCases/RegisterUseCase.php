<?php

declare(strict_types=1);

namespace App\Modules\Auth\Aplication\UseCases;

use App\Modules\Auth\Aplication\DTOs\RegisterDTO;
use App\Modules\Auth\Domain\Service\RegisterService;
use App\Modules\Patients\Domain\Entities\Patient;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientEmail;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientName;
use App\Modules\Patients\Domain\ValueObjects\Patients\PasswordHash;

final readonly class RegisterUseCase
{
    public function __construct(
        private RegisterService $service,
    ) {}

    public function execute(RegisterDTO $dto): void
    {
        $email = new PatientEmail($dto->email);

        $patient = Patient::create(
            name: PatientName::create($dto->firstName, $dto->lastName),
            email: $email,
            passwordHash: PasswordHash::createFromPlainText($dto->password),
        );

        $this->service->registerPatient(
            patient: $patient,
            confirmPassword: $dto->confirmPassword,
        );
    }
}
