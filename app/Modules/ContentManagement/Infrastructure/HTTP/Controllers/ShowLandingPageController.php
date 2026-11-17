<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Infrastructure\HTTP\Controllers;

use App\Core\Authorization\Exceptions\AuthorizationException;
use App\Modules\ContentManagement\Application\DTOs\GetLandingContentDTO;
use App\Modules\ContentManagement\Application\UseCases\GetLandingContentUseCase;
use App\Modules\ContentManagement\Infrastructure\HTTP\Resources\LandingPageResource;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

final readonly class ShowLandingPageController
{
    /**
     * A new key: the previous one cached promotions without the validity filter (spec 026, A46).
     * The clinic's day is part of it, so that one day never serves the promotions of another.
     */
    private const CACHE_KEY = 'landing.content.visible.v2';

    private const CACHE_TTL_MINUTES = 10;

    private const VISIBLE_STATUS = 'visible';

    private const CLINIC_TIMEZONE = 'America/Mexico_City';

    public function __construct(
        private GetLandingContentUseCase $useCase,
    ) {}

    public function __invoke(Request $request): View
    {
        $view = config('site.new_landing') ? 'site.inicio' : 'pages.landing.inicio';

        return view($view, $this->content($request));
    }

    /**
     * Resolves the published content, degrading to an empty landing page rather
     * than a 500 when the CMS content cannot be read.
     *
     * @return array{
     *     promotions: array<int, array<string, mixed>>,
     *     certifications: array<int, array<string, mixed>>,
     *     testimonials: array<int, array<string, mixed>>,
     * }
     */
    private function content(Request $request): array
    {
        try {
            return Cache::remember(
                self::CACHE_KEY.'.'.CarbonImmutable::now(self::CLINIC_TIMEZONE)->toDateString(),
                $this->cacheUntil(),
                function () use ($request): array {
                    $content = $this->useCase->execute(
                        new GetLandingContentDTO(status: self::VISIBLE_STATUS),
                    );

                    return (new LandingPageResource($content))->toArray($request);
                },
            );
        } catch (AuthorizationException $exception) {
            abort(403, $exception->getMessage());
        } catch (Throwable $exception) {
            Log::error('Unable to load landing page content', ['exception' => $exception]);

            return $this->emptyContent();
        }
    }

    /**
     * Ten minutes, or the clinic's next midnight if it comes first, so that a promotion that ends
     * today is not shown tomorrow (spec 026, CA14).
     */
    private function cacheUntil(): CarbonImmutable
    {
        $now = CarbonImmutable::now(self::CLINIC_TIMEZONE);

        return $now->addMinutes(self::CACHE_TTL_MINUTES)->min($now->addDay()->startOfDay());
    }

    /**
     * @return array{promotions: array<int, never>, certifications: array<int, never>, testimonials: array<int, never>}
     */
    private function emptyContent(): array
    {
        return [
            'promotions' => [],
            'certifications' => [],
            'testimonials' => [],
        ];
    }
}
