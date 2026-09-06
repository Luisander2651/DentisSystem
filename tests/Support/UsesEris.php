<?php

declare(strict_types=1);

namespace Tests\Support;

use Eris\TestTrait;

/**
 * Exposes Eris (property-based testing) to Pest tests: forAll(), generators,
 * automatic shrinking and seed logging on failure. Each unit (1-7) adds its
 * own domain-specific generators (e.g. valid appointment dates, valid emails)
 * during its own Functional Design / Code Generation stage.
 */
trait UsesEris
{
    use TestTrait;
}
