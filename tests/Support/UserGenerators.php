<?php

declare(strict_types=1);

namespace Tests\Support;

use Eris\Generator;

use function Eris\Generator\choose;
use function Eris\Generator\elements;
use function Eris\Generator\map;
use function Eris\Generator\tuple;

/**
 * Domain-specific generators for the Users unit (PBT-07). Raw primitive generators
 * produce meaningless staff records, so every generator here respects the business
 * constraints the module actually enforces (BR-1..BR-28).
 */
trait UserGenerators
{
    /**
     * First/last name pairs whose COMBINED length stays inside UserName's 3-50
     * range. The minimum applies to "first last", not to each field, so 'Zoe'+'Wu'
     * (6) is valid while 'A'+'B' (3 with the space) sits exactly on the boundary.
     */
    protected function validUserNameGenerator(): Generator
    {
        return tuple(
            elements(['Ana', 'John', 'Maria de Jesus', 'Luis Antonio', 'Zoe', 'ÁNGELA']),
            elements(['Doe', 'Rebolledo Murga', 'Perez', 'Garcia Lopez', 'Wu', 'ñuñez']),
        );
    }

    /**
     * Name pairs that UserName MUST reject. Before BR-10 these did not raise a
     * catchable exception at all - create() recursed until PHP died - so this
     * generator is the input space of property P1.
     */
    protected function invalidUserNameGenerator(): Generator
    {
        // Every pair here must be invalid, so no combination may land on a legal
        // combined length. 'A'+'B' is NOT in this set: "A B" is 3 characters, which
        // sits exactly on the minimum - the 3-50 bound applies to the combined name,
        // not to each field.
        return elements([
            ['', ''],
            ['', ' '],
            [' ', ''],
            ['  ', '   '],
            ['A', ''],
            ['', 'B'],
            ['A', ' '],
            [str_repeat('x', 60), ''],
            ['', str_repeat('y', 60)],
            [str_repeat('x', 30), str_repeat('y', 30)],
        ]);
    }

    /**
     * Arbitrary name pairs - valid, invalid and degenerate mixed together. P1 asserts
     * that create() TERMINATES over this whole space, either returning a UserName or
     * throwing UserNameException, never exhausting the stack.
     */
    protected function anyUserNameGenerator(): Generator
    {
        return tuple(
            elements(['', ' ', 'A', 'Ana', 'Maria de Jesus', str_repeat('x', 60), '123', '  padded  ']),
            elements(['', ' ', 'B', 'Doe', 'Garcia Lopez', str_repeat('y', 60), '!!!', "\ttabbed\t"]),
        );
    }

    /**
     * Name pairs whose combined form is 2 to 4 whitespace-separated words - the
     * precondition of the fromString/full round-trip (P3).
     *
     * fromString() splits into at most 4 parts and then always assigns exactly two
     * words to the first name, so a name of four or more words round-trips only when
     * its first name was already two words. That is finding 20, recorded during Code
     * Generation and deliberately NOT fixed here.
     */
    protected function roundTripUserNameGenerator(): Generator
    {
        return tuple(
            elements(['Ana', 'John', 'Zoe', 'ÁNGELA', 'Luis']),
            elements(['Doe', 'Perez', 'Wu', 'ñuñez', 'Garcia Lopez']),
        );
    }

    /**
     * Addresses that always pass FILTER_VALIDATE_EMAIL.
     */
    protected function validUserEmailGenerator(): Generator
    {
        return map(
            fn (array $parts): string => sprintf('%s%s@%s', $parts[0], $parts[1], $parts[2]),
            tuple(
                elements(['a', 'john', 'maria.jose', 'st4ff', 'x_y-z', 'verylonglocalpartforemail']),
                elements(['', '+tag', '+2026', '.test']),
                elements(['example.com', 'mail.example.com', 'clinica-dentissa.mx', 'sub.domain.co.uk']),
            ),
        );
    }

    /**
     * Strings FILTER_VALIDATE_EMAIL always rejects.
     */
    protected function invalidUserEmailGenerator(): Generator
    {
        return elements([
            '',
            'not-an-email',
            '@example.com',
            'user@',
            'user@@example.com',
            'user example@test.com',
            'user@.com',
        ]);
    }

    /**
     * Passwords that satisfy the 8-character policy of BR-11, including Unicode and
     * the bcrypt 72-byte boundary.
     */
    protected function validUserPasswordGenerator(): Generator
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
     * Passwords BELOW the BR-11 policy - always rejected with 422.
     */
    protected function tooShortUserPasswordGenerator(): Generator
    {
        return map(
            fn (int $length): string => substr('aB3!xY9', 0, $length),
            choose(1, 7),
        );
    }

    /**
     * Padded passwords for P14: POST and PUT must store the SAME credential for the
     * same value, which they did not before BR-14 (TrimStrings exempts `password`
     * but not `new_password`).
     */
    protected function paddedUserPasswordGenerator(): Generator
    {
        return elements([
            '   Sup3rSecret!   ',
            "\tSup3rSecret!\t",
            'Sup3rSecret! ',
            ' Sup3rSecret!',
            "\n Sup3rSecret! \n",
        ]);
    }

    /**
     * The three canonical role literals exactly as the `roles` table stores them
     * (BR-16, BR-17).
     *
     * @return list<string>
     */
    protected function canonicalRoles(): array
    {
        return ['Administrador', 'Asistente', 'Doctor'];
    }

    protected function canonicalRoleGenerator(): Generator
    {
        return elements($this->canonicalRoles());
    }

    /**
     * Every role literal in assorted capitalisations. P9 asserts they all collapse to
     * the same canonical value, so a client may send 'administrador' or 'ADMINISTRADOR'
     * and get the same role.
     */
    protected function anyCaseRoleGenerator(): Generator
    {
        return elements([
            'Administrador', 'administrador', 'ADMINISTRADOR', 'aDmInIsTrAdOr',
            'Asistente', 'asistente', 'ASISTENTE',
            'Doctor', 'doctor', 'DOCTOR',
        ]);
    }

    /**
     * Strings that are never a role, including the pre-BR-16 vocabulary: `admin` and
     * `asistent` used to be the only accepted values, so they must now be rejected.
     */
    protected function invalidRoleGenerator(): Generator
    {
        return elements(['', 'admin', 'asistent', 'recepcionista', 'root', '1', 'Administradora']);
    }

    protected function userStatusGenerator(): Generator
    {
        return elements(['active', 'inactive']);
    }

    protected function invalidUserStatusGenerator(): Generator
    {
        return elements(['', 'Active', 'ACTIVE', 'enabled', 'activo', 'deleted', '1']);
    }

    /**
     * Strings that are not valid UUIDs. Before BR-19 these produced a 500, because
     * UuidIdentifier throws InvalidArgumentException, which does NOT descend from the
     * module's ValueObjectsException umbrella.
     */
    protected function malformedUuidGenerator(): Generator
    {
        return elements([
            'not-a-uuid',
            '123',
            '',
            '00000000-0000-0000-0000',
            'zzzzzzzz-zzzz-zzzz-zzzz-zzzzzzzzzzzz',
            '../../etc/passwd',
        ]);
    }
}
