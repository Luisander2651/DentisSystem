<?php

declare(strict_types=1);

namespace App\Modules\Auth\Domain\Service;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use App\Modules\Auth\Domain\Exceptions\PasswordResetServiceException;
use App\Modules\Auth\Infrastructure\Exceptions\Repositories\PasswordResetException;
use App\Modules\Auth\Infrastructure\Repositories\PasswordResetRepository;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientEmail;
use App\Modules\Users\Domain\ValueObjects\UserEmail;
use App\Modules\Patients\Domain\ValueObjects\Patients\PasswordHash as PatientPasswordHash;
use App\Modules\Users\Domain\ValueObjects\PasswordHash as UserPasswordHash;

final readonly class PasswordResetService {

    public function __construct(
        private PasswordResetRepository $passwordResetRepository
    ) {}
    
    public function generateResetToken(string $email) 
    {
        // 1. Generar token criptográficamente seguro
        $token = Str::random(40);
        
        // 2. Guardar en Redis usando el patrón que definimos
        Redis::setex("password_reset:token:$token", 900, json_encode([
            'email' => $email,
            'status' => 'PENDING'
        ]));
        
        return $token;
    }

    private function validateResetToken(string $token): ?array 
    {
        $data = Redis::get("password_reset:token:$token");
        
        if (!$data) {
            throw PasswordResetServiceException::TokenNotFound();
        }
        
        $decodedData = json_decode($data, true);
        
        if ($decodedData['status'] !== 'PENDING') {
            throw PasswordResetServiceException::TokenAlreadyUsed();
        }
        
        return $decodedData;
    }

    private function markTokenAsUsed(string $token): void 
    {
        $data = Redis::get("password_reset:token:$token");
        
        if (!$data) {
            throw PasswordResetServiceException::TokenNotFound();
        }
        
        $decodedData = json_decode($data, true);
        $decodedData['status'] = 'USED';
        
        Redis::setex("password_reset:token:$token", 900, json_encode($decodedData));
    }

    public function resetPassword(string $token, string $newPassword): void 
    {
        $data = $this->validateResetToken($token);

        $email = $data['email'];

        try {
            $userEmailVo = UserEmail::fromString($email);
            $user = $this->passwordResetRepository->findUserByEmail($userEmailVo);

            $this->passwordResetRepository->updatePassword($user, UserPasswordHash::createFromPlainText($newPassword));
            $this->markTokenAsUsed($token);

            return;
        } catch (PasswordResetException $e) {
        }

        try {
            $patientEmailVo = PatientEmail::fromString($email);
            $patient = $this->passwordResetRepository->findPatientByEmail($patientEmailVo);

            $this->passwordResetRepository->updatePassword($patient, PatientPasswordHash::createFromPlainText($newPassword));
            $this->markTokenAsUsed($token);

            return;
        } catch (PasswordResetException $e) {
            throw PasswordResetServiceException::UserNotFound();
        }
    }
}