<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Modules\Certificaciones\Aplication\DTOs;

use App\Modules\ContentManagement\Modules\Certificaciones\Aplication\Exceptions\CertificationInputException;

final readonly class DeleteCertificationDTO
{
    public function __construct(
        public string $id,
    ) {
        if (empty($id)) {
            throw CertificationInputException::missingRequiredFields();
        }
    }
}
