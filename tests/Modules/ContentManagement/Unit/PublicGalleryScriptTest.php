<?php

declare(strict_types=1);

use Symfony\Component\Process\Process;

/*
 * Spec 011, CA3: /galeria shows the images that GET /api/v1/public/gallery-images returns.
 * With a single image the endpoint answers a bare object instead of a list, and the page must
 * still show it (docs/changes/2026-10-17-fix-galeria-una-imagen.md).
 */

/**
 * @param  array<mixed>  $payload
 * @return array{requested: ?string, list: string, emptyShown: bool, failed: bool}
 */
function renderPublicGallery(array $payload): array
{
    $process = new Process(['node', __DIR__.'/render-public-gallery.mjs', json_encode($payload, JSON_THROW_ON_ERROR)]);
    $process->mustRun();

    return json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR);
}

/**
 * @return array<string, string>
 */
function publicGalleryImage(string $id): array
{
    return [
        'id' => $id,
        'url' => "https://example.test/{$id}.jpg",
        'description' => "Imagen {$id}",
        'status' => 'visible',
        'created_at' => '2026-10-17 10:00:00',
        'updated_at' => '2026-10-17 10:00:00',
    ];
}

it('shows the image when the endpoint returns a single image as an object', function (): void {
    $result = renderPublicGallery(publicGalleryImage('una'));

    expect($result['requested'])->toBe('/api/v1/public/gallery-images')
        ->and($result['failed'])->toBeFalse()
        ->and($result['emptyShown'])->toBeFalse()
        ->and(substr_count($result['list'], 'class="gallery-card'))->toBe(1)
        ->and($result['list'])->toContain('https://example.test/una.jpg');
});

it('shows every image when the endpoint returns a list', function (): void {
    $result = renderPublicGallery([publicGalleryImage('a'), publicGalleryImage('b')]);

    expect($result['failed'])->toBeFalse()
        ->and(substr_count($result['list'], 'class="gallery-card'))->toBe(2)
        ->and($result['list'])->toContain('https://example.test/a.jpg', 'https://example.test/b.jpg');
});

it('shows the empty state when the endpoint returns no images', function (): void {
    $result = renderPublicGallery([]);

    expect($result['failed'])->toBeFalse()
        ->and($result['emptyShown'])->toBeTrue()
        ->and($result['list'])->toBe('');
});
