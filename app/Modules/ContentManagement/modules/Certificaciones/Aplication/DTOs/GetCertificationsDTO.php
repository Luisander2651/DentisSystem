<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Modules\Certificaciones\Aplication\DTOs;

final readonly class GetCertificationsDTO
{
    public function __construct(
        public ?string $id = null,
        public ?string $name = null,
    ) {}
}
