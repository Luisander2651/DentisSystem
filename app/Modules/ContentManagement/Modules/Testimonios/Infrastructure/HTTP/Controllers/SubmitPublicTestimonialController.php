<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Modules\Testimonios\Infrastructure\HTTP\Controllers;

use App\Modules\ContentManagement\Modules\Testimonios\Aplication\DTOs\SubmitPublicTestimonialDTO;
use App\Modules\ContentManagement\Modules\Testimonios\Aplication\UseCases\SubmitPublicTestimonialUseCase;
use App\Modules\ContentManagement\Modules\Testimonios\Infrastructure\HTTP\Requests\SubmitPublicTestimonialRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;

/**
 * Spec 027: an opinion sent from /opinion. Only accepted submissions count (CA5, A7): a form with
 * errors never gets here. Each one is counted before saving, atomically, against two limits: three
 * every ten minutes per connection, where an IPv6 connection counts by its /64 network so changing
 * the address inside it gives no new quota, and thirty an hour for the whole site, which keeps the
 * queue of moderation bounded (R1). A filled trap field looks accepted but saves nothing (CA6). An
 * unexpected failure comes back with a message of its own and logs only the class (P7).
 */
final readonly class SubmitPublicTestimonialController
{
    private const LIMIT = 3;

    private const WINDOW_SECONDS = 600;

    private const SITE_LIMIT = 30;

    private const SITE_WINDOW_SECONDS = 3600;

    public function __construct(
        private SubmitPublicTestimonialUseCase $useCase,
    ) {}

    public function __invoke(SubmitPublicTestimonialRequest $request): RedirectResponse|Response
    {
        if (RateLimiter::hit('public-testimonials:'.self::network((string) $request->ip()), self::WINDOW_SECONDS) > self::LIMIT) {
            return response()->view('site.opinion', ['throttled' => 'Enviaste varias opiniones seguidas. Inténtalo de nuevo en unos minutos.'], 429);
        }

        if (RateLimiter::hit('public-testimonials:site', self::SITE_WINDOW_SECONDS) > self::SITE_LIMIT) {
            return response()->view('site.opinion', ['throttled' => 'Recibimos muchas opiniones en este momento. Inténtalo de nuevo más tarde.'], 429);
        }

        if (! $request->isFromARobot()) {
            try {
                $this->useCase->execute(new SubmitPublicTestimonialDTO(
                    author: (string) $request->validated('name'),
                    description: (string) $request->validated('opinion'),
                ));
            } catch (Throwable $exception) {
                Log::error('public_testimonial.save_failed', ['exception' => $exception::class]);

                return redirect()->route('opinion')
                    ->withInput($request->except('website'))
                    ->withErrors(['form' => 'No pudimos guardar tu opinión. Inténtalo de nuevo en un momento.']);
            }
        }

        return redirect()->route('opinion')->with('opinion_sent', true);
    }

    /** The connection a limit counts: an IPv4 address, or the /64 network of an IPv6 one. */
    private static function network(string $ip): string
    {
        if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return $ip;
        }

        return inet_ntop(substr((string) inet_pton($ip), 0, 8).str_repeat("\0", 8)).'/64';
    }
}
