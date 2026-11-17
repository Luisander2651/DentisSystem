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
 * Spec 027: an opinion sent from /opinion. Only accepted submissions count for the limit of three
 * every ten minutes per connection (CA5, A7): a form with errors never gets here. A filled trap field
 * looks accepted but saves nothing (CA6). An unexpected failure comes back with a message of its own
 * and logs only the class (P7).
 */
final readonly class SubmitPublicTestimonialController
{
    private const LIMIT = 3;

    private const WINDOW_SECONDS = 600;

    public function __construct(
        private SubmitPublicTestimonialUseCase $useCase,
    ) {}

    public function __invoke(SubmitPublicTestimonialRequest $request): RedirectResponse|Response
    {
        $key = 'public-testimonials:'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, self::LIMIT)) {
            return response()->view('site.opinion', ['throttled' => true], 429);
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

        RateLimiter::hit($key, self::WINDOW_SECONDS);

        return redirect()->route('opinion')->with('opinion_sent', true);
    }
}
