<?php

declare(strict_types=1);

namespace App\Modules\Users\Infrastructure\Http\Requests;

use App\Modules\Users\Domain\ValueObjects\UserRoleId;

/**
 * BR-16: accepts any capitalisation of a role literal and normalises it to the form the
 * `roles` table stores, before validation runs.
 *
 * Normalising here as well as in the value object is deliberate: without it, Rule::in()
 * would reject `administrador` with a validation error even though the domain would have
 * accepted it, so the error message would be a lie.
 */
trait CanonicalisesRoleInput
{
    protected function canonicaliseRoleInput(string $field = 'role_id'): void
    {
        $submitted = $this->input($field);

        if (! is_string($submitted)) {
            return;
        }

        $needle = mb_strtolower(trim($submitted));

        foreach (UserRoleId::all() as $canonical) {
            if (mb_strtolower($canonical) === $needle) {
                $this->merge([$field => $canonical]);

                return;
            }
        }
    }
}
