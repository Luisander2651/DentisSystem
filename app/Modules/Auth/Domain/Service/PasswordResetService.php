<?php

declare(strict_types=1);

namespace App\Modules\Auth\Domain\Service;

use App\Modules\Auth\Domain\Exceptions\PasswordResetServiceException;
use App\Modules\Auth\Infrastructure\Exceptions\Repositories\PasswordResetException;
use App\Modules\Auth\Infrastructure\Repositories\PasswordResetRepository;
use App\Modules\Patients\Domain\ValueObjects\Patients\PasswordHash as PatientPasswordHash;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientEmail;
use App\Modules\Users\Domain\ValueObjects\PasswordHash as UserPasswordHash;
use App\Modules\Users\Domain\ValueObjects\UserEmail;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

final readonly class PasswordResetService
{
    private const TOKEN_TTL_SECONDS = 900;

    public function __construct(
        private PasswordResetRepository $passwordResetRepository
    ) {}

    public function generateResetToken(string $email): string
    {
        $token = Str::random(40);

        Redis::setex($this->tokenKey($token), self::TOKEN_TTL_SECONDS, json_encode([
            'email' => $email,
            'status' => 'PENDING',
        ]));

        return $token;
    }

    /**
     * @return array{email:string,status:string}
     */
    private function validateResetToken(string $token): array
    {
        $data = Redis::get($this->tokenKey($token));

        if (! $data) {
            throw PasswordResetServiceException::TokenNotFound();
        }

        $decodedData = json_decode($data, true);

        if ($decodedData['status'] !== 'PENDING') {
            throw PasswordResetServiceException::TokenAlreadyUsed();
        }

        return $decodedData;
    }

    /**
     * BR-17: a consumed token is deleted outright instead of being rewritten with a
     * refreshed 900s TTL, so it cannot linger in Redis past its original expiry.
     * Reusing it now yields TokenNotFound rather than TokenAlreadyUsed.
     */
    private function consumeToken(string $token): void
    {
        Redis::del($this->tokenKey($token));
    }

    private function tokenKey(string $token): string
    {
        return "password_reset:token:{$token}";
    }

    public function resetPassword(string $token, string $newPassword): void
    {
        $data = $this->validateResetToken($token);

        $email = $data['email'];

        try {
            $userEmailVo = UserEmail::fromString($email);
            $user = $this->passwordResetRepository->findUserByEmail($userEmailVo);

            $this->passwordResetRepository->updatePassword($user, UserPasswordHash::createFromPlainText($newPassword));
            $this->consumeToken($token);

            return;
        } catch (PasswordResetException $e) {
        }

        try {
            $patientEmailVo = PatientEmail::fromString($email);
            $patient = $this->passwordResetRepository->findPatientByEmail($patientEmailVo);

            $this->passwordResetRepository->updatePassword($patient, PatientPasswordHash::createFromPlainText($newPassword));
            $this->consumeToken($token);

            return;
        } catch (PasswordResetException $e) {
            throw PasswordResetServiceException::UserNotFound();
        }
    }
}
