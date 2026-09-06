<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Modules\Certificaciones\Aplication\DTOs;

use Illuminate\Http\UploadedFile;
use App\Modules\ContentManagement\Modules\Certificaciones\Aplication\Exceptions\CertificationInputException;

final readonly class UpdateCertificationDTO
{
    public function __construct(
        public string $id,
        public ?string $name = null,
        public ?string $description = null,
        public ?UploadedFile $image = null,
    ) {
        if (empty($id)) {
            throw CertificationInputException::missingRequiredFields();
        }
        if (empty($name) && empty($description) && empty($image)) {
            throw CertificationInputException::atLeastOneFieldRequired();
        }
    }
}
