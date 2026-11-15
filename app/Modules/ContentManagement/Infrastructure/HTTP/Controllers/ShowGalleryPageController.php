<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Infrastructure\HTTP\Controllers;

use App\Modules\ContentManagement\Modules\Galeria\Aplication\DTOs\GetGalleryImagesDTO;
use App\Modules\ContentManagement\Modules\Galeria\Aplication\UseCases\GetGalleryImagesUseCase;
use App\Modules\ContentManagement\Modules\Galeria\Domain\Entities\GalleryImageEntity;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * The gallery of the public site (spec 026): the visible images come in the HTML the server sends
 * (CA5), never the hidden ones (CA14), behind a cache of ten minutes (TM5) and, when they cannot
 * be read, an empty gallery instead of an error (P7).
 */
final readonly class ShowGalleryPageController
{
    private const CACHE_KEY = 'public_gallery.visible.v1';

    private const CACHE_TTL_MINUTES = 10;

    private const VISIBLE_STATUS = 'visible';

    public function __construct(
        private GetGalleryImagesUseCase $useCase,
    ) {}

    public function __invoke(): View
    {
        if (! config('site.new_landing')) {
            return view('pages.landing.galeria');
        }

        return view('site.galeria', ['images' => $this->images()]);
    }

    /**
     * @return list<array{url: string, description: string}>
     */
    private function images(): array
    {
        try {
            return Cache::remember(
                self::CACHE_KEY,
                now()->addMinutes(self::CACHE_TTL_MINUTES),
                fn (): array => array_values(array_map(
                    fn (GalleryImageEntity $image): array => [
                        'url' => $image->Url()->value,
                        'description' => (string) $image->Description()->value,
                    ],
                    $this->useCase->execute(new GetGalleryImagesDTO(status: self::VISIBLE_STATUS)),
                )),
            );
        } catch (Throwable $exception) {
            Log::error('public_gallery.load_failed', ['exception' => $exception::class]);

            return [];
        }
    }
}
