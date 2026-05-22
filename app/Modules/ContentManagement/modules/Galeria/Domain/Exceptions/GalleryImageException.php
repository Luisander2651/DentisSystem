<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Modules\Galeria\Domain\Exceptions;

use App\Modules\ContentManagement\Modules\Galeria\Domain\ValueObjects\GalleryImageId;
use Exception;

final class GalleryImageException extends Exception
{
	public static function notFound(mixed $identifier): self
	{
		if ($identifier instanceof GalleryImageId) {
			return new self("Gallery image with ID {$identifier->value} not found.");
		}

		return new self('Gallery image not found.');
	}

	public static function invalidUrl(string $url): self
	{
		return new self(sprintf('Invalid gallery image URL: %s', $url));
	}

	public static function uploadFailed(string $reason = ''): self
	{
		$msg = 'Failed to upload gallery image.';
		if ($reason !== '') {
			$msg .= ' Reason: ' . $reason;
		}

		return new self($msg);
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
