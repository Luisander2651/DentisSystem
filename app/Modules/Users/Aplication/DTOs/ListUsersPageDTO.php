<?php

declare(strict_types=1);

namespace App\Modules\Users\Aplication\DTOs;

/**
 * Spec 028 (CA1): the page of the staff list asked for, twelve cards by default, with the status
 * and role filters. An empty filter means none.
 */
final readonly class ListUsersPageDTO
{
    public const DEFAULT_PER_PAGE = 12;

    private function __construct(
        public int $page,
        public int $perPage,
        public ?string $status,
        public ?string $role,
    ) {}

    public static function create(?int $page, ?int $perPage, ?string $status, ?string $role): self
    {
        return new self(
            page: $page ?? 1,
            perPage: $perPage ?? self::DEFAULT_PER_PAGE,
            status: ($status === null || $status === '') ? null : $status,
            role: ($role === null || $role === '') ? null : $role,
        );
    }
}
