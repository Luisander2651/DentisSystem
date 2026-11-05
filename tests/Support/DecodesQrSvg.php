<?php

declare(strict_types=1);

namespace Tests\Support;

use chillerlan\QRCode\QRCode;
use DOMDocument;
use RuntimeException;

/**
 * Spec 024 (CA1, D6): reads back the text of a QR code drawn as SVG. GD cannot open an SVG, so the
 * dark modules (`<path class="… dark …" d="Mx y h1 v1 h-1Z …">`, drawn with `connectPaths` off)
 * are painted on a white image, with a quiet zone, and the PNG is decoded with the reader of
 * chillerlan/php-qrcode.
 */
trait DecodesQrSvg
{
    protected function decodeQrSvg(string $svg): string
    {
        $pixelsPerModule = 8;
        $quietZone = 4;

        $document = new DOMDocument;

        if (! $document->loadXML($svg)) {
            throw new RuntimeException('The QR code is not a valid SVG document.');
        }

        $viewBox = preg_split('/\s+/', trim($document->documentElement->getAttribute('viewBox')));

        if (count($viewBox) !== 4) {
            throw new RuntimeException('The QR code SVG has no viewBox.');
        }

        $size = ((int) $viewBox[2] + 2 * $quietZone) * $pixelsPerModule;
        $image = imagecreatetruecolor($size, $size);
        imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255));
        $black = imagecolorallocate($image, 0, 0, 0);

        foreach ($document->getElementsByTagName('path') as $path) {
            if (! in_array('dark', explode(' ', $path->getAttribute('class')), true)) {
                continue;
            }

            preg_match_all('/M(\d+)\s+(\d+)/', $path->getAttribute('d'), $modules, PREG_SET_ORDER);

            foreach ($modules as [, $x, $y]) {
                $left = ((int) $x + $quietZone) * $pixelsPerModule;
                $top = ((int) $y + $quietZone) * $pixelsPerModule;
                imagefilledrectangle($image, $left, $top, $left + $pixelsPerModule - 1, $top + $pixelsPerModule - 1, $black);
            }
        }

        ob_start();
        imagepng($image);
        $png = (string) ob_get_clean();

        return (string) (new QRCode)->readFromBlob($png);
    }
}
