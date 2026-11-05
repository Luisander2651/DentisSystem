<?php

declare(strict_types=1);

namespace Tests\Support;

use chillerlan\QRCode\Data\QRMatrix;
use chillerlan\QRCode\Decoder\BitMatrix;
use chillerlan\QRCode\Decoder\Decoder;
use chillerlan\QRCode\Decoder\DecoderResult;
use chillerlan\QRCode\Detector\Detector;
use DOMDocument;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use RuntimeException;

/**
 * Spec 024 (CA1, D6): reads back the text of a QR code drawn as SVG. The dark modules of the
 * document (`<path class="… dark …" d="Mx y h1 v1 h-1Z …">`, drawn with `connectPaths` off) give
 * the exact matrix, which goes straight to the decoder of chillerlan/php-qrcode. Its detector,
 * which looks for the code inside a picture, is skipped on purpose: on a rasterized picture it
 * misses about 1 in 1 000 codes, and these tests must not depend on chance.
 */
trait DecodesQrSvg
{
    protected function decodeQrSvg(string $svg): string
    {
        return (string) $this->decodeQrSvgResult($svg);
    }

    /**
     * The whole result of the decoder, with its error correction level and version.
     */
    protected function decodeQrSvgResult(string $svg): DecoderResult
    {
        $document = new DOMDocument;

        if (! $document->loadXML($svg)) {
            throw new RuntimeException('The QR code is not a valid SVG document.');
        }

        $darkModules = [];

        foreach ($document->getElementsByTagName('path') as $path) {
            if (! in_array('dark', explode(' ', $path->getAttribute('class')), true)) {
                continue;
            }

            preg_match_all('/M(\d+)\s+(\d+)/', $path->getAttribute('d'), $modules, PREG_SET_ORDER);

            foreach ($modules as [, $x, $y]) {
                $darkModules[] = [(int) $x, (int) $y];
            }
        }

        if ($darkModules === []) {
            throw new RuntimeException('The QR code SVG draws no dark module.');
        }

        // The finder patterns are dark at both ends of the code, so the dark modules span it whole.
        $left = min(array_column($darkModules, 0));
        $top = min(array_column($darkModules, 1));
        $dimension = max(array_column($darkModules, 0)) - $left + 1;

        $matrix = new BitMatrix($dimension);

        for ($y = 0; $y < $dimension; $y++) {
            for ($x = 0; $x < $dimension; $x++) {
                $matrix->set($x, $y, false, QRMatrix::M_DATA);
            }
        }

        foreach ($darkModules as [$x, $y]) {
            $matrix->set($x - $left, $y - $top, true, QRMatrix::M_DATA);
        }

        return $this->decodeQrMatrix($matrix);
    }

    /**
     * `Decoder::decodeMatrix()` is private and reads the finder patterns of its detector for the
     * result, so a detector without patterns stands in for the one that never ran.
     */
    private function decodeQrMatrix(BitMatrix $matrix): DecoderResult
    {
        $detector = (new ReflectionClass(Detector::class))->newInstanceWithoutConstructor();
        (new ReflectionProperty($detector, 'finderPatterns'))->setValue($detector, []);

        $decoder = new Decoder;
        (new ReflectionProperty($decoder, 'detector'))->setValue($decoder, $detector);

        return (new ReflectionMethod($decoder, 'decodeMatrix'))->invoke($decoder, $matrix);
    }
}
