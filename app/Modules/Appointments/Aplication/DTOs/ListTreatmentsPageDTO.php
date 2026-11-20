<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Aplication\DTOs;

/**
 * Spec 028 (CA1, CA4): the page of the treatments list asked for, twelve cards by default, with
 * the search. An empty search means none.
 */
final readonly class ListTreatmentsPageDTO
{
    public const DEFAULT_PER_PAGE = 12;

    private function __construct(
        public int $page,
        public int $perPage,
        public ?string $search,
    ) {}

    public static function create(?int $page, ?int $perPage, ?string $search): self
    {
        $search = $search === null ? null : trim($search);

        return new self(
            page: $page ?? 1,
            perPage: $perPage ?? self::DEFAULT_PER_PAGE,
            search: ($search === null || $search === '') ? null : $search,
        );
    }
}
