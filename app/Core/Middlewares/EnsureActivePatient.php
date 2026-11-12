<?php

declare(strict_types=1);

namespace App\Core\Middlewares;

use App\Modules\Patients\Infrastructure\Persistence\Eloquent\Models\PatientModel;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Spec 025 (CA15, CA16, TM6): the sibling of EnsureActiveStaff for the patient portal. It lets
 * through only an active patient; staff of any role and a patient whose account the clinic
 * deactivated get 403. The status is read from the database on every request, so a session
 * opened before the deactivation stops working at once.
 */
final class EnsureActivePatient
{
    public function handle(Request $request, Closure $next): Response
    {
        $actor = $request->user();

        if (! $actor instanceof PatientModel) {
            return $this->forbidden($request, 'Only patients can access this resource.');
        }

        if (($actor->status ?? null) !== 'active') {
            return $this->forbidden($request, 'Your account is inactive.');
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
