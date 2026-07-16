<?php

declare(strict_types=1);

namespace App\Modules\Auth\Aplication\UseCases;

use App\Modules\Auth\Domain\Events\SendEmailForChangePasswordEvent;
use App\Modules\Auth\Aplication\Dtos\SendEmailForChangePasswordDTO;
use App\Modules\Patients\Infrastructure\Persistence\Eloquent\EloquentPatientRepository;
use App\Modules\Users\Infrastructure\Persistence\Eloquent\EloquentUserRepository;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientEmail;
use App\Modules\Users\Domain\ValueObjects\UserEmail;
use App\Modules\Auth\Domain\Service\PasswordResetService;

final readonly class SendEmailForChangePasswordUseCase
{
    public function __construct(
        private EloquentPatientRepository $patientRepository,
        private EloquentUserRepository $userRepository,
        private PasswordResetService $passwordResetService,
    ) {}

    public function execute(SendEmailForChangePasswordDTO $dto): void
    {
        $user = null;
        $patientEmailVo = new PatientEmail($dto->email);

        $patient = $this->patientRepository->findByEmailExcludingId($patientEmailVo, null);

        if (!$patient) {
            $usersEmailVo = UserEmail::fromString($dto->email);
            $user = $this->userRepository->findByEmailExcludingId($usersEmailVo, null);

            if (!$user) {
                return;
            }
        }
        $token = $this->passwordResetService->generateResetToken($dto->email);
        event(new SendEmailForChangePasswordEvent($patient, $user, $token));
    }
}
