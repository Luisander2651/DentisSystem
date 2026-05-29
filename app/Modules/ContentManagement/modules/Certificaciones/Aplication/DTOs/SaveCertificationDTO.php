<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Modules\Certificaciones\Aplication\DTOs;

use App\Modules\ContentManagement\Modules\Certificaciones\Aplication\Exceptions\CertificationInputException;
use Illuminate\Http\UploadedFile;

final readonly class SaveCertificationDTO
{
    public function __construct(
        public string $name,
        public string $description,
        public string $date,
        public UploadedFile $image,
    ) {
        if (empty($name) || empty($description) || empty($date) || empty($image)) {
            throw CertificationInputException::missingRequiredFields();
        }
    }
}