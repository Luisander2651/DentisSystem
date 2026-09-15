<?php

declare(strict_types=1);

namespace App\Modules\Users\Domain\ValueObjects;

use App\Modules\Users\Domain\Exceptions\ValueObjects\UserRoleException;

/**
 * BR-16: the canonical vocabulary is the one the `roles` table stores - Administrador,
 * Asistente, Doctor - not the former admin/asistent/doctor, which matched nothing in the
 * database. That mismatch was papered over by a hand-written translation table in
 * resources/js/pages/usuarios/edit-user.js; with a single vocabulary, it is gone.
 *
 * Construction is case-insensitive and always canonicalises, so a client may send
 * `administrador` or `ADMINISTRADOR` and what the domain exposes is the exact literal the
 * table holds - comparable against the database without normalising at each use.
 *
 * BR-9: toDatabaseId()/fromDatabaseId() still translate to the primary key with a
 * hardcoded 1/2/3 mapping. RoleSeeder (BR-17) turns those ids into a versioned contract,
 * but replacing the translation with a real lookup against the table is finding 8's
 * Level C, deferred to a dedicated iteration. See
 * aidlc-docs/construction/plans/unit-4-users-finding-8-analysis.md.
 */
final class UserRoleId
{
    private const ADMINISTRADOR = 'Administrador';

    private const ASISTENTE = 'Asistente';

    private const DOCTOR = 'Doctor';

    private const VALID_ROLES = [
        self::ADMINISTRADOR,
        self::ASISTENTE,
        self::DOCTOR,
    ];

    public readonly string $value;

    public function __construct(string $value)
    {
        $canonical = self::canonicalise($value);

        if ($canonical === null) {
            // The rejected literal is passed as a plain string, never as a half-built
            // instance: `$this->value` is not assigned yet at this point, and readonly
            // properties throw on uninitialised access. This is the same trap that made
            // UserName::create() recurse forever before BR-10.
            throw UserRoleException::invalidFormat($value);
        }

        $this->value = $canonical;
    }

    /**
     * Resolves any capitalisation to the literal the `roles` table stores, or null when
     * the input names no role at all.
     */
    private static function canonicalise(string $value): ?string
    {
        $needle = mb_strtolower(trim($value));

        foreach (self::VALID_ROLES as $role) {
            if (mb_strtolower($role) === $needle) {
                return $role;
            }
        }

        return null;
    }

    /**
     * The canonical literals, in the order the roles table stores them. Exposed so the
     * HTTP layer can validate against the domain vocabulary instead of repeating it.
     *
     * @return list<string>
     */
    public static function all(): array
    {
        return self::VALID_ROLES;
    }

    public static function administrador(): self
    {
        return new self(self::ADMINISTRADOR);
    }

    public static function asistente(): self
    {
        return new self(self::ASISTENTE);
    }

    public static function doctor(): self
    {
        return new self(self::DOCTOR);
    }

    public static function fromString(string $role): self
    {
        return new self($role);
    }

    public function isAdministrador(): bool
    {
        return $this->value === self::ADMINISTRADOR;
    }

    public function isAsistente(): bool
    {
        return $this->value === self::ASISTENTE;
    }

    public function isDoctor(): bool
    {
        return $this->value === self::DOCTOR;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function toDatabaseId(): int
    {
        return match ($this->value) {
            self::ADMINISTRADOR => 1,
            self::ASISTENTE => 2,
            self::DOCTOR => 3,
            default => throw UserRoleException::notFound($this->value),
        };
    }

    public static function fromDatabaseId(string $id): self
    {
        return match ((int) $id) {
            1 => self::administrador(),
            2 => self::asistente(),
            3 => self::doctor(),
            default => throw UserRoleException::invalidFormat($id),
        };
    }
}
