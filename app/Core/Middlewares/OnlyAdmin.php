<?php

declare(strict_types=1);

namespace App\Core\Middlewares;

use App\Modules\Users\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class OnlyAdmin
{
	public function handle(Request $request, Closure $next): Response
	{
		$actor = $request->user();

		if (!$actor instanceof UserModel) {
			return $this->forbidden('Only users can access this resource.');
		}

		if (($actor->status ?? null) !== 'active') {
			return $this->forbidden('Your account is inactive.');
		}

		$roleName = strtolower((string) optional($actor->role)->name);

		if ($roleName !== 'administrador') {
			return $this->forbidden('Only administrators can access this resource.');
		}

		return $next($request);
	}

	private function forbidden(string $message): JsonResponse
	{
		return response()->json([
			'error' => $message,
		], 403);
	}
}
