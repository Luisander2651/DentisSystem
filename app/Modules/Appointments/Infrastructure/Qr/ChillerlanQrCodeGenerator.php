<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Infrastructure\Qr;

use App\Modules\Appointments\Aplication\Support\QrCodeGeneratorInterface;
use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Output\QRMarkupSVG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

/**
 * Spec 024 (ADR 0007): the QR code of an appointment with chillerlan/php-qrcode. Error correction
 * M so phones read it on dim screens; one path per module (`connectPaths` off) and no light
 * modules, so the document only draws the dark squares on a quiet zone.
 */
final readonly class ChillerlanQrCodeGenerator implements QrCodeGeneratorInterface
{
    public function svg(string $content): string
    {
        $options = new QROptions([
            'outputInterface' => QRMarkupSVG::class,
            'outputBase64' => false,
            'eccLevel' => EccLevel::M,
            'addQuietzone' => true,
            'connectPaths' => false,
            'drawLightModules' => false,
        ]);

        return (new QRCode($options))->render($content);
    }
}
