<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Modules\Certificaciones\Aplication\Exceptions;

use App\Modules\ContentManagement\Modules\Certificaciones\Domain\ValueObjects\CertificationId;
use Exception;

final class CertificationException extends Exception
{
    public static function notFound(mixed $identifier): self
    {
        if ($identifier instanceof CertificationId) {
            return new self("Certification with ID {$identifier->value} not found.");
        }

        return new self('Certification not found.');
    }
}
