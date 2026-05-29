<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Domain\Exceptions;

use RuntimeException;

final class StorageException extends RuntimeException
{
    public static function emptyModuleName(): self
    {
        return new self('Module name cannot be empty.');
    }

    public static function emptyStoragePath(): self
    {
        return new self('Storage path cannot be empty.');
    }

    public static function invalidUploadedImage(): self
    {
        return new self('No valid image uploaded.');
    }

    public static function storeFailed(): self
    {
        return new self('Failed to store uploaded image.');
    }

    public static function imageConversionFailed(): self
    {
        return new self('Failed to convert image to AVIF format.');
    }

    public static function unsupportedImageExtension(): self
    {
        return new self('Unsupported image extension.');
    }

    public static function invalidImageMime(): self
    {
        return new self('Uploaded file is not an image.');
    }
}
