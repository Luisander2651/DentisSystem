<?php

declare(strict_types=1);

namespace Tests\Modules\Users\Integration;

use App\Modules\Users\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Str;
use Tests\Support\ActingAsPatient;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

abstract class UsersIntegrationTestCase extends TestCase
{
    use ActingAsPatient;
    use ActingAsStaff;
    use RefreshDatabase;

    /**
     * Eris\TestTrait declares a `$seed` property and RefreshDatabase decides whether
     * to seed with `property_exists($this, 'seed') ? $this->seed : false`, so any
     * property-based test that also refreshes the database would run
     * `migrate:fresh --seed` and fire database/seeders/DatabaseSeeder.php - a
     * leftover skeleton seeder referencing App\Models\User, a class this modular app
     * does not have. Seeding is switched off explicitly to break that collision.
     * RoleSeeder is invoked on its own where a test needs it (see seedRoles()).
     */
    protected function shouldSeed(): bool
    {
        return false;
    }

    /**
     * The api rate limiter caps unauthenticated traffic at 10 requests/min per IP.
     * Property-based tests fire far more than that inside a single test, so they opt
     * out explicitly.
     */
    protected function withoutRateLimiting(): void
    {
        $this->withoutMiddleware(ThrottleRequests::class);
    }

    /**
     * Laravel keeps one application instance for the whole test, so the sanctum guard
     * caches the user it resolved on the first authenticated request. A test that
     * authenticates as one actor and then asserts behaviour for another must clear
     * that cache, otherwise the stale actor answers from memory.
     */
    protected function forgetAuthState(): void
    {
        $this->app['auth']->forgetGuards();
    }

    // --- URLs ---

    protected function usersUrl(): string
    {
        return '/api/v1/users';
    }

    protected function userUrl(string $id): string
    {
        return '/api/v1/users/'.$id;
    }

    // --- Payloads ---

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function validRegisterUserPayload(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Ana',
            'last_name' => 'Ruiz',
            'email' => Str::uuid().'@example.com',
            'password' => 'Sup3rSecret!',
            'role_id' => 'Asistente',
        ], $overrides);
    }

    // --- Domain helpers ---

    /**
     * Persist a staff member directly, bypassing the endpoint, for tests that need an
     * existing row rather than to exercise the creation path.
     *
     * @param  array<string, mixed>  $overrides
     */
    protected function existingUser(string $roleName = 'Asistente', array $overrides = []): UserModel
    {
        return $this->createUserWithRole($roleName, $overrides);
    }

    protected function missingUserId(): string
    {
        return (string) Str::uuid();
    }
}
