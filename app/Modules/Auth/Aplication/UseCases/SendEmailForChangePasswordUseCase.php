<?php

declare(strict_types=1);

namespace App\Modules\Auth\Aplication\UseCases;

use App\Modules\Auth\Aplication\DTOs\SendEmailForChangePasswordDTO;
use App\Modules\Auth\Domain\Events\SendEmailForChangePasswordEvent;
use App\Modules\Auth\Domain\Service\PasswordResetService;
use App\Modules\Patients\Domain\Entities\Patient;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientEmail;
use App\Modules\Patients\Infrastructure\Persistence\Eloquent\EloquentPatientRepository;
use App\Modules\Users\Domain\Entities\UserEntity;
use App\Modules\Users\Domain\ValueObjects\UserEmail;
use App\Modules\Users\Infrastructure\Persistence\Eloquent\EloquentUserRepository;

final readonly class SendEmailForChangePasswordUseCase
{
    public function __construct(
        private EloquentPatientRepository $patientRepository,
        private EloquentUserRepository $userRepository,
        private PasswordResetService $passwordResetService,
    ) {}

    public function execute(SendEmailForChangePasswordDTO $dto): void
    {
        $patient = $this->findPatient($dto->email);
        $user = $patient === null ? $this->findUser($dto->email) : null;

        if ($patient === null && $user === null) {
            return;
        }

        $token = $this->passwordResetService->generateResetToken($dto->email);
        event(new SendEmailForChangePasswordEvent($patient, $user, $token));
    }

    /**
     * BR-9/BR-16: a malformed address is indistinguishable from an unknown one. The
     * value-object exception is swallowed so the endpoint keeps returning the same
     * neutral 200 instead of leaking a 500 or revealing which accounts exist.
     */
    private function findPatient(string $email): ?Patient
    {
        try {
            return $this->patientRepository->findByEmailExcludingId(new PatientEmail($email), null);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function findUser(string $email): ?UserEntity
    {
        try {
            return $this->userRepository->findByEmailExcludingId(UserEmail::fromString($email), null);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
