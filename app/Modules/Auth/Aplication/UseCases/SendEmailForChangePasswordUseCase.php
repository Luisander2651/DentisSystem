<?php

declare(strict_types=1);

namespace App\Modules\Auth\Aplication\UseCases;

use App\Modules\Auth\Domain\Events\SendEmailForChangePasswordEvent;
use App\Modules\Auth\Aplication\Dtos\SendEmailForChangePasswordDTO;
use App\Modules\Patients\Infrastructure\Persistence\Eloquent\EloquentPatientRepository;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientEmail;
use App\Modules\Auth\Domain\Service\PasswordResetService;

final readonly class SendEmailForChangePasswordUseCase
{
    public function __construct(
        private EloquentPatientRepository $patientRepository,
        private PasswordResetService $passwordResetService,
    ) {}

    public function execute(SendEmailForChangePasswordDTO $dto): void
    {
        $emailVo = new PatientEmail($dto->email);

        $patient = $this->patientRepository->findByEmailExcludingId($emailVo, null);
        
        $token = $this->passwordResetService->generateResetToken($dto->email);
        event(new SendEmailForChangePasswordEvent($patient, $token));
    }
}
