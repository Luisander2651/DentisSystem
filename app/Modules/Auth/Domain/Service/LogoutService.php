<?php

declare(strict_types=1);

namespace App\Modules\Auth\Domain\Service;

use App\Modules\Auth\Domain\Exceptions\AuthException;
use App\Modules\Patients\Infrastructure\Persistence\Eloquent\Models\PatientModel;
use App\Modules\Users\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Laravel\Sanctum\PersonalAccessToken;

final class LogoutService
{
	public function logout(UserModel|PatientModel $actor, ?string $plainTextToken = null): void
	{
		$currentToken = $actor->currentAccessToken();

		if ($currentToken instanceof PersonalAccessToken) {
			$currentToken->delete();

			return;
		}

		if (is_string($plainTextToken) && trim($plainTextToken) !== '') {
			$token = PersonalAccessToken::findToken(trim($plainTextToken));

			if ($token instanceof PersonalAccessToken) {
				$token->delete();

				return;
			}
		}

		throw AuthException::tokenRevocationFailed();
	}
}
