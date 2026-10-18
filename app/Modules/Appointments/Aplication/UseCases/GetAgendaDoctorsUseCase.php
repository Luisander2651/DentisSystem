<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Aplication\UseCases;

use App\Core\Authorization\AuthorizationServiceInterface;
use App\Modules\Users\Domain\Entities\UserEntity;
use App\Modules\Users\Domain\Service\UserService;
use App\Modules\Users\Domain\ValueObjects\UserRoleId;
use App\Modules\Users\Domain\ValueObjects\UserStatus;

/**
 * Spec 018 (D11): who may book an appointment may list who can attend it, without the
 * management of users (`users.view`) that the assistant does not hold.
 */
final readonly class GetAgendaDoctorsUseCase
{
    public function __construct(
        private AuthorizationServiceInterface $authorization,
        private UserService $userService,
    ) {}

    /**
     * @return list<UserEntity>
     */
    public function execute(): array
    {
        $this->authorization->assertCan('agenda.selectors.view');

        $active = UserStatus::fromString('active');
        $attendees = [];

        foreach (['doctor', 'administrador'] as $role) {
            foreach ($this->userService->findByRoleAndStatus($active, UserRoleId::fromString($role)) as $user) {
                $attendees[(string) $user->id()->value] = $user;
            }
        }

        return array_values($attendees);
    }
}
