<?php

declare(strict_types=1);

namespace Tests\Modules\ContentManagement\Integration;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * Minimal base for the ContentManagement module. Spec 014 only exercises the unexpected-error
 * path of its controllers; the module is Unit 5 of the paused AI-DLC flow, so this base does
 * not anticipate any of its pending design decisions.
 */
abstract class ContentManagementIntegrationTestCase extends TestCase
{
    use ActingAsStaff;
    use RefreshDatabase;
}
