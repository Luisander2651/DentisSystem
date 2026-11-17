<?php

declare(strict_types=1);

use App\Modules\ContentManagement\Modules\Testimonios\Aplication\DTOs\SubmitPublicTestimonialDTO;
use App\Modules\ContentManagement\Modules\Testimonios\Aplication\UseCases\SubmitPublicTestimonialUseCase;
use App\Modules\ContentManagement\Modules\Testimonios\Infrastructure\Persistence\Eloquent\Models\TestimonialModel;
use Illuminate\Support\Facades\Log;
use Tests\Modules\ContentManagement\Integration\ContentManagementIntegrationTestCase;

uses(ContentManagementIntegrationTestCase::class);

/*
 * Spec 027: a visitor leaves an opinion from the public site. It always arrives hidden, with the
 * server's time, and the clinic publishes it from "Contenido". The log of each opinion received
 * keeps only its id and the consent, never the name or the text (TM4, TM6).
 */

it('keeps a public opinion hidden, with the time of the server, and logs only its id and the consent', function () {
    $this->freezeTime();
    Log::spy();

    app(SubmitPublicTestimonialUseCase::class)->execute(new SubmitPublicTestimonialDTO(
        author: 'Mariana López',
        description: 'Me atendieron con mucha paciencia y el resultado de mi limpieza fue excelente.',
    ));

    $row = TestimonialModel::query()->sole();

    expect($row->status)->toBe('oculto')
        ->and($row->author)->toBe('Mariana López')
        ->and($row->created_at->toDateTimeString())->toBe(now()->toDateTimeString());

    Log::shouldHaveReceived('info')->once()->withArgs(fn (string $message, array $context): bool => $message === 'public_testimonial.received'
        && $context === ['testimonial_id' => $row->id, 'consent' => true]);
});

it('keeps a name of eighty characters of four bytes each (A9)', function () {
    app(SubmitPublicTestimonialUseCase::class)->execute(new SubmitPublicTestimonialDTO(
        author: str_repeat('😀', 80),
        description: 'Una opinión con un nombre hecho solo de emoji, que mide ochenta caracteres.',
    ));

    expect(TestimonialModel::query()->sole()->author)->toBe(str_repeat('😀', 80));
});
