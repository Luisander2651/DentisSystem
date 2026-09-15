<?php

declare(strict_types=1);

namespace App\Core\Middlewares;

use App\Modules\Users\Domain\ValueObjects\UserRoleId;
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

        if (! $actor instanceof UserModel) {
            return $this->forbidden('Only users can access this resource.');
        }

        if (($actor->status ?? null) !== 'active') {
            return $this->forbidden('Your account is inactive.');
        }

        // BR-16: compared against the domain vocabulary instead of a fourth hardcoded
        // copy of the literal, so the middleware cannot drift from the value object.
        //
        // BR-27, corrected: the relation is loaded lazily here and Eloquent caches it on
        // the instance, so CurrentActorAuthorizationService reads it for free later in
        // the same request. Measured: 1 query for this access, 0 for the next. There is
        // no N+1 to remove and loadMissing() would change nothing.
        $roleName = mb_strtolower((string) ($actor->role?->name ?? ''));

        if ($roleName !== mb_strtolower(UserRoleId::administrador()->value)) {
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
