<?php

declare(strict_types=1);

namespace App\Core\Middlewares;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Closure;
use Database\Seeders\UiAuditSeeder;
use DateTimeImmutable;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Spec 020 (D16, TM9): `npm run test:ui` opens the dashboard on the day UiAuditSeeder seeded by
 * sending the instant in X-Ui-Audit-Now (ISO 8601 with offset). In the environments of
 * UiAuditSeeder::ALLOWED_ENVIRONMENTS it fixes now() while the request is handled; anywhere
 * else, or with a value that is not a date, it does nothing.
 *
 * It is registered in the web and api groups whatever the environment, because the web stack
 * is put together before the environment is loaded, and it checks the environment on every
 * request (A27). The clock goes back before the outer middleware add their cookies, so the
 * session and XSRF-TOKEN still expire by the real clock (A38).
 */
final class UiAuditClock
{
    public const HEADER = 'X-Ui-Audit-Now';

    private const FORMAT = 'Y-m-d\TH:i:sP';

    public function handle(Request $request, Closure $next): Response
    {
        $fixed = $this->fixedNow($request);

        if ($fixed === null) {
            return $next($request);
        }

        $previous = Carbon::getTestNow();
        $previousImmutable = CarbonImmutable::getTestNow();

        Carbon::setTestNow($fixed);
        CarbonImmutable::setTestNow($fixed);

        try {
            return $next($request);
        } finally {
            Carbon::setTestNow($previous);
            CarbonImmutable::setTestNow($previousImmutable);
        }
    }

    private function fixedNow(Request $request): ?CarbonImmutable
    {
        if (! app()->environment(UiAuditSeeder::ALLOWED_ENVIRONMENTS)) {
            return null;
        }

        $value = (string) $request->headers->get(self::HEADER, '');

        if ($value === '') {
            return null;
        }

        $instant = DateTimeImmutable::createFromFormat(self::FORMAT, $value);

        // A date that does not exist (30 February) rolls over; it is not the one that was sent.
        if ($instant === false || $instant->format(self::FORMAT) !== $value) {
            return null;
        }

        return CarbonImmutable::instance($instant);
    }
}
