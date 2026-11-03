<?php

declare(strict_types=1);

namespace App\Modules\Auth\Domain\Service;

use App\Modules\Auth\Domain\Exceptions\AuthException;
use App\Modules\Auth\Domain\Exceptions\MobileAccessDeniedException;
use App\Modules\Patients\Infrastructure\Persistence\Eloquent\Models\PatientModel;
use App\Modules\Users\Domain\ValueObjects\UserRoleId;
use App\Modules\Users\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Illuminate\Support\Facades\Hash;

final class LoginService
{
    private const WEB_USER_TOKEN = 'user-auth';

    private const MOBILE_USER_TOKEN = 'user-mobile';

    /**
     * @return array{token:string,actorType:string,actorId:string,name:string,email:string}
     */
    public function login(string $email, string $password): array
    {
        $user = UserModel::query()->where('email', $email)->first();

        if ($user !== null && Hash::check($password, $user->password)) {
            return $this->issueTokenForUser($user);
        }

        $patient = PatientModel::query()->where('email', $email)->first();

        if ($patient !== null && Hash::check($password, $patient->password)) {
            return $this->issueTokenForPatient($patient);
        }

        throw AuthException::invalidCredentials();
    }

    /**
     * Only active administrators get a mobile session. A staff member or patient with the right
     * password is refused without issuing or revoking any token, so their web session survives.
     *
     * @return array{token:string,actorType:string,actorId:string,name:string,email:string}
     */
    public function loginAdministratorOnMobile(string $email, string $password): array
    {
        $user = UserModel::query()->where('email', $email)->first();

        if ($user !== null && Hash::check($password, $user->password)) {
            if (($user->status ?? null) !== 'active') {
                throw AuthException::inactiveAccount('user');
            }

            if (mb_strtolower((string) ($user->role?->name ?? '')) !== mb_strtolower(UserRoleId::administrador()->value)) {
                throw MobileAccessDeniedException::notAnAdministrator();
            }

            return $this->issueTokenForUser($user, self::MOBILE_USER_TOKEN);
        }

        $patient = PatientModel::query()->where('email', $email)->first();

        if ($patient !== null && Hash::check($password, $patient->password)) {
            throw MobileAccessDeniedException::notAnAdministrator();
        }

        throw AuthException::invalidCredentials();
    }

    /**
     * @return array{token:string,actorType:string,actorId:string,name:string,email:string}
     */
    private function issueTokenForUser(UserModel $user, string $tokenName = self::WEB_USER_TOKEN): array
    {
        if (($user->status ?? null) !== 'active') {
            throw AuthException::inactiveAccount('user');
        }

        // Keep a single active token per actor and channel, so the web and mobile sessions coexist:
        // a mobile login replaces only the mobile token, and a web login every other one.
        $user->tokens()
            ->where('name', $tokenName === self::MOBILE_USER_TOKEN ? '=' : '!=', self::MOBILE_USER_TOKEN)
            ->delete();
        $token = $user->createToken($tokenName)->plainTextToken;

        return [
            'token' => $token,
            'actorType' => 'user',
            'actorId' => (string) $user->id,
            'name' => trim("{$user->first_name} {$user->last_name}"),
            'email' => (string) $user->email,
        ];
    }

    /**
     * @return array{token:string,actorType:string,actorId:string,name:string,email:string}
     */
    private function issueTokenForPatient(PatientModel $patient): array
    {
        if (($patient->status ?? null) !== 'active') {
            throw AuthException::inactiveAccount('patient');
        }

        // Keep a single active token per actor.
        $patient->tokens()->delete();
        $token = $patient->createToken('patient-auth')->plainTextToken;

        return [
            'token' => $token,
            'actorType' => 'patient',
            'actorId' => (string) $patient->id,
            'name' => trim("{$patient->first_name} {$patient->last_name}"),
            'email' => (string) $patient->email,
        ];
    }
}
