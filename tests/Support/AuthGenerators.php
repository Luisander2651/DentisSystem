<?php

declare(strict_types=1);

namespace Tests\Support;

use Eris\Generator;

use function Eris\Generator\choose;
use function Eris\Generator\elements;
use function Eris\Generator\map;
use function Eris\Generator\tuple;

/**
 * Domain-specific generators for the Auth unit (PBT-07). Raw primitive generators
 * produce meaningless credentials, so every generator here respects the business
 * constraints the module actually enforces.
 */
trait AuthGenerators
{
    /**
     * Addresses that always pass FILTER_VALIDATE_EMAIL, covering subdomains, plus
     * tags, digits and short/long local parts.
     */
    protected function validEmailGenerator(): Generator
    {
        return map(
            fn (array $parts): string => sprintf('%s%s@%s', $parts[0], $parts[1], $parts[2]),
            tuple(
                elements(['a', 'john', 'maria.jose', 'p4tient', 'x_y-z', 'verylonglocalpartforemail']),
                elements(['', '+tag', '+2026', '.test']),
                elements(['example.com', 'mail.example.com', 'clinica-dentissa.mx', 'sub.domain.co.uk']),
            ),
        );
    }

    /**
     * Passwords BELOW the 8-character policy of BR-14 - always rejected with 422.
     */
    protected function tooShortPasswordGenerator(): Generator
    {
        return map(
            fn (int $length): string => substr('aB3!xY9', 0, $length),
            choose(1, 7),
        );
    }

    /**
     * Passwords that satisfy BR-14, including Unicode, spaces and the bcrypt
     * 72-byte boundary.
     */
    protected function validPasswordGenerator(): Generator
    {
        return elements([
            'Sup3rSecret!',
            'password',
            '12345678',
            'contraseña con acentos',
            'con espacios internos',
            str_repeat('z', 72),
            'ñÑáéíóú8',
        ]);
    }

    /**
     * Passwords padded with whitespace. Kept separate from
     * validPasswordGenerator() because they exercise BR-23 specifically: the
     * borders are normalised away, so the padded and trimmed forms are the same
     * credential. See WhitespacePasswordTest.
     */
    protected function paddedPasswordGenerator(): Generator
    {
        return elements([
            '   spaced out   ',
            '	Sup3rSecret!	',
            'trailing space ',
            ' leading space',
        ]);
    }

    /**
     * First/last name pairs whose combined length stays inside PatientName's 3-50
     * range (Unit 2, BR-10).
     */
    protected function validPatientNameGenerator(): Generator
    {
        return tuple(
            elements(['Ana', 'John', 'Maria de Jesus', 'Luis Antonio', 'Zoe']),
            elements(['Doe', 'Rebolledo Murga', 'Perez', 'Garcia Lopez', 'Wu']),
        );
    }
}
