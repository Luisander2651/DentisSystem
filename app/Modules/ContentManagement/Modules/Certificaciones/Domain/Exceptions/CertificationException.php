<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Modules\Certificaciones\Domain\Exceptions;

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

	public static function alreadyExists(string $field, mixed $value): self
	{
		return new self(sprintf('Certification with %s "%s" already exists.', $field, (string)$value));
	}

	public static function invalidDateFormat(string $value): self
	{
		return new self(sprintf('Invalid certification date: %s. Expected format: Y-m-d', $value));
	}

	public static function empty(string $class): self
	{
		return new self(sprintf('<%s> must be a non-empty string.', $class));
	}

	public static function invalidFormat(string $class, string $value, string $expected): self
	{
		return new self(sprintf('<%s> does not allow the value <%s>. Expected format: %s', $class, $value, $expected));
	}

	public static function invalidValue(string $class, mixed $value): self
	{
		return new self(sprintf('<%s> does not allow the value <%s>.', $class, (string)$value));
	}

	public static function outOfRange(string $class, mixed $value, mixed $min, mixed $max): self
	{
		return new self(sprintf('<%s> must be between %s and %s. Got: %s', $class, (string)$min, (string)$max, (string)$value));
	}
}
