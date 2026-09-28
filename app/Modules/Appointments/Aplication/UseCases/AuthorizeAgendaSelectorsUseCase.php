<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Aplication\UseCases;

use App\Core\Authorization\AuthorizationServiceInterface;

/**
 * Spec 014 (plan, D5): the patient selector of the appointment form reuses the patients list,
 * which every staff member may read, while the agenda and its selectors are only for the
 * administrator. The selector asks this use case first instead of deciding in the controller.
 */
final readonly class AuthorizeAgendaSelectorsUseCase
{
    public function __construct(
        private AuthorizationServiceInterface $authorization,
    ) {}

    public function execute(): void
    {
        $this->authorization->assertCan('agenda.selectors.view');
    }
}
