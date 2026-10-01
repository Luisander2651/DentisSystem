<?php

declare(strict_types=1);

namespace Tests\Modules\Core\Integration;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Tests\Support\ActingAsPatient;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * Base for the cross-cutting behaviour that lives in app/Core and bootstrap/:
 * security headers, CORS, trusted proxies, the health check and the web error page.
 */
abstract class CoreIntegrationTestCase extends TestCase
{
    use ActingAsPatient;
    use ActingAsStaff;
    use RefreshDatabase;

    /**
     * Same reason as the other module bases: Eris' `$seed` property would make
     * RefreshDatabase run the skeleton DatabaseSeeder, which this app does not use.
     */
    protected function shouldSeed(): bool
    {
        return false;
    }

    /**
     * The api rate limiter caps unauthenticated traffic at 10 requests/min per IP.
     * Tests that are not about the limiter itself opt out explicitly.
     */
    protected function withoutRateLimiting(): void
    {
        $this->withoutMiddleware(ThrottleRequests::class);
    }
}
