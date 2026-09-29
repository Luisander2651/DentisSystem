<?php

declare(strict_types=1);

namespace Tests\Modules\Email\Integration;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Tests\Support\ActingAsPatient;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * Base for the e-mail flows. Unlike AuthIntegrationTestCase it does not fake
 * SendEmailForChangePasswordEvent, so the listener, the use case and BrevoApi really run.
 * Each test must therefore swap the Brevo client for a stub before triggering a flow.
 */
abstract class EmailIntegrationTestCase extends TestCase
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

    protected function withoutRateLimiting(): void
    {
        $this->withoutMiddleware(ThrottleRequests::class);
    }

    protected function sendResetUrl(): string
    {
        return '/api/v1/auth/send-reset-password-email';
    }
}
