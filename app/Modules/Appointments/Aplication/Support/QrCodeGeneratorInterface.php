<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Aplication\Support;

/**
 * Spec 024 (D1): draws a QR code. It is an output format, not a rule of the appointments, so
 * the port lives in the Application layer; the adapter that knows the library lives in
 * Infrastructure.
 */
interface QrCodeGeneratorInterface
{
    /**
     * An SVG document whose QR code holds exactly `$content`.
     */
    public function svg(string $content): string;
}
