<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Infrastructure\Qr;

use chillerlan\QRCode\Output\QRMarkupSVG;

/**
 * Review of spec 024 (R17): the SVG of chillerlan only carries a `viewBox`, and some browsers
 * (Firefox) refuse to draw on a canvas an image without a size of its own, so "Descargar QR"
 * would fail there. This output adds `width` and `height` to the root: each module is 8 px.
 */
final class SizedQrMarkupSvg extends QRMarkupSVG
{
    private const MODULE_PIXELS = 8;

    protected function header(): string
    {
        [$width, $height] = $this->getOutputDimensions();

        return (string) preg_replace(
            '/<svg /',
            sprintf('<svg width="%d" height="%d" ', $width * self::MODULE_PIXELS, $height * self::MODULE_PIXELS),
            parent::header(),
            1,
        );
    }
}
