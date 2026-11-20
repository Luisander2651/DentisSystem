<?php

declare(strict_types=1);

/*
 * Spec 028 (P3, A2): the use case of "Completadas sin seguimiento" reads through the domain
 * service PendingTrackingService, never from a repository directly.
 */
it('keeps the use case of the pending follow-ups away from the repositories', function () {
    $path = dirname(__DIR__, 4).'/app/Modules/AppointmentTracking/Application/UseCases/GetCompletedWithoutTrackingUseCase.php';
    $source = (string) file_get_contents($path);

    expect($source)->not->toMatch('/RepositoryInterface/')
        ->and($source)->toContain('PendingTrackingService');
});
