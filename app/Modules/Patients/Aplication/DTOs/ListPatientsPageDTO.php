<?php

declare(strict_types=1);

namespace App\Modules\Patients\Aplication\DTOs;

/**
 * Spec 028 (CA1, CA4): the page of the patients list asked for, twelve cards by default, with the
 * status filter and the search. An empty status or search means none.
 */
final readonly class ListPatientsPageDTO
{
    public const DEFAULT_PER_PAGE = 12;

    private function __construct(
        public int $page,
        public int $perPage,
        public ?string $status,
        public ?string $search,
    ) {}

    public static function create(?int $page, ?int $perPage, ?string $status, ?string $search): self
    {
        $search = $search === null ? null : trim($search);

        return new self(
            page: $page ?? 1,
            perPage: $perPage ?? self::DEFAULT_PER_PAGE,
            status: ($status === null || $status === '') ? null : $status,
            search: ($search === null || $search === '') ? null : $search,
        );
    }
}
