<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Modules\Testimonios\Domain\Exceptions;

use App\Modules\ContentManagement\Modules\Testimonios\Domain\ValueObjects\TestimonialId;
use Exception;

final class TestimonialException extends Exception
{
	public static function notFound(mixed $identifier): self
	{
		if ($identifier instanceof TestimonialId) {
			return new self("Testimonial with ID {$identifier->value} not found.");
		}

		return new self('Testimonial not found.');
	}

	public static function invalidAuthor(string $author): self
	{
		return new self(sprintf('Invalid testimonial author: %s', $author));
	}

	public static function invalidState(string $state): self
	{
		return new self(sprintf('Invalid testimonial state: %s', $state));
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
