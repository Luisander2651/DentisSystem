<?php

declare(strict_types=1);

namespace App\Core\Middlewares;

use App\Modules\Users\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Spec 014: lets through only an active staff member (never a patient token, which the
 * sanctum guard also accepts) and, when roles are given (`staff:administrador,doctor`), only
 * those roles. Per-operation permissions are still decided by assertCan() in each use case.
 */
final class EnsureActiveStaff
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $actor = $request->user();

        if (! $actor instanceof UserModel) {
            return $this->forbidden($request, 'Only staff can access this resource.');
        }

        if (($actor->status ?? null) !== 'active') {
            return $this->forbidden($request, 'Your account is inactive.');
        }

        if ($roles !== []) {
            $roleName = mb_strtolower((string) ($actor->role?->name ?? ''));
            $allowed = array_map(fn (string $role): string => mb_strtolower($role), $roles);

            if (! in_array($roleName, $allowed, true)) {
                return $this->forbidden($request, 'You are not allowed to access this resource.');
            }
        }

        return $next($request);
    }

    private function forbidden(Request $request, string $message): Response
    {
        if ($request->is('api/*')) {
            return new JsonResponse(['error' => $message], 403);
        }

        abort(403);
    }
}
