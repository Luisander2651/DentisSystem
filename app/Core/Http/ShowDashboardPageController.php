<?php

declare(strict_types=1);

namespace App\Core\Http;

use App\Core\Authorization\AuthorizationServiceInterface;
use App\Modules\Users\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Spec 020 (CA10, CA16, A17, D20): the start of the panel. It hands the view the role, the date
 * and time of the clinic, the month in course and what the role may do, so the page shows only
 * its actions; the data comes from the API, which checks every permission again (P5). An inactive
 * staff member gets "Sin permiso", as in /agenda. Exception to P3 recorded in the plan (D20): the
 * view carries no protected data, so there is no use case in between.
 */
final readonly class ShowDashboardPageController
{
    public function __construct(
        private AuthorizationServiceInterface $authorization,
    ) {}

    public function __invoke(Request $request): View
    {
        $actor = $request->user();
        $isStaff = $actor instanceof UserModel;

        if ($isStaff && ($actor->status ?? null) !== 'active') {
            abort(403);
        }

        $now = now()->toImmutable();

        return view('pages.dashboard', [
            'dashboardRole' => $isStaff ? mb_strtolower((string) ($actor->role?->name ?? '')) : 'paciente',
            'dashboardNow' => $now,
            'dashboardMonth' => $now->format('Y-m'),
            'dashboardCan' => $isStaff ? $this->capabilities() : [],
        ]);
    }

    /**
     * @return array<string, bool>
     */
    private function capabilities(): array
    {
        $completes = $this->authorization->can('appointment-tracking.create')
            || $this->authorization->can('appointment-tracking.create-own');

        return [
            'complete' => $completes,
            'register' => $completes,
            'cancel' => $this->authorization->can('appointments.update'),
            'create_appointment' => $this->authorization->can('appointments.create'),
            'create_patient' => $this->authorization->can('patients.create'),
            'reports' => $this->authorization->can('reports.view'),
        ];
    }
}
