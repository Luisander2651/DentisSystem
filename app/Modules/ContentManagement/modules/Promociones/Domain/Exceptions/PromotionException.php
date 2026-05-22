<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Modules\Promociones\Domain\Exceptions;

use App\Modules\ContentManagement\Modules\Promociones\Domain\ValueObjects\PromotionId;
use Exception;

final class PromotionException extends Exception
{
	public static function notFound(mixed $identifier): self
	{
		if ($identifier instanceof PromotionId) {
			return new self("Promotion with ID {$identifier->value} not found.");
		}

		return new self('Promotion not found.');
	}

	public static function invalidDiscount(float $value): self
	{
		return new self(sprintf('Invalid discount percentage: %s. Must be between 0 and 100.', (string)$value));
	}

	public static function dateRangeInvalid(string $from, string $to): self
	{
		return new self(sprintf('Invalid promotion date range: %s to %s.', $from, $to));
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
