<?php

declare(strict_types=1);

namespace App\Core\Http\Requests;

/**
 * Normalises password fields by trimming leading and trailing whitespace.
 *
 * Introduced by Unit 3 as BR-23 for the Auth module and moved to Core by Unit 4 (BR-14),
 * where the Users module needs exactly the same rule for `password` and `new_password`.
 * Keeping one copy is deliberate: two implementations of the same credential-normalising
 * rule can drift, and a drift here silently locks accounts out.
 *
 * Laravel's TrimStrings middleware exempts `current_password`, `password` and
 * `password_confirmation`, but this project uses `confirm_password` and
 * `new_password`, which are not exempt. That asymmetry meant `password` kept its
 * padding while its confirmation lost it, so `same:password` could never pass for
 * a padded password, and a reset stored the TRIMMED value while login compared the
 * untrimmed one, locking the account silently.
 *
 * The middleware's exempt list can only be added to, never reduced
 * (TrimStrings::except() merges into $neverTrim), so normalisation is done here
 * instead - local to the Auth module, and at the layer where the semantics belong.
 *
 * Only the borders are trimmed: internal whitespace is part of the password and is
 * preserved, so passphrases keep working exactly as before.
 */
trait TrimsPasswordFields
{
    /**
     * @return list<string>
     */
    abstract protected function passwordFields(): array;

    protected function prepareForValidation(): void
    {
        $normalised = [];

        foreach ($this->passwordFields() as $field) {
            $value = $this->input($field);

            if (is_string($value)) {
                $normalised[$field] = trim($value);
            }
        }

        if ($normalised !== []) {
            $this->merge($normalised);
        }
    }
}
