<?php

declare(strict_types=1);

namespace Tests\Modules\Auth\Integration;

use App\Modules\Auth\Domain\Events\SendEmailForChangePasswordEvent;
use App\Modules\Auth\Domain\Service\PasswordResetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Tests\Support\ActingAsPatient;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

abstract class AuthIntegrationTestCase extends TestCase
{
    use ActingAsPatient;
    use ActingAsStaff;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // FR4: the Brevo SDK must never be constructed nor called from the suite.
        // tests/Support/FakesBrevo.php cannot help here - BrevoApi is a final class,
        // so Mockery cannot substitute it (finding recorded for Unit 6 - Email).
        // Faking the domain event stops SendPasswordResetListener from running at
        // all, which keeps the boundary closed without touching production code.
        // Token generation happens in the use case before the event is dispatched,
        // so the reset flow is still exercised end to end.
        Event::fake([SendEmailForChangePasswordEvent::class]);
    }

    /**
     * Eris\TestTrait declares a `$seed` property (its RNG seed) and Laravel's
     * RefreshDatabase decides whether to seed with
     * `property_exists($this, 'seed') ? $this->seed : false`. Any Eris test that
     * also refreshes the database therefore runs `migrate:fresh --seed`, which
     * fires database/seeders/DatabaseSeeder.php - a leftover skeleton seeder
     * referencing App\Models\User, a class this modular app does not have.
     * Seeding is switched off explicitly to break that collision.
     */
    protected function shouldSeed(): bool
    {
        return false;
    }

    /**
     * BR-12 caps unauthenticated traffic at 10 requests/min per IP. Property-based
     * tests fire far more than that inside a single test, so they opt out of the
     * throttle explicitly. RateLimitAndErrorLeakTest deliberately does NOT call
     * this, because verifying the 429 is its whole purpose.
     */
    protected function withoutRateLimiting(): void
    {
        $this->withoutMiddleware(ThrottleRequests::class);
    }

    /**
     * Laravel keeps one application instance for the whole test, so the sanctum
     * guard caches the user it resolved on the first authenticated request. A test
     * that authenticates and then asserts the same token no longer works must clear
     * that cache, otherwise the stale request is answered from memory.
     */
    protected function forgetAuthState(): void
    {
        $this->app['auth']->forgetGuards();
    }

    // --- URLs ---

    protected function loginUrl(): string
    {
        return '/api/v1/auth/login';
    }

    protected function registerUrl(): string
    {
        return '/api/v1/auth/register';
    }

    protected function logoutUrl(): string
    {
        return '/api/v1/auth/logout';
    }

    protected function sendResetUrl(): string
    {
        return '/api/v1/auth/send-reset-password-email';
    }

    protected function resetUrl(): string
    {
        return '/api/v1/auth/reset-password';
    }

    // --- Payloads ---

    /**
     * @return array{email:string,password:string}
     */
    protected function validLoginPayload(array $overrides = []): array
    {
        return array_merge([
            'email' => Str::uuid().'@example.com',
            'password' => 'Sup3rSecret!',
        ], $overrides);
    }

    /**
     * @return array{first_name:string,last_name:string,email:string,password:string,confirm_password:string}
     */
    protected function validRegisterPayload(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => Str::uuid().'@example.com',
            'password' => 'Sup3rSecret!',
            'confirm_password' => 'Sup3rSecret!',
        ], $overrides);
    }

    // --- Reset token helpers ---

    /**
     * Issue a reset token straight from the domain service, for tests that need a
     * valid token without exercising the request endpoint.
     */
    protected function generateResetTokenFor(string $email): string
    {
        return app(PasswordResetService::class)->generateResetToken($email);
    }

    /**
     * Drive the real request endpoint and capture the token carried by the domain
     * event, so the test asserts on the token the user would actually receive.
     */
    protected function requestResetTokenViaApi(string $email): ?string
    {
        $this->postJson($this->sendResetUrl(), ['email' => $email])->assertOk();

        $captured = null;

        Event::assertDispatched(SendEmailForChangePasswordEvent::class, function (SendEmailForChangePasswordEvent $event) use (&$captured): bool {
            $captured = $event->token;

            return true;
        });

        return $captured;
    }

    protected function resetTokenKey(string $token): string
    {
        return "password_reset:token:{$token}";
    }

    /**
     * Simulate expiry by dropping the key. Note there is deliberately no global
     * Redis flush in tearDown: the suite runs with --parallel against a single
     * Redis instance, so flushing would destroy sibling processes' keys. Tokens
     * are 40 random characters with a 900s TTL, so leftovers cannot collide and
     * expire on their own.
     */
    protected function forgetResetToken(string $token): void
    {
        Redis::del($this->resetTokenKey($token));
    }
}
