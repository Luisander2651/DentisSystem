<?php

declare(strict_types=1);

namespace App\Modules\Auth\Domain\Service;

use App\Modules\Auth\Domain\Exceptions\AuthException;
use App\Modules\Patients\Infrastructure\Persistence\Eloquent\Models\PatientModel;
use App\Modules\Users\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Illuminate\Support\Facades\Hash;

final class LoginService
{
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
	 * @return array{token:string,actorType:string,actorId:string,name:string,email:string}
	 */
	private function issueTokenForUser(UserModel $user): array
	{
		if (($user->status ?? null) !== 'active') {
			throw AuthException::inactiveAccount('user');
		}

		// Keep a single active token per actor.
		$user->tokens()->delete();
		$token = $user->createToken('user-auth')->plainTextToken;

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
